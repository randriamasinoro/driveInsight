<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation — Park DTTS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="site-nav">
    <a href="/" class="nav-brand">
        <img src="images/logo.jpeg" alt="Park DTTS">
        <span class="name">Park <span>DTTS</span></span>
    </a>
</nav>

<div class="page-wrapper">
    <div class="card">
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="confirm-box"><div class="icon">🚫</div><h1 style="color:var(--danger)">Accès refusé</h1><p>Cette page n\'est pas accessible directement.</p><a href="/" class="btn-home">Retour à l\'accueil</a></div>';
} else {
    require_once __DIR__ . '/db.php';

    $id_voiture = intval($_POST['id_voiture']);
    $dateDebut  = $_POST['dateDebut'];
    $dateFin    = $_POST['dateFin'];
    $nom        = $_POST['nom']       ?? '';
    $prenom     = $_POST['prenom']    ?? '';
    $tel        = $_POST['telephone'] ?? '';
    $email      = $_POST['email']     ?? '';

    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if ($client) {
        $id_client = $client['id'];
    } else {
        $pdo->prepare("INSERT INTO clients (nom, prenom, telephone, email) VALUES (?, ?, ?, ?)")
            ->execute([$nom, $prenom, $tel, $email]);
        $id_client = $pdo->lastInsertId();
    }

    $ok = $pdo->prepare("INSERT INTO reservations (id_voiture, id_client, date_debut, date_fin) VALUES (?, ?, ?, ?)")
              ->execute([$id_voiture, $id_client, $dateDebut, $dateFin]);

    if ($ok) {
        $stmt = $pdo->prepare("SELECT nom FROM voitures WHERE id = ?");
        $stmt->execute([$id_voiture]);
        $nomVoiture = $stmt->fetchColumn();
        ?>
        <div class="confirm-box">
            <div class="icon">🎉</div>
            <h1>Réservation confirmée !</h1>
            <p>Merci <strong><?= htmlspecialchars($prenom . ' ' . $nom) ?></strong>, votre réservation pour la <strong><?= htmlspecialchars($nomVoiture) ?></strong> du <strong><?= htmlspecialchars($dateDebut) ?></strong> au <strong><?= htmlspecialchars($dateFin) ?></strong> a bien été enregistrée.</p>
            <a href="/" class="btn-home">Retour à l'accueil</a>
        </div>
        <?php
    } else {
        echo '<div class="confirm-box"><div class="icon">❌</div><h1 style="color:var(--danger)">Erreur</h1><p>Une erreur est survenue lors de la réservation.</p><a href="/" class="btn-home">Réessayer</a></div>';
    }
}
?>
    </div>
</div>
</body>
</html>
