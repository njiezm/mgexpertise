<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_role = $_SESSION['user_role'] ?? 'Collaborateur';

//$today = date('Y-m-d');
$today = date('2025-01-17');


function extractClientName(string $nomClient): string {
    $parts = explode(' - ', $nomClient, 2);
    return count($parts) === 2 ? trim($parts[1]) : $nomClient;
}


// Requête planning global (tous dossiers et utilisateurs)
$sql_all = "
SELECT a.id AS temps_id, d.code_dossier AS code, d.nom_client AS dossier_nom, d.deadline, d.date_debut, d.date_fin,
       a.commentaires,
       u.nom AS collaborateur, u.role, d.id AS dossier_id
FROM affectation a
JOIN utilisateurs u ON a.utilisateur_id = u.id
JOIN dossiers d ON a.dossier_id = d.id
ORDER BY d.deadline, u.nom
";

$planning_all = $pdo->query($sql_all)->fetchAll();

// Planning de l'utilisateur connecté (seulement dossiers assignés à lui)
$sql_user = "
SELECT a.id AS temps_id, d.code_dossier AS code, d.nom_client AS dossier_nom, d.deadline, d.date_debut, d.date_fin,
       a.commentaires
FROM affectation a
JOIN dossiers d ON a.dossier_id = d.id
WHERE a.utilisateur_id = ?
ORDER BY d.date_debut, d.deadline
";

$stmt = $pdo->prepare($sql_user);
$stmt->execute([$user_id]);
$planning_user = $stmt->fetchAll();

// Dossiers distincts assignés à l'utilisateur
$sql_dossiers_user = "
SELECT DISTINCT d.id, d.code_dossier AS code, d.nom_client AS nom, d.deadline, d.date_debut, d.date_fin, d.statut
FROM dossiers d
JOIN affectation a ON a.dossier_id = d.id
WHERE a.utilisateur_id = ?
ORDER BY d.date_debut
";

$stmt2 = $pdo->prepare($sql_dossiers_user);
$stmt2->execute([$user_id]);
$mes_dossiers = $stmt2->fetchAll();

// Construction des événements calendrier
$events = [];
foreach ($planning_user as $row) {
    if (!empty($row['date_debut']) && !empty($row['date_fin'])) {
        $color = (isset($row['retard_declared']) && $row['retard_declared']) 
         ? '#d9534f' 
         : ((isset($row['valide']) && $row['valide']) 
            ? '#28a745' 
            : '#ffc107');

        $events[] = [
            'title' => $row['code'] . ' - ' . extractClientName($row['dossier_nom']),
            'start' => $row['date_debut'],
            'end' => $row['date_fin'],
            'color' => $color,
        ];
    }
}
$events_json = json_encode($events);

// Dossier en cours et prochain dossier
$current_dossier = null;
$next_dossier = null;
$today_dt = new DateTime($today);
foreach ($planning_user as $row) {
    $date_debut = new DateTime($row['date_debut']);
    $date_fin = new DateTime($row['date_fin']);
    // On utilise l'opérateur null coalescent pour éviter le warning
    $valide = $row['valide'] ?? false;
    if ($date_debut <= $today_dt && $date_fin >= $today_dt && !$valide) {
        $current_dossier = $row;
        break;
    }
}

foreach ($planning_user as $row) {
    $date_debut = new DateTime($row['date_debut']);
    if ($date_debut > $today_dt) {
        $next_dossier = $row;
        break;
    }
}
// Nombre dossiers retard non validés

