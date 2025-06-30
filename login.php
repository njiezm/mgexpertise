<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $mot_de_passe === $user['mot_de_passe']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - MG EXPERTISE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 30px;
        }

        .login-title {
            font-size: 1.8rem;
            color: #003366;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #003366;
            border-color: #003366;
        }

        .btn-primary:hover {
            background-color: #005599;
            border-color: #005599;
        }

        .form-label {
            font-weight: 500;
        }

        .error-msg {
            color: #d9534f;
            text-align: center;
            margin-bottom: 15px;
        }

        .footer {
            text-align: center;
            font-size: 0.8rem;
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-title">MG EXPERTISE</div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    </form>

    <div class="footer mt-3">
        © <?= date('Y') ?> MG EXPERTISE. Tous droits réservés.
    </div>
</div>

</body>
</html>
