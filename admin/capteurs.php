<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php');
    exit();
}

require_once __DIR__ . '/../db.php';
$pdo = getDB();

$vehicule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM voitures WHERE id = ?");
$stmt->execute([$vehicule_id]);
$voiture = $stmt->fetch();

if (!$voiture) {
    header('Location: /admin/index.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT r.date_debut, r.date_fin,
            c.nom AS client_nom, c.prenom AS client_prenom, c.email, c.telephone
     FROM reservations r
     JOIN clients c ON r.id_client = c.id
     WHERE r.id_voiture = ? AND r.date_fin >= date('now') AND r.date_debut <= date('now')
     ORDER BY r.date_debut DESC LIMIT 1"
);
$stmt->execute([$vehicule_id]);
$location = $stmt->fetch();

$stmt = $pdo->prepare("SELECT date_maintenance, description FROM maintenances WHERE id_voiture = ? ORDER BY date_maintenance DESC");
$stmt->execute([$vehicule_id]);
$maintenances = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($voiture['nom']) ?> — Park DTTS</title>
    <link rel="stylesheet" href="/admin/styles.css">
    <script src="https://cdn.maptiler.com/maptiler-sdk-js/v2.0.3/maptiler-sdk.umd.min.js"></script>
    <link href="https://cdn.maptiler.com/maptiler-sdk-js/v2.0.3/maptiler-sdk.css" rel="stylesheet">
</head>
<body>

<div class="topbar">
    <div class="brand">Park <span>DTTS</span> — <?= htmlspecialchars($voiture['nom']) ?></div>
    <div class="topbar-nav">
        <a href="/admin/index.php">← Dashboard</a>
        <a href="/admin/logout.php">Déconnexion</a>
    </div>
</div>

<div class="container">

    <div class="vehicule-header">
        <img src="/<?= htmlspecialchars($voiture['image']) ?>" alt="<?= htmlspecialchars($voiture['nom']) ?>">
        <h1><?= htmlspecialchars($voiture['nom']) ?></h1>
        <?php if ($location): ?>
            <span class="status-badge active">● En location — jusqu'au <?= htmlspecialchars($location['date_fin']) ?></span>
        <?php else: ?>
            <span class="status-badge inactive">● Disponible</span>
        <?php endif; ?>
    </div>

    <nav class="navigation-bar">
        <ul>
            <li><a href="#capteurs"    onclick="showSection('capteurs')">Capteurs</a></li>
            <li><a href="#map"         onclick="showSection('map')">Carte GPS</a></li>
            <li><a href="#maintenance" onclick="showSection('maintenance')">Maintenance</a></li>
            <li><a href="#locataire"   onclick="showSection('locataire')">Locataire</a></li>
        </ul>
    </nav>

    <!-- Capteurs -->
    <div id="capteurs" class="content-section">
        <h2>Données des capteurs</h2>
        <div class="sensor-grid">
            <div class="sensor-card">
                <div class="sensor-icon">⛽</div>
                <div class="sensor-label">Carburant</div>
                <div class="sensor-value" id="val-carburant">—</div>
                <div class="sensor-unit">%</div>
                <div class="sensor-bar"><div class="sensor-bar-fill" id="bar-carburant" style="width:0%"></div></div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">🛢️</div>
                <div class="sensor-label">Huile</div>
                <div class="sensor-value" id="val-huile">—</div>
                <div class="sensor-unit">%</div>
                <div class="sensor-bar"><div class="sensor-bar-fill" id="bar-huile" style="width:0%"></div></div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">🔋</div>
                <div class="sensor-label">Batterie</div>
                <div class="sensor-value" id="val-batterie">—</div>
                <div class="sensor-unit">V</div>
            </div>
        </div>
    </div>

    <!-- Carte -->
    <div id="map" class="content-section">
        <h2>Carte GPS — Position en temps réel</h2>
        <select class="mapstyles-select">
            <optgroup label="Ville">
                <option value="STREETS">Streets</option>
                <option value="STREETS.DARK">Streets Dark</option>
                <option value="STREETS.LIGHT">Streets Light</option>
            </optgroup>
            <option value="OUTDOOR">Outdoor</option>
            <option value="SATELLITE">Satellite</option>
            <option value="HYBRID" selected>Hybride</option>
        </select>
        <div id="map-container" class="map-container"></div>
    </div>

    <!-- Maintenance -->
    <div id="maintenance" class="content-section">
        <h2>Historique Maintenance</h2>
        <div class="table-container">
            <table class="maintenance-table">
                <thead><tr><th>Date</th><th>Description</th></tr></thead>
                <tbody>
                    <?php foreach ($maintenances as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date_maintenance']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$maintenances): ?>
                    <tr><td colspan="2" style="text-align:center;color:#9ca3af;padding:24px">Aucun historique de maintenance</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Locataire -->
    <div id="locataire" class="content-section">
        <h2>Informations du Locataire</h2>
        <?php if ($location): ?>
        <div class="client-info">
            <p><strong>Nom</strong><?= htmlspecialchars($location['client_nom']) ?></p>
            <p><strong>Prénom</strong><?= htmlspecialchars($location['client_prenom']) ?></p>
            <p><strong>Email</strong><?= htmlspecialchars($location['email']) ?></p>
            <p><strong>Téléphone</strong><?= htmlspecialchars($location['telephone']) ?></p>
            <p><strong>Début</strong><?= htmlspecialchars($location['date_debut']) ?></p>
            <p><strong>Fin</strong><?= htmlspecialchars($location['date_fin']) ?></p>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:48px 20px;color:#9ca3af">
            <div style="font-size:2.5rem;margin-bottom:10px">👤</div>
            <p>Aucune location active pour ce véhicule.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
maptilersdk.config.apiKey = 'Il2e1UDv4rtglFg1NRtv';
let map, marker;

function showSection(id) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.navigation-bar a').forEach(a => a.classList.remove('active'));
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
    const link = document.querySelector(`.navigation-bar a[href="#${id}"]`);
    if (link) link.classList.add('active');
    if (id === 'map') initMap();
}

function setBar(id, val) {
    const bar = document.getElementById(id);
    if (!bar) return;
    bar.style.width = val + '%';
    bar.className = 'sensor-bar-fill ' + (val < 25 ? 'low' : val < 60 ? 'mid' : 'high');
}

setInterval(() => {
    fetch(`/admin/data/distances_<?= $vehicule_id ?>.json?t=${Date.now()}`)
        .then(r => r.json())
        .then(d => {
            if (d.distance1 !== undefined) { document.getElementById('val-carburant').textContent = d.distance1; setBar('bar-carburant', d.distance1); }
            if (d.distance2 !== undefined) { document.getElementById('val-huile').textContent     = d.distance2; setBar('bar-huile',     d.distance2); }
            if (d.distance3 !== undefined) { document.getElementById('val-batterie').textContent  = d.distance3; }
        }).catch(() => {});
}, 500);

function initMap() {
    if (map) return;
    map = new maptilersdk.Map({ container: 'map-container', style: maptilersdk.MapStyle.HYBRID, center: [47.5425, -18.8958], zoom: 14 });
    marker = new maptilersdk.Marker().setLngLat([47.5425, -18.8958]).addTo(map);
    setInterval(() => {
        fetch(`/admin/data/coordinates_<?= $vehicule_id ?>.json?t=${Date.now()}`)
            .then(r => r.json())
            .then(d => {
                if (d.latitude && d.longitude) {
                    marker.setLngLat([d.longitude, d.latitude]);
                    map.setCenter([d.longitude, d.latitude]);
                }
            }).catch(() => {});
    }, 1000);
}

document.querySelector('.mapstyles-select')?.addEventListener('change', e => {
    const parts = e.target.value.split('.');
    map?.setStyle(parts.length === 2 ? maptilersdk.MapStyle[parts[0]][parts[1]] : maptilersdk.MapStyle[parts[0]]);
});

document.addEventListener('DOMContentLoaded', () => {
    showSection(window.location.hash.substr(1) || 'capteurs');
});
</script>
</body>
</html>