$retard_count = 0;
foreach ($planning_user as $row) {
    if (($row['statut'] ?? '') === 'en retard') $retard_count++;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard MG EXPERTISE</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- FullCalendar -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

  <!-- Google Fonts Montserrat (clean, professional) -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background-color: #f5f8fa;
      color: #222;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      background-color: #003366;
      color: white;
      padding: 1rem 2rem;
      border-radius: 0 0 0.5rem 0.5rem;
      box-shadow: 0 2px 8px rgb(0 0 0 / 0.15);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    header h1 {
      font-weight: 700;
      font-size: 1.5rem;
      margin: 0;
      flex-grow: 1;
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .user-info img {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 50%;
      box-shadow: 0 0 8px rgba(255 255 255 / 0.8);
      border: 2px solid #007BFF;
    }
    .user-details h5, .user-details small {
      margin: 0;
      line-height: 1.1;
    }
    .user-details h5 {
      font-weight: 600;
    }
    a.logout-btn {
      background-color: #007BFF;
      color: white;
      padding: 0.4rem 1rem;
      border-radius: 0.4rem;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    a.logout-btn:hover {
      background-color: #0056b3;
      text-decoration: none;
      color: white;
    }

    main.container {
      flex-grow: 1;
      max-width: 1100px;
      margin: 2rem auto;
    }

    /* Info rapide section */
    section.quick-info {
      background: white;
      border-radius: 0.5rem;
      padding: 1.5rem;
      box-shadow: 0 0 15px rgb(0 115 230 / 0.15);
      margin-bottom: 2rem;
      font-size: 1.1rem;
      color: #222;
    }
    section.quick-info h4 {
      font-weight: 700;
      margin-bottom: 1rem;
      color: #003366;
    }
    section.quick-info p {
      margin-bottom: 0.4rem;
    }
    section.quick-info p em {
      color: #666;
    }
    section.quick-info strong {
      color: #007BFF;
    }

    /* Tabs */
    ul.nav-tabs {
      border-bottom: 2px solid #003366;
      margin-bottom: 1rem;
    }
    ul.nav-tabs .nav-link {
      color: #003366;
      font-weight: 600;
      border: none;
      padding: 0.75rem 1.2rem;
      border-radius: 0.5rem 0.5rem 0 0;
      background-color: #e6f0ff;
      transition: background-color 0.3s ease;
    }
    ul.nav-tabs .nav-link.active {
      background-color: white;
      border: 2px solid #003366;
      border-bottom-color: white;
      color: #003366;
    }
    ul.nav-tabs .nav-link:hover:not(.active) {
      background-color: #cce0ff;
      color: #002244;
    }

    /* Search inputs */
    .search-input {
      max-width: 360px;
      margin: 0 0 1rem 0;
      border-radius: 1.5rem;
      padding-left: 2.5rem;
      box-shadow: inset 1px 1px 4px rgb(0 0 0 / 0.1);
      transition: box-shadow 0.3s ease;
      position: relative;
    }
    .search-input:focus {
      box-shadow: 0 0 8px #007BFF;
      outline: none;
      border-color: #007BFF;
    }
    /* Icon loupe dans la search */
    .search-wrapper {
      position: relative;
      max-width: 360px;
      margin-bottom: 1rem;
    }
    .search-wrapper svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      fill: #007BFF;
      width: 18px;
      height: 18px;
      pointer-events: none;
      opacity: 0.7;
    }

    /* Tables */
    table {
      background: white;
      border-radius: 0.5rem;
      overflow: hidden;
      box-shadow: 0 0 15px rgb(0 0 0 / 0.05);
      border-collapse: separate !important;
      border-spacing: 0;
      font-size: 0.9rem;
    }
    thead tr {
      background-color: #003366;
      color: white;
      text-align: left;
      font-weight: 700;
    }
    tbody tr {
      transition: background-color 0.25s ease;
    }
    tbody tr:hover {
      background-color: #e6f0ff;
      cursor: pointer;
    }
    tbody tr.table-danger {
      background-color: #f8d7da !important;
      color: #842029;
    }
    tbody tr.table-success {
      background-color: #d1e7dd !important;
      color: #0f5132;
    }
    tbody tr.table-warning {
      background-color: #fff3cd !important;
      color: #664d03;
    }
    td, th {
      padding: 0.6rem 1rem;
      vertical-align: middle;
    }
    textarea.form-control {
      font-size: 0.9rem;
      resize: vertical;
      min-height: 48px;
    }
    button.btn-sm {
      min-width: 75px;
      margin: 0 0.1rem 0.1rem 0;
    }

    /* Cards dossiers */
    .card-dossier {
      cursor: pointer;
      transition: box-shadow 0.3s ease-in-out;
      border-radius: 0.6rem;
      box-shadow: 0 3px 8px rgb(0 0 0 / 0.07);
      background: white;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .card-dossier:hover {
      box-shadow: 0 0 20px #007BFFaa;
      border-color: #007BFF;
    }
    .card-dossier .card-body p {
      margin-bottom: 0.3rem;
      font-size: 0.9rem;
      color: #333;
    }
    .card-dossier .card-title {
      font-weight: 700;
      font-size: 1.2rem;
      color: #003366;
    }

    /* Calendar */
    #calendar {
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 0 15px rgb(0 0 0 / 0.1);
      padding: 1rem;
      max-width: 900px;
      margin: auto;
    }

    /* Modal */
    .modal-content {
      border-radius: 0.5rem;
      font-size: 0.95rem;
    }
    .modal-header {
      background-color: #003366;
      color: white;
      border-bottom: none;
    }
    .modal-footer button.btn-primary {
      background-color: #007BFF;
      border-color: #007BFF;
    }
    .modal-footer button.btn-primary:hover {
      background-color: #0056b3;
      border-color: #0056b3;
    }

    /* Responsive tweaks */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        gap: 0.5rem;
      }
      main.container {
        margin: 1rem 1rem 2rem;
      }
      .search-input, .search-wrapper {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
<header>
  <h1>MG EXPERTISE - Planning Collaborateurs</h1>
  <div class="user-info">
    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_nom) ?>&background=007BFF&color=fff&rounded=true" alt="Avatar <?= htmlspecialchars($user_nom) ?>" />
    <div class="user-details">
      <h5><?= htmlspecialchars($user_nom) ?></h5>
      <small><?= htmlspecialchars($user_role) ?></small>
    </div>
    <a href="logout.php" class="logout-btn">Déconnexion</a>
  </div>
</header>

<main class="container">

  <section class="quick-info" aria-label="Informations rapides">
    <h4>Informations rapides au <?= htmlspecialchars($today) ?></h4>
    <?php if ($current_dossier): ?>
        <p><strong>Dossier en cours :</strong> <?= htmlspecialchars($current_dossier['code'] . ' - ' . extractClientName($current_dossier['dossier_nom'])) ?> (<?= htmlspecialchars($current_dossier['date_debut']) ?> → <?= htmlspecialchars($current_dossier['date_fin']) ?>)</p>
    <?php else: ?>
        <p><em>Aucun dossier en cours.</em></p>
    <?php endif; ?>
    <?php if ($next_dossier): ?>
        <p><strong>Prochain dossier :</strong> <?= htmlspecialchars($next_dossier['code'] . ' - ' . extractClientName($next_dossier['dossier_nom'])) ?> (Début le <?= htmlspecialchars($next_dossier['date_debut']) ?>)</p>
    <?php else: ?>
        <p><em>Aucun prochain dossier planifié.</em></p>
    <?php endif; ?>
    <p><strong>Dossiers en retard non validés :</strong> <?= $retard_count ?></p>
  </section>

  <ul class="nav nav-tabs" id="myTab" role="tablist" aria-label="Onglets de navigation">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#view-global" type="button" role="tab" aria-controls="view-global" aria-selected="true">Planning global</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="perso-tab" data-bs-toggle="tab" data-bs-target="#view-perso" type="button" role="tab" aria-controls="view-perso" aria-selected="false">Mon planning</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="dossiers-tab" data-bs-toggle="tab" data-bs-target="#view-dossiers" type="button" role="tab" aria-controls="view-dossiers" aria-selected="false">Mes dossiers</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="calendrier-tab" data-bs-toggle="tab" data-bs-target="#view-calendrier" type="button" role="tab" aria-controls="view-calendrier" aria-selected="false">Calendrier</button></li>
  </ul>

  <div class="tab-content" id="myTabContent">

    <div class="tab-pane fade show active" id="view-global" role="tabpanel" aria-labelledby="global-tab" tabindex="0">
      <div class="search-wrapper">
        <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
        <input type="text" id="searchGlobal" class="form-control search-input" placeholder="Rechercher dans planning global..." aria-label="Rechercher dans planning global" />
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="tableGlobal" aria-describedby="tableGlobalDesc" role="table">
          <caption id="tableGlobalDesc" class="visually-hidden">Tableau des dossiers et collaborateurs</caption>
          <thead>
            <tr><th>Code</th><th>Client</th><th>Collaborateur</th><th>Rôle</th><th>Début</th><th>Fin</th></tr>
          </thead>
          <tbody>
          <?php foreach($planning_all as $row): 
  $rowClass = '';
  if (isset($row['retard_declared'], $row['valide'])) {
    if ($row['retard_declared'] && !$row['valide']) $rowClass = 'table-danger';
    else if ($row['valide']) $rowClass = 'table-success';
  }
?>

            <tr class="<?= $rowClass ?>">
              <td><?= htmlspecialchars($row['code']) ?></td>
              <td><?= htmlspecialchars(extractClientName($row['dossier_nom'])) ?></td>
              <td><?= htmlspecialchars($row['collaborateur']) ?></td>
              <td><?= htmlspecialchars($row['role']) ?></td>
              <td><?= htmlspecialchars($row['date_debut'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['date_fin'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="view-perso" role="tabpanel" aria-labelledby="perso-tab" tabindex="0">
      <div class="search-wrapper">
        <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
        <input type="text" id="searchPerso" class="form-control search-input" placeholder="Rechercher dans mon planning..." aria-label="Rechercher dans mon planning" />
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="tablePerso" aria-describedby="tablePersoDesc" role="table">
          <caption id="tablePersoDesc" class="visually-hidden">Tableau de mon planning</caption>
          <thead>
            <tr><th>Code</th><th>Client</th><th>Début</th><th>Fin</th><th>Commentaires</th><th>Actions</th></tr>
          </thead>
          <tbody>
<?php foreach($planning_user as $row): 
    $rowClass = '';
    if (($row['statut'] ?? '') === 'en retard') {
        $rowClass = 'table-danger';
    } else if (!empty($row['valide'])) {
        $rowClass = 'table-success';
    }
?>
<tr class="<?= $rowClass ?>">
  <td><?= htmlspecialchars($row['code']) ?></td>
  <td><?= htmlspecialchars(extractClientName($row['dossier_nom'])) ?></td>
  <td><?= htmlspecialchars($row['date_debut'] ?? '-') ?></td>
  <td><?= htmlspecialchars($row['date_fin'] ?? '-') ?></td>
  <td><textarea class="form-control" data-id="<?= $row['temps_id'] ?>" aria-label="Commentaires pour <?= htmlspecialchars($row['code']) ?>"><?= htmlspecialchars($row['commentaires']) ?></textarea></td>
  <td>
    <!-- Bouton validation -->
    <?php if (!empty($row['valide'])): ?>
      <button type="button" class="btn btn-sm btn-secondary" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleValid')" title="Annuler validation">Annuler validation</button>
    <?php else: ?>
      <button type="button" class="btn btn-sm btn-success" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleValid')" title="Valider">Valide</button>
    <?php endif; ?>

    <!-- Bouton retard / relancer -->
    <?php if (($row['statut'] ?? '') === 'en retard'): ?>
      <button type="button" class="btn btn-sm btn-warning" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleRetard')" title="Relancer">Relancer</button>
      <button type="button" class="btn btn-sm btn-primary" onclick="openEditModal(<?= $row['temps_id'] ?>, '<?= htmlspecialchars($row['code']) ?>', '<?= $row['date_fin'] ?>')" title="Modifier date de fin">Modifier fin</button>
    <?php else: ?>
      <button type="button" class="btn btn-sm btn-outline-warning" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleRetard')" title="Marquer retard">Marquer retard</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="view-dossiers" role="tabpanel" aria-labelledby="dossiers-tab" tabindex="0">
      <div class="search-wrapper">
        <svg viewBox="0 0 24 24"><path d="M21.71 20.29l-3.388-3.388A7.936 7.936 0 0 0 18 10a8 8 0 1 0-8 8 7.936 7.936 0 0 0 6.902-3.678l3.388 3.388a1 1 0 0 0 1.414-1.414zM4 10a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z"/></svg>
        <input type="text" id="searchDossiers" class="form-control search-input" placeholder="Rechercher dans mes dossiers..." aria-label="Rechercher dans mes dossiers" />
      </div>
      <div class="row row-cols-1 row-cols-md-2 g-4 mt-2" id="dossiersContainer">
        <?php foreach($mes_dossiers as $row):
          //$stmtEnc = $pdo->prepare("SELECT u.nom, u.role FROM temps t JOIN utilisateurs u ON u.id = t.utilisateur_id WHERE t.dossier_id = ?");
          $stmtEnc = $pdo->prepare("SELECT u.nom, u.role FROM affectation a JOIN utilisateurs u ON u.id = a.utilisateur_id WHERE a.dossier_id = ?");

          $stmtEnc->execute([$row['id']]);
          $encadrants = $stmtEnc->fetchAll();

          $chefs = array_filter($encadrants, function($u) {
            return strtolower(trim($u['role'])) === 'chef';
          });
          $collabs = array_filter($encadrants, function($u) {
            return strtolower(trim($u['role'])) === 'collaborateur';
          });
        ?>
          <div class="col">
            <div class="card card-dossier shadow-sm" tabindex="0" role="article" aria-label="Dossier <?= htmlspecialchars($row['code']) ?>">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($row['code']) ?></h5>
                <p><strong>Client :</strong> <?= htmlspecialchars(extractClientName($row['nom'])) ?></p>
                <p><strong>Début :</strong> <?= htmlspecialchars($row['date_debut']) ?></p>
                <p><strong>Fin :</strong> <?= htmlspecialchars($row['date_fin']) ?></p>
                <p><strong>Statut :</strong> <?= htmlspecialchars($row['statut']) ?></p>
                <p><strong>Chefs :</strong> <?= implode(', ', array_column($chefs, 'nom')) ?: 'Aucun' ?></p>
                <p><strong>Collaborateurs :</strong> <?= implode(', ', array_column($collabs, 'nom')) ?: 'Aucun' ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="tab-pane fade" id="view-calendrier" role="tabpanel" aria-labelledby="calendrier-tab" tabindex="0">
      <div id="calendar" aria-label="Calendrier des dossiers"></div>
    </div>

  </div>

</main>

<!-- Modal pour modifier date fin -->
<div class="modal fade" id="editDateModal" tabindex="-1" aria-labelledby="editDateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editDateForm" novalidate>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDateModalLabel">Modifier date de fin</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="tempsId" name="temps_id" />
          <p>Dossier : <span id="modalDossierCode" class="fw-bold"></span></p>
          <label for="newDateFin" class="form-label">Nouvelle date de fin :</label>
          <input type="date" id="newDateFin" name="new_date_fin" class="form-control" required />
          <div class="invalid-feedback">Veuillez choisir une date valide.</div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Enregistrer</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  // Gestion des actions boutons
  function sendAction(id, action, commentaires = '') {
  const data = new URLSearchParams();
  data.append('id_temps', id);
  data.append('action', action);
  data.append('commentaires', commentaires);
  
  fetch('actions.php', { method: 'POST', body: data })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then(res => {
      if (!res.success) {
        alert("Erreur: " + res.message);
      } else {
        location.reload();
      }
    })
    .catch(error => {
      alert('Erreur réseau ou serveur : ' + error.message);
      console.error('Erreur fetch:', error);
    });
}

  // Sauvegarde commentaires en changeant la textarea
  document.querySelectorAll('textarea[data-id]').forEach(textarea => {
    textarea.addEventListener('change', () => sendAction(textarea.dataset.id, 'updateComment', textarea.value));
  });

  // FullCalendar
  document.addEventListener('DOMContentLoaded', function () {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
      initialView: 'dayGridMonth',
      initialDate: '<?= $today ?>',
      events: <?= json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
      height: 650,
      themeSystem: 'bootstrap5',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      buttonText: {
        today: 'Aujourd\'hui',
        month: 'Mois',
        week: 'Semaine',
        day: 'Jour'
      }
    });
    calendar.render();
  });

  // Recherche dans tables
  document.getElementById('searchGlobal').addEventListener('input', () => filterTable('searchGlobal', 'tableGlobal'));
  document.getElementById('searchPerso').addEventListener('input', () => filterTable('searchPerso', 'tablePerso'));
  document.getElementById('searchDossiers').addEventListener('input', () => filterCards('searchDossiers', 'dossiersContainer'));

  function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const rows = document.getElementById(tableId).getElementsByTagName("tbody")[0].getElementsByTagName("tr");
    for (let i = 0; i < rows.length; i++) {
      const text = rows[i].textContent.toLowerCase();
      rows[i].style.display = text.includes(filter) ? "" : "none";
    }
  }

  function filterCards(inputId, containerId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const cards = document.getElementById(containerId).getElementsByClassName("card-dossier");
    for (let card of cards) {
      const text = card.textContent.toLowerCase();
      card.parentElement.style.display = text.includes(filter) ? "" : "none";
    }
  }

  // Modal modification date fin
  const editModalEl = document.getElementById('editDateModal');
  const editModal = new bootstrap.Modal(editModalEl);

  function openEditModal(id, code, currentFin) {
    document.getElementById('tempsId').value = id;
    document.getElementById('modalDossierCode').textContent = code;
    document.getElementById('newDateFin').value = currentFin;
    document.getElementById('newDateFin').classList.remove('is-invalid');
    editModal.show();
  }

  document.getElementById('editDateForm').addEventListener('submit', e => {
    e.preventDefault();
    const inputDate = document.getElementById('newDateFin');
    if (!inputDate.value) {
      inputDate.classList.add('is-invalid');
      return;
    } else {
      inputDate.classList.remove('is-invalid');
    }

    const formData = new FormData(e.target);
    fetch('update_fin_dossier.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) location.reload();
      else alert('Erreur: ' + data.message);
    })
    .catch(() => alert('Erreur réseau.'));
  });
</script>

</body>
</html>
