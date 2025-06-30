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

switch($action) {
    case 'toggleValid':
        $stmt = $pdo->prepare("UPDATE temps SET valide = NOT valide, retard_declared = CASE WHEN valide=1 THEN 0 ELSE retard_declared END WHERE id = ?");
        $stmt->execute([$id_temps]);
        break;
    case 'toggleRetard':
        // Vérifier que ce n'est pas validé
        $stmtCheck = $pdo->prepare("SELECT valide FROM temps WHERE id = ?");
        $stmtCheck->execute([$id_temps]);
        $valide = $stmtCheck->fetchColumn();
        if ($valide) {
            echo json_encode(['success' => false, 'message' => 'Impossible de déclarer un retard sur un dossier validé']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE temps SET retard_declared = NOT retard_declared WHERE id = ?");
        $stmt->execute([$id_temps]);
        break;
    case 'updateComment':
        $stmt = $pdo->prepare("UPDATE temps SET commentaires = ? WHERE id = ?");
        $stmt->execute([$commentaires, $id_temps]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
        exit;
}

echo json_encode(['success' => true]);
