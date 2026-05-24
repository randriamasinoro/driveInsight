<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php');
    exit();
}

require_once __DIR__ . '/../db.php';
$pdo = getDB();

$voitures = $pdo->query(
    "SELECT v.*,
            r.id         AS res_id,
            r.date_debut, r.date_fin,
            c.nom        AS client_nom,
            c.prenom     AS client_prenom,
            c.telephone  AS client_tel,
            c.email      AS client_email
     FROM voitures v
     LEFT JOIN reservations r
            ON v.id = r.id_voiture
           AND r.date_fin   >= date('now')
           AND r.date_debut <= date('now')
     LEFT JOIN clients c ON r.id_client = c.id
     ORDER BY r.id DESC, v.nom ASC"
)->fetchAll();

$total  = count($voitures);
$louees = count(array_filter($voitures, fn($v) => $v['res_id']));
$dispo  = $total - $louees;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — Park DTTS</title>
    <link rel="stylesheet" href="/admin/styles.css">
</head>
<body>

<div class="topbar">
    <div class="brand">Park <span>DTTS</span> — Admin</div>
    <div class="topbar-nav">
        <a href="/">Accueil client</a>
        <a href="/admin/logout.php">Déconnexion</a>
    </div>
</div>

<div class="page">

    <div class="stats">
        <div class="stat">
            <div class="stat-icon">🚗</div>
            <div><div class="stat-num"><?= $total ?></div><div class="stat-label">Total véhicules</div></div>
        </div>
        <div class="stat orange">
            <div class="stat-icon">🔑</div>
            <div><div class="stat-num"><?= $louees ?></div><div class="stat-label">En location</div></div>
        </div>
        <div class="stat green">
            <div class="stat-icon">✅</div>
            <div><div class="stat-num"><?= $dispo ?></div><div class="stat-label">Disponibles</div></div>
        </div>
    </div>

    <?php $actives = array_filter($voitures, fn($v) => $v['res_id']); ?>
    <?php if ($actives): ?>
    <div class="section-title">En location actuellement</div>
    <div class="car-grid" style="margin-bottom:36px">
        <?php foreach ($actives as $v): ?>
        <div class="car-card louee">
            <img src="/<?= htmlspecialchars($v['image']) ?>" alt="<?= htmlspecialchars($v['nom']) ?>">
            <span class="badge louee">Loué</span>
            <div class="car-body">
                <div class="car-name"><?= htmlspecialchars($v['nom']) ?></div>
                <div class="car-specs"><?= $v['nombre_places'] ?> places · <?= htmlspecialchars($v['type_boite']) ?> · <?= number_format($v['prix_jour'], 0, ',', ' ') ?> Ar/j</div>
                <div class="locataire-box">
                    <div class="lbl">Locataire</div>
                    <div class="name"><?= htmlspecialchars($v['client_prenom'] . ' ' . $v['client_nom']) ?></div>
                    <div class="meta"><?= htmlspecialchars($v['client_tel']) ?> · <?= htmlspecialchars($v['client_email']) ?></div>
                    <div class="dates">Du <?= $v['date_debut'] ?> au <?= $v['date_fin'] ?></div>
                </div>
                <a href="/admin/capteurs.php?id=<?= $v['id'] ?>" class="btn-detail">Carte &amp; Capteurs →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php $libres = array_filter($voitures, fn($v) => !$v['res_id']); ?>
    <?php if ($libres): ?>
    <div class="section-title">Disponibles</div>
    <div class="car-grid">
        <?php foreach ($libres as $v): ?>
        <div class="car-card">
            <img src="/<?= htmlspecialchars($v['image']) ?>" alt="<?= htmlspecialchars($v['nom']) ?>">
            <span class="badge dispo">Disponible</span>
            <div class="car-body">
                <div class="car-name"><?= htmlspecialchars($v['nom']) ?></div>
                <div class="car-specs"><?= $v['nombre_places'] ?> places · <?= htmlspecialchars($v['type_boite']) ?> · <?= number_format($v['prix_jour'], 0, ',', ' ') ?> Ar/j</div>
                <a href="/admin/capteurs.php?id=<?= $v['id'] ?>" class="btn-detail">Voir capteurs →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
