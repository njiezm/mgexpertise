<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';

$user_nom = $_SESSION['user_nom'];
$today = date('Y-m-d');

$users = $pdo->query("SELECT id, nom, email, role, actif FROM utilisateurs ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - MG EXPERTISE</title>

  <!-- Google Fonts Montserrat -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background-color: #f9fafd;
      color: #003366;
    }
    header {
      background-color: #003366;
      color: #fff;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      border-radius: 0.25rem;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }
    header h1 {
      font-weight: 700;
      font-size: 1.8rem;
      margin: 0;
      flex-grow: 1;
      min-width: 220px;
    }
    header .user-info {
      font-size: 1rem;
    }
    header .user-info strong {
      color: #ffdd57;
    }
    header a.logout {
      color: #ffdd57;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    header a.logout:hover {
      color: #ffd633;
      text-decoration: underline;
    }

    .nav-tabs .nav-link.active {
      background-color: #007BFF;
      color: #fff;
      border-color: #007BFF #007BFF #fff;
    }
    .nav-tabs .nav-link {
      color: #003366;
      font-weight: 600;
    }
    .nav-tabs {
      border-bottom: 2px solid #007BFF;
      margin-bottom: 1.5rem;
    }

    .card {
      border-radius: 0.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    label {
      font-weight: 600;
      color: #003366;
    }
    input.form-control, select.form-select {
      border: 1.5px solid #007BFF;
      border-radius: 0.375rem;
      transition: border-color 0.3s ease;
    }
    input.form-control:focus, select.form-select:focus {
      border-color: #0056b3;
      box-shadow: 0 0 5px rgba(0,123,255,0.5);
    }

    button.btn-primary {
      background-color: #007BFF;
      border-color: #007BFF;
      font-weight: 700;
      transition: background-color 0.3s ease;
    }
    button.btn-primary:hover {
      background-color: #0056b3;
      border-color: #004085;
    }

    /* Table styling */
    table.table {
      background: white;
      border-radius: 0.5rem;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    }
    table thead {
      background-color: #007BFF;
      color: white;
    }
    table tbody tr:hover {
      background-color: #e6f0ff;
    }
    .btn-sm {
      font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 575.98px) {
      header {
        flex-direction: column;
        align-items: flex-start;
      }
      header h1 {
        font-size: 1.4rem;
      }
    }
  </style>

</head>
<body>

<header role="banner" aria-label="Entête de la page">
  <h1>MG EXPERTISE - Espace Administrateur</h1>
  <div class="user-info" role="contentinfo">
    Bienvenue, <strong><?= htmlspecialchars($user_nom) ?></strong> (Administrateur) &nbsp;|&nbsp;
    <a href="logout.php" class="logout" aria-label="Déconnexion">Déconnexion</a>
  </div>
</header>

<main class="container" role="main">

  <ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-account" type="button" role="tab" aria-controls="create-account" aria-selected="true">Création de compte</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage-account" type="button" role="tab" aria-controls="manage-account" aria-selected="false">Activation / Désactivation</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="false">Statistiques</button>
    </li>
  </ul>

  <div class="tab-content" id="adminTabContent">

    <section class="tab-pane fade show active p-4 bg-white rounded shadow-sm" id="create-account" role="tabpanel" aria-labelledby="create-tab" tabindex="0">
      <h2 class="h5 mb-4 text-primary">Créer un nouvel utilisateur</h2>
      <form action="admin_create_user.php" method="POST" novalidate>
        <div class="row g-3 mb-3">
          <div class="col-md">
            <label for="nom" class="form-label">Nom</label>
            <input id="nom" name="nom" type="text" class="form-control" required autocomplete="family-name" />
          </div>
          <div class="col-md">
            <label for="prenom" class="form-label">Prénom</label>
            <input id="prenom" name="prenom" type="text" class="form-control" required autocomplete="given-name" />
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control" required autocomplete="email" />
          </div>
          <div class="col-md">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input id="mot_de_passe" name="mot_de_passe" type="password" class="form-control" required autocomplete="new-password" />
          </div>
        </div>
        <div class="mb-3">
          <label for="role" class="form-label">Rôle</label>
          <select id="role" name="role" class="form-select" required>
            <option value="collaborateur">Collaborateur</option>
            <option value="chef">Chef</option>
            <option value="associe">Associé</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
      </form>
    </section>

    <section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="manage-account" role="tabpanel" aria-labelledby="manage-tab" tabindex="0">
      <h2 class="h5 mb-4 text-primary">Gestion des utilisateurs</h2>
      <div class="table-responsive">
        <table class="table" aria-describedby="userTableDesc" role="table">
          <caption id="userTableDesc" class="visually-hidden">Liste des utilisateurs avec leurs statuts et actions</caption>
          <thead class="table-primary">
            <tr>
              <th scope="col">Nom</th>
              <th scope="col">Email</th>
              <th scope="col">Rôle</th>
              <th scope="col">Statut</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td>
                  <?php if ($u['actif']): ?>
                    <span class="text-success fw-semibold">Actif</span>
                  <?php else: ?>
                    <span class="text-danger fw-semibold">Inactif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form action="admin_toggle_user.php" method="POST" class="d-inline" aria-label="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?> l'utilisateur <?= htmlspecialchars($u['nom']) ?>">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm <?= $u['actif'] ? 'btn-danger' : 'btn-success' ?>">
                      <?= $u['actif'] ? 'Désactiver' : 'Activer' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="stats" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">
      <h2 class="h5 mb-3 text-primary">Statistiques</h2>
      <p>Module à venir : suivi du nombre de dossiers, utilisateurs actifs, répartition des rôles, etc.</p>
    </section>

  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
