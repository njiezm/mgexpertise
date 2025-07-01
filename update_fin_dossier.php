<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Non connecté']);
  exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$temps_id = $_POST['temps_id'] ?? null;
$new_date_fin = $_POST['new_date_fin'] ?? null;

if (!$temps_id || !$new_date_fin) {
  echo json_encode(['success' => false, 'message' => 'Données manquantes']);
  exit;
}

// Vérifier que ce temps appartient bien à l'utilisateur
$stmt = $pdo->prepare("SELECT t.id, t.dossier_id, d.date_debut, d.date_fin, t.estimation_2025
                       FROM temps t
                       JOIN dossiers d ON t.dossier_id = d.id
                       WHERE t.id = ? AND t.utilisateur_id = ?");
$stmt->execute([$temps_id, $user_id]);
$temps = $stmt->fetch();

if (!$temps) {
  echo json_encode(['success' => false, 'message' => 'Dossier non trouvé ou accès refusé']);
  exit;
}

try {
  $pdo->beginTransaction();

  $date_debut_dossier = new DateTime($temps['date_debut']);
  $new_fin = new DateTime($new_date_fin);

  if ($new_fin <= $date_debut_dossier) {
    throw new Exception('La nouvelle date de fin doit être après la date de début.');
  }

  // Mettre à jour la date_fin du dossier modifié
  $updateDossier = $pdo->prepare("UPDATE dossiers SET date_fin = ? WHERE id = ?");
  $updateDossier->execute([$new_date_fin, $temps['dossier_id']]);

  // Récupérer tous les dossiers suivants de l'utilisateur triés par date_debut
  $stmtS = $pdo->prepare("SELECT t.id, t.dossier_id, d.date_debut, d.date_fin, t.estimation_2025
                          FROM temps t
                          JOIN dossiers d ON t.dossier_id = d.id
                          WHERE t.utilisateur_id = ? AND d.date_debut > ?
                          ORDER BY d.date_debut ASC");
  $stmtS->execute([$user_id, $temps['date_debut']]);
  $dossiers_suivants = $stmtS->fetchAll();

  // Date de début pour le dossier suivant
  $current_start = (clone $new_fin)->modify('+1 day');

  foreach ($dossiers_suivants as $ds) {
    $duration_days = max(1, (int)round($ds['estimation_2025']));

    $new_start = clone $current_start;
    $new_end = (clone $new_start)->modify('+' . ($duration_days - 1) . ' days');

    $update = $pdo->prepare("UPDATE dossiers SET date_debut = ?, date_fin = ? WHERE id = ?");
    $update->execute([$new_start->format('Y-m-d'), $new_end->format('Y-m-d'), $ds['dossier_id']]);

    $current_start = $new_end->modify('+1 day');
  }

  $pdo->commit();
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  $pdo->rollBack();
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
