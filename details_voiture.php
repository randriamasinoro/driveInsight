<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails — Park DTTS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="site-nav">
    <a href="/" class="nav-brand">
        <img src="images/logo.jpeg" alt="Park DTTS">
        <span class="name">Park <span>DTTS</span></span>
    </a>
    <div class="nav-links">
        <a href="javascript:history.back()">← Retour</a>
        <a href="/admin/login.php" class="btn-admin">Admin</a>
    </div>
</nav>

<div class="details-voiture">
<?php
if (!isset($_GET['id'], $_GET['dateDebut'], $_GET['dateFin'])) {
    echo '<div class="card"><p>Paramètres manquants. <a href="/">Retour à l\'accueil</a></p></div>';
} else {
    require_once __DIR__ . '/db.php';

    $id        = intval($_GET['id']);
    $dateDebut = $_GET['dateDebut'];
    $dateFin   = $_GET['dateFin'];

    $stmt = getDB()->prepare("SELECT * FROM voitures WHERE id = ?");
    $stmt->execute([$id]);
    $voiture = $stmt->fetch();

    if ($voiture) {
        $jours = (new DateTime($dateDebut))->diff(new DateTime($dateFin))->days;
        $total = $jours * $voiture['prix_jour'];
        ?>
        <div class="details-card">
            <div class="image-container">
                <img src="<?= htmlspecialchars($voiture['image']) ?>" alt="<?= htmlspecialchars($voiture['nom']) ?>">
            </div>
            <div class="details-body">
                <h1><?= htmlspecialchars($voiture['nom']) ?></h1>
                <div class="infos-grid">
                    <div class="info-item"><div class="lbl">Prix / jour</div><div class="val"><?= number_format($voiture['prix_jour'], 0, ',', ' ') ?> Ar</div></div>
                    <div class="info-item"><div class="lbl">Durée</div><div class="val"><?= $jours ?> jour<?= $jours > 1 ? 's' : '' ?></div></div>
                    <div class="info-item"><div class="lbl">Total</div><div class="val" style="color:var(--accent)"><?= number_format($total, 0, ',', ' ') ?> Ar</div></div>
                    <div class="info-item"><div class="lbl">Places</div><div class="val"><?= $voiture['nombre_places'] ?></div></div>
                    <div class="info-item"><div class="lbl">Boîte</div><div class="val"><?= htmlspecialchars($voiture['type_boite']) ?></div></div>
                    <div class="info-item"><div class="lbl">Consommation</div><div class="val"><?= $voiture['consommation'] ?> L/100km</div></div>
                </div>
                <p style="font-size:.85rem;color:var(--muted);margin-bottom:16px">
                    Du <strong><?= htmlspecialchars($dateDebut) ?></strong> au <strong><?= htmlspecialchars($dateFin) ?></strong>
                </p>
                <form action="payment.php" method="POST" class="form-reservation">
                    <input type="hidden" name="id_voiture" value="<?= $id ?>">
                    <input type="hidden" name="dateDebut"  value="<?= htmlspecialchars($dateDebut) ?>">
                    <input type="hidden" name="dateFin"    value="<?= htmlspecialchars($dateFin) ?>">
                    <button type="submit" class="btn-reserver">Réserver ce véhicule →</button>
                </form>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="card"><p>Véhicule introuvable.</p></div>';
    }
}
?>
</div>
</body>
</html>
