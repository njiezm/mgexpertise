<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

include 'db_connect.php';

$id_temps = intval($_POST['id_temps'] ?? 0);
$action = $_POST['action'] ?? '';
$commentaires = $_POST['commentaires'] ?? '';

if ($id_temps <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

// Récupérer l'id du dossier lié à ce temps
$stmtDossier = $pdo->prepare("SELECT dossier_id FROM affectation WHERE id = ?");
$stmtDossier->execute([$id_temps]);
$dossier_id = $stmtDossier->fetchColumn();

if (!$dossier_id) {
    echo json_encode(['success' => false, 'message' => 'Dossier non trouvé']);
    exit;
}

// Fonction pour réajuster le planning de l'utilisateur (laisses comme avant si utile)
function reajusterPlanningUtilisateur(PDO $pdo, int $user_id) {
    $sql = "SELECT a.id AS temps_id, d.id AS dossier_id, d.date_debut, d.date_fin, d.statut
            FROM affectation a
            JOIN dossiers d ON a.dossier_id = d.id
            WHERE a.utilisateur_id = ?
            ORDER BY d.date_debut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $prev_end = null;

    foreach ($dossiers as $dossier) {
        $date_debut = new DateTime($dossier['date_debut']);
        $date_fin = new DateTime($dossier['date_fin']);

        if ($prev_end) {
            if ($date_debut <= $prev_end) {
                $new_debut = clone $prev_end;
                $new_debut->modify('+1 day');
                $duration = $date_fin->diff($date_debut)->days;
                $new_fin = clone $new_debut;
                $new_fin->modify("+$duration days");

                if ($new_debut != $date_debut || $new_fin != $date_fin) {
                    $upd = $pdo->prepare("UPDATE dossiers SET date_debut = ?, date_fin = ? WHERE id = ?");
                    $upd->execute([$new_debut->format('Y-m-d'), $new_fin->format('Y-m-d'), $dossier['dossier_id']]);
                }
                $date_debut = $new_debut;
                $date_fin = $new_fin;
            }
        }

        // Si statut = 'en retard', on bloque la chaîne (on ne décale pas les suivants)
        if (($dossier['statut'] ?? '') === 'en retard') {
            break;
        }

        $prev_end = $date_fin;
    }
}

switch ($action) {
    case 'toggleValid':
        // Ici on considère que "validé" correspond à un statut "validé" dans dossiers
        $stmtGetStatut = $pdo->prepare("SELECT statut FROM dossiers WHERE id = ?");
        $stmtGetStatut->execute([$dossier_id]);
        $statut = $stmtGetStatut->fetchColumn();

        // Toggle entre "validé" et "en cours" (par exemple)
        if ($statut === 'validé') {
            $new_statut = 'en cours';
        } else {
            $new_statut = 'validé';
        }

        $stmtUpdate = $pdo->prepare("UPDATE dossiers SET statut = ? WHERE id = ?");
        $stmtUpdate->execute([$new_statut, $dossier_id]);
        break;

    case 'toggleRetard':
        // Impossible de déclarer un retard si dossier validé
        $stmtGetStatut = $pdo->prepare("SELECT statut FROM dossiers WHERE id = ?");
        $stmtGetStatut->execute([$dossier_id]);
        $statut = $stmtGetStatut->fetchColumn();

        if ($statut === 'validé') {
            echo json_encode(['success' => false, 'message' => 'Impossible de déclarer un retard sur un dossier validé']);
            exit;
        }

        // Toggle entre "en retard" et "en cours" par exemple
        if ($statut === 'en retard') {
            $new_statut = 'en cours';
        } else {
            $new_statut = 'en retard';
        }

        $stmtUpdate = $pdo->prepare("UPDATE dossiers SET statut = ? WHERE id = ?");
        $stmtUpdate->execute([$new_statut, $dossier_id]);
        break;

    case 'updateComment':
        $stmt = $pdo->prepare("UPDATE affectation SET commentaires = ? WHERE id = ?");
        $stmt->execute([$commentaires, $id_temps]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
        exit;
}

// Après action, réajuster le planning utilisateur
$stmtUser = $pdo->prepare("SELECT utilisateur_id FROM affectation WHERE id = ?");
$stmtUser->execute([$id_temps]);
$user_id = $stmtUser->fetchColumn();

if ($user_id) {
    reajusterPlanningUtilisateur($pdo, $user_id);
}

echo json_encode(['success' => true]);
