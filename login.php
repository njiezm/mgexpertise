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

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(to right, #003366, #005599);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,0,0,0.05);
      animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-title {
      font-size: 1.8rem;
      color: #003366;
      font-weight: 700;
      text-align: center;
      margin-bottom: 10px;
    }

    .login-subtitle {
      font-size: 0.95rem;
      color: #666;
      text-align: center;
      margin-bottom: 20px;
    }

    .form-label {
      font-weight: 600;
      color: #003366;
    }

    .form-control:focus {
      border-color: #007BFF;
      box-shadow: 0 0 5px rgba(0,123,255,0.3);
    }

    .btn-primary {
      background-color: #003366;
      border: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #005599;
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
  <div class="login-subtitle">Connexion à l'espace sécurisé</div>

  <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off" aria-label="Formulaire de connexion">
    <div class="mb-3">
      <label for="email" class="form-label">Adresse e-mail</label>
      <input type="email" class="form-control" id="email" name="email" required autocomplete="username" autofocus />
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Mot de passe</label>
      <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" />
    </div>

    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
  </form>

  <div class="footer">
    © <?= date('Y') ?> MG EXPERTISE — Tous droits réservés.
  </div>

</div>

</body>
</html>
