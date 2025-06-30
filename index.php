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

// Date fictive (à remplacer par date('Y-m-d') quand prêt)
$today = '2025-01-15';

// Fonction pour extraire le nom client sans le code devant
function extractClientName(string $nomDossier): string {
    $parts = explode(' - ', $nomDossier, 2);
    return count($parts) === 2 ? trim($parts[1]) : $nomDossier;
}

// --- Planning global ---
$sql_all = "
SELECT t.id AS temps_id, d.code, d.nom AS dossier_nom, d.deadline, d.date_debut, d.date_fin,
       t.temps_2023, t.estimation_2025, t.valide, t.retard_declared, t.commentaires,
       u.nom AS collaborateur, u.role
FROM temps t
JOIN utilisateurs u ON t.utilisateur_id = u.id
JOIN dossiers d ON t.dossier_id = d.id
ORDER BY d.deadline, u.nom
";
$planning_all = $pdo->query($sql_all)->fetchAll();

// --- Planning perso ---
$sql_user = "
SELECT t.id AS temps_id, d.code, d.nom AS dossier_nom, d.deadline, d.date_debut, d.date_fin,
       t.temps_2023, t.estimation_2025, t.valide, t.retard_declared, t.commentaires
FROM temps t
JOIN dossiers d ON t.dossier_id = d.id
WHERE t.utilisateur_id = ?
ORDER BY d.date_debut, d.deadline
";
$stmt = $pdo->prepare($sql_user);
$stmt->execute([$user_id]);
$planning_user = $stmt->fetchAll();

// --- Mes dossiers (liste unique des dossiers de l'utilisateur) ---
$sql_dossiers_user = "
SELECT DISTINCT d.id, d.code, d.nom, d.deadline, d.date_debut, d.date_fin, d.statut
FROM dossiers d
JOIN temps t ON t.dossier_id = d.id
WHERE t.utilisateur_id = ?
ORDER BY d.date_debut
";
$stmt2 = $pdo->prepare($sql_dossiers_user);
$stmt2->execute([$user_id]);
$mes_dossiers = $stmt2->fetchAll();

// --- Événements FullCalendar (planning perso) ---
$events = [];
foreach ($planning_user as $row) {
    if (!empty($row['date_debut']) && !empty($row['date_fin'])) {
        $color = $row['retard_declared'] ? '#d9534f' : ($row['valide'] ? '#5cb85c' : '#f0ad4e');
        $events[] = [
            'title' => $row['code'] . ' - ' . extractClientName($row['dossier_nom']),
            'start' => $row['date_debut'],
            // FullCalendar exclut la date de fin donc on ajoute 1 jour
            'end' => date('Y-m-d', strtotime($row['date_fin'] . ' +1 day')),
            'color' => $color
        ];
    }
}
$events_json = json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// --- Calcul infos utilisateur pour affichage en haut ---
// Dossier en cours : date_debut <= today <= date_fin ET pas validé
$current_dossier = null;
$next_dossier = null;
$today_dt = new DateTime($today);
foreach ($planning_user as $row) {
    $date_debut = new DateTime($row['date_debut']);
    $date_fin = new DateTime($row['date_fin']);
    if ($date_debut <= $today_dt && $date_fin >= $today_dt && !$row['valide']) {
        $current_dossier = $row;
        break;
    }
}
// Dossier suivant : date_debut > today, premier trouvé (trié par date_debut)
foreach ($planning_user as $row) {
    $date_debut = new DateTime($row['date_debut']);
    if ($date_debut > $today_dt) {
        $next_dossier = $row;
        break;
    }
}
// Nombre dossiers en retard
$retard_count = 0;
foreach ($planning_user as $row) {
    if ($row['retard_declared'] && !$row['valide']) $retard_count++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard MG EXPERTISE</title>

<!-- Bootstrap 5 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- FullCalendar v5 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<!-- CSS personnalisé -->
<style>
#calendar {
  max-width: 900px;
  margin: 0 auto;
}
textarea {
    resize: vertical;
    min-height: 40px;
}
.card-dossier {
    cursor: pointer;
    transition: box-shadow 0.3s ease-in-out;
}
.card-dossier:hover {
    box-shadow: 0 0 15px rgba(0, 123, 255, 0.5);
}
.search-input {
    max-width: 300px;
    margin-bottom: 10px;
}
</style>
</head>
<body>

