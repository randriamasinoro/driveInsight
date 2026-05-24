<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voitures Disponibles — Park DTTS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="site-nav">
    <a href="/" class="nav-brand">
        <img src="images/logo.jpeg" alt="Park DTTS">
        <span class="name">Park <span>DTTS</span></span>
    </a>
    <div class="nav-links">
        <a href="/">← Retour</a>
        <a href="/admin/login.php" class="btn-admin">Admin</a>
    </div>
</nav>

<div class="page-wrapper" style="max-width:1100px">
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['dateDebut']) || empty($_POST['dateFin'])) {
    echo '<div class="card"><p>Paramètres manquants. <a href="/" style="color:var(--accent)">Retour à l\'accueil</a></p></div>';
} else {
    require_once __DIR__ . '/db.php';

    $dateDebut = $_POST['dateDebut'];
    $dateFin   = $_POST['dateFin'];

    $stmt = getDB()->prepare(
        "SELECT * FROM voitures WHERE id NOT IN (
            SELECT id_voiture FROM reservations
            WHERE (? < date_fin AND ? > date_debut)
        )"
    );
    $stmt->execute([$dateDebut, $dateFin]);
    $voitures = $stmt->fetchAll();

    echo '<h1>Voitures disponibles</h1>';
    echo '<p style="color:var(--muted);margin-bottom:24px">Du <strong>' . htmlspecialchars($dateDebut) . '</strong> au <strong>' . htmlspecialchars($dateFin) . '</strong></p>';

    if (count($voitures) > 0) {
        echo "<div class='car-list'>";
        foreach ($voitures as $row) {
            echo "<div class='car-item'>
                    <a href='details_voiture.php?id=" . $row['id'] . "&dateDebut=" . urlencode($dateDebut) . "&dateFin=" . urlencode($dateFin) . "'>
                        <img src='" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['nom']) . "'>
                        <div class='car-info'>
                            <h3>" . htmlspecialchars($row['nom']) . "</h3>
                            <span class='price'>" . number_format($row['prix_jour'], 0, ',', ' ') . " Ar/jour</span>
                        </div>
                    </a>
                  </div>";
        }
        echo "</div>";
    } else {
        echo '<div class="card" style="text-align:center;padding:48px">
                <div style="font-size:3rem;margin-bottom:16px">😔</div>
                <h2>Aucune voiture disponible</h2>
                <p style="color:var(--muted);margin:12px 0 24px">Essayez d\'autres dates.</p>
                <a href="/" class="btn" style="display:inline-block;margin:0">Changer les dates</a>
              </div>';
    }
}
?>
</div>
</body>
</html>
