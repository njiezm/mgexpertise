<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';

$user_nom = $_SESSION['user_nom'];
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin - MG EXPERTISE</title>

<!-- Bootstrap 5 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom CSS -->
<link rel="stylesheet" href="styles.css" />
</head>
<body>

<div class="container my-4">
    <header class="mb-4">
        <h1 class="text-primary">MG EXPERTISE - Espace Administrateur</h1>
        <p>Bienvenue, <strong><?= htmlspecialchars($user_nom) ?></strong> (Administrateur) - <a href="logout.php">Déconnexion</a></p>
    </header>

    <!-- Onglets d'administration -->
    <ul class="nav nav-tabs mb-3" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-account" type="button" role="tab">Création de compte</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage-account" type="button" role="tab">Activation / Désactivation</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">Statistiques</button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabContent">
        <!-- Onglet Création de compte -->
        <div class="tab-pane fade show active" id="create-account" role="tabpanel" aria-labelledby="create-tab">
            <div class="card p-4">
                <h5 class="mb-3">Créer un nouvel utilisateur</h5>
                <form action="admin_create_user.php" method="POST">
                    <div class="row mb-3">
                        <div class="col">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>Mot de passe</label>
                            <input type="password" name="mot_de_passe" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Rôle</label>
                        <select name="role" class="form-select" required>
                            <option value="collaborateur">Collaborateur</option>
                            <option value="chef">Chef</option>
                            <option value="associe">Associé</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                </form>
            </div>
        </div>

        <!-- Onglet Gestion activation/desactivation -->
        <div class="tab-pane fade" id="manage-account" role="tabpanel" aria-labelledby="manage-tab">
            <div class="card p-4">
                <h5 class="mb-3">Gestion des utilisateurs</h5>
                <?php
                $users = $pdo->query("SELECT id, nom, email, role, actif FROM utilisateurs ORDER BY nom")->fetchAll();
                ?>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nom']) ?></td>
                            <!--td><--?= htmlspecialchars($u['prenom']) ?></td-->
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= $u['actif'] ? '<span class="text-success">Actif</span>' : '<span class="text-danger">Inactif</span>' ?></td>
                            <td>
                                <form action="admin_toggle_user.php" method="POST" class="d-inline">
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
        </div>

        <!-- Onglet Statistiques -->
        <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
            <div class="card p-4">
                <h5 class="mb-3">Statistiques</h5>
                <p>Module à venir : suivi du nombre de dossiers, utilisateurs actifs, répartition des rôles, etc.</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