<div class="container my-4">

    <header class="mb-4">
        <h1 class="text-primary">MG EXPERTISE - Planning Collaborateurs</h1>
        <div class="d-flex align-items-center gap-3">
  <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_nom) ?>" class="rounded-circle shadow-sm" height="48" />
  <div>
    <h5 class="mb-0"><?= htmlspecialchars($user_nom) ?></h5>
    <small class="text-muted"><?= htmlspecialchars($user_role) ?></small>
  </div>
</div> <a href="logout.php">Déconnexion</a></p>
    </header>

    <!-- Infos importantes -->
    <section class="mb-4 p-3 bg-light border rounded">
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

    <!-- Onglets Bootstrap -->
    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#view-global" type="button" role="tab" aria-controls="view-global" aria-selected="true">Planning global</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="perso-tab" data-bs-toggle="tab" data-bs-target="#view-perso" type="button" role="tab" aria-controls="view-perso" aria-selected="false">Mon planning</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="dossiers-tab" data-bs-toggle="tab" data-bs-target="#view-dossiers" type="button" role="tab" aria-controls="view-dossiers" aria-selected="false">Mes dossiers</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="calendrier-tab" data-bs-toggle="tab" data-bs-target="#view-calendrier" type="button" role="tab" aria-controls="view-calendrier" aria-selected="false">Calendrier</button>
      </li>
    </ul>

    <div class="tab-content" id="myTabContent">

      <!-- Planning global -->
      <div class="tab-pane fade show active" id="view-global" role="tabpanel" aria-labelledby="global-tab" tabindex="0">

        <input type="text" id="searchGlobal" class="form-control search-input" placeholder="Rechercher dans planning global..." />

        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle" id="tableGlobal">
              <thead class="table-primary">
                  <tr>
                      <th>Code</th>
                      <th>Client</th>
                      <th>Collaborateur</th>
                      <th>Rôle</th>
                      <th>Début</th>
                      <th>Fin</th>
                  </tr>
              </thead>
              <tbody>
              <?php foreach($planning_all as $row): ?>
                  <tr class="<?= $row['retard_declared'] ? 'table-danger' : '' ?> <?= $row['valide'] ? 'table-success' : '' ?>">
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

      <!-- Mon planning -->
      <div class="tab-pane fade" id="view-perso" role="tabpanel" aria-labelledby="perso-tab" tabindex="0">

        <input type="text" id="searchPerso" class="form-control search-input" placeholder="Rechercher dans mon planning..." />

        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle" id="tablePerso">
              <thead class="table-primary">
                  <tr>
                      <th>Code</th>
                      <th>Client</th>
                      <th>Début</th>
                      <th>Fin</th>
                      <th>Commentaires</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
              <?php foreach($planning_user as $row): ?>
                  <tr class="<?= $row['retard_declared'] ? 'table-danger' : '' ?> <?= $row['valide'] ? 'table-success' : '' ?>">
                      <td><?= htmlspecialchars($row['code']) ?></td>
                      <td><?= htmlspecialchars(extractClientName($row['dossier_nom'])) ?></td>
                      <td><?= htmlspecialchars($row['date_debut'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($row['date_fin'] ?? '-') ?></td>
                      <td>
                          <textarea class="form-control" data-id="<?= $row['temps_id'] ?>"><?= htmlspecialchars($row['commentaires']) ?></textarea>
                      </td>
                      <td>
                          <button class="btn btn-sm btn-success me-1" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleValid')" title="Valider / Annuler validation">Valide</button>
                          <button class="btn btn-sm btn-warning" onclick="sendAction(<?= $row['temps_id'] ?>, 'toggleRetard')" title="Déclarer / Annuler retard">En Retard</button>
                      </td>
                  </tr>
              <?php endforeach; ?>
              </tbody>
          </table>
        </div>

      </div>

      <!-- Mes dossiers -->
      <div class="tab-pane fade" id="view-dossiers" role="tabpanel" aria-labelledby="dossiers-tab" tabindex="0">
        <input type="text" id="searchDossiers" class="form-control search-input" placeholder="Rechercher dans mes dossiers..." />
        <div class="row row-cols-1 row-cols-md-3 g-3 mt-2" id="dossiersContainer">
          <?php foreach($mes_dossiers as $row): ?>
          <div class="col">
            <div class="card card-dossier shadow-sm" onclick="alert('Dossier: <?= htmlspecialchars(addslashes($row['code'])) ?>\nClient: <?= htmlspecialchars(addslashes(extractClientName($row['nom']))) ?>\nDébut: <?= htmlspecialchars($row['date_debut']) ?>\nFin: <?= htmlspecialchars($row['date_fin']) ?>\nStatut: <?= htmlspecialchars($row['statut']) ?>')">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($row['code']) ?></h5>
                <p class="card-text"><strong>Client :</strong> <?= htmlspecialchars(extractClientName($row['nom'])) ?></p>
                <p class="card-text"><strong>Début :</strong> <?= htmlspecialchars($row['date_debut'] ?? '-') ?></p>
                <p class="card-text"><strong>Fin :</strong> <?= htmlspecialchars($row['date_fin'] ?? '-') ?></p>
                <p class="card-text"><strong>Statut :</strong> <?= htmlspecialchars($row['statut']) ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Calendrier -->
      <div class="tab-pane fade" id="view-calendrier" role="tabpanel" aria-labelledby="calendrier-tab" tabindex="0">
        <div id="calendar" style="max-width: 900px; margin: 0 auto;"></div>
      </div>

    </div>
</div>

<script>
// Actions AJAX
function sendAction(id, action, commentaires = '') {
    const data = new URLSearchParams();
    data.append('id_temps', id);
    data.append('action', action);
    data.append('commentaires', commentaires);

    fetch('actions.php', {
        method: 'POST',
        body: data
    }).then(r => r.json()).then(res => {
        if (!res.success) alert("Erreur: " + res.message);
        else location.reload();
    });
}

// Envoyer commentaires à chaque changement
document.querySelectorAll('textarea[data-id]').forEach(textarea => {
    textarea.addEventListener('change', () => {
        sendAction(textarea.dataset.id, 'updateComment', textarea.value);
    });
});

// FullCalendar initialization
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: '2025-01-15',
        events: <?= $events_json ?>,
        height: 650,
        eventDisplay: 'block',
        editable: false,
        eventColor: '#378006'
    });
    calendar.render();
});

// Fonctions de recherche simples sur tableaux et cartes
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const trs = table.getElementsByTagName("tr");
    for (let i = 1; i < trs.length; i++) { // skip header row
        const tr = trs[i];
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(filter) ? "" : "none";
    }
}

function filterCards(inputId, containerId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const container = document.getElementById(containerId);
    const cards = container.getElementsByClassName("card-dossier");
    for (let card of cards) {
        const text = card.textContent.toLowerCase();
        card.parentElement.style.display = text.includes(filter) ? "" : "none";
    }
}

// Event listeners recherche
document.getElementById('searchGlobal').addEventListener('input', () => filterTable('searchGlobal', 'tableGlobal'));
document.getElementById('searchPerso').addEventListener('input', () => filterTable('searchPerso', 'tablePerso'));
document.getElementById('searchDossiers').addEventListener('input', () => filterCards('searchDossiers', 'dossiersContainer'));

</script>

</body>
</html>
