<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: /admin/index.php');
    exit();
}

require_once __DIR__ . '/../db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare("SELECT * FROM administrateurs WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin/index.php');
        exit();
    }
    $error = "Nom d'utilisateur ou mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — Park DTTS</title>
    <link rel="stylesheet" href="/admin/styles.css">
</head>
<body>
<div class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <span class="icon">🔐</span>
            <h1>Park DTTS</h1>
            <p>Espace administrateur</p>
        </div>
        <form method="POST" action="/admin/login.php">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" autocomplete="username" required>
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="submit">Se connecter</button>
            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>
