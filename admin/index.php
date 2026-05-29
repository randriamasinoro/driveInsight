<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php');
    exit();
}

require_once __DIR__ . '/../db.php';
$pdo = getDB();

// 1. Réservations EN COURS (aujourd'hui est entre date_debut et date_fin)
$actives = $pdo->query("
    SELECT r.id AS res_id, r.date_debut, r.date_fin,
           v.id, v.nom, v.image, v.prix_jour, v.nombre_places, v.type_boite,
           c.nom AS client_nom, c.prenom AS client_prenom,
           c.telephone AS client_tel, c.email AS client_email
    FROM reservations r
    JOIN voitures v ON r.id_voiture = v.id
    JOIN clients  c ON r.id_client  = c.id
    WHERE r.date_debut <= date('now')
      AND r.date_fin   >= date('now')
    ORDER BY r.date_fin ASC
")->fetchAll();

// 2. TOUTES les réservations à venir (pas encore commencées)
$futures = $pdo->query("
    SELECT r.id AS res_id, r.date_debut, r.date_fin,
           v.id, v.nom, v.image, v.prix_jour, v.nombre_places, v.type_boite,
           c.nom AS client_nom, c.prenom AS client_prenom,
           c.telephone AS client_tel, c.email AS client_email
    FROM reservations r
    JOIN voitures v ON r.id_voiture = v.id
    JOIN clients  c ON r.id_client  = c.id
    WHERE r.date_debut > date('now')
    ORDER BY r.date_debut ASC
")->fetchAll();

// 3. Voitures DISPONIBLES (aucune réservation présente ou future)
$disponibles = $pdo->query("
    SELECT * FROM voitures
    WHERE id NOT IN (
        SELECT id_voiture FROM reservations WHERE date_fin >= date('now')
    )
    ORDER BY nom ASC
")->fetchAll();

// Stats
$totalVoitures = $pdo->query("SELECT COUNT(*) FROM voitures")->fetchColumn();
$nbEnCours     = count(array_unique(array_column($actives, 'id')));
$nbReservees   = count(array_diff(
    array_unique(array_column($futures, 'id')),
    array_unique(array_column($actives, 'id'))
));
$nbDispo = count($disponibles);

// Flash messages
$flash = '';
if (isset($_GET['ok'])) {
    $flash = $_GET['ok'] === 'voiture' ? 'Véhicule ajouté avec succès.' : '';
    $flashType = 'success';
}
if (isset($_GET['err'])) {
    $msgs = ['champs' => 'Nom et prix sont obligatoires.', 'format' => 'Format d\'image non autorisé (jpg, png, webp).'];
    $flash = $msgs[$_GET['err']] ?? 'Une erreur est survenue.';
    $flashType = 'error';
}
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

    <?php if ($flash): ?>
    <div class="flash flash-<?= $flashType ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
        <div class="stats">
            <div class="stat">
                <div class="stat-icon">🚗</div>
                <div><div class="stat-num"><?= $totalVoitures ?></div><div class="stat-label">Total véhicules</div></div>
            </div>
            <div class="stat orange">
                <div class="stat-icon">🔑</div>
                <div><div class="stat-num"><?= $nbEnCours ?></div><div class="stat-label">En location</div></div>
            </div>
            <div class="stat" style="border-left-color:#1a3c6e">
                <div class="stat-icon">📅</div>
                <div><div class="stat-num"><?= $nbReservees ?></div><div class="stat-label">Réservées</div></div>
            </div>
            <div class="stat green">
                <div class="stat-icon">✅</div>
                <div><div class="stat-num"><?= $nbDispo ?></div><div class="stat-label">Disponibles</div></div>
            </div>
        </div>
        <button class="btn-add-car" id="btn-open-modal-voiture">+ Ajouter un véhicule</button>
    </div>

    <?php if ($actives): ?>
    <div class="section-title">En location actuellement</div>
    <div class="car-grid" style="margin-bottom:36px">
        <?php foreach ($actives as $v): ?>
        <div class="car-card louee">
            <img src="/<?= htmlspecialchars($v['image']) ?>" alt="<?= htmlspecialchars($v['nom']) ?>">
            <span class="badge louee">En cours</span>
            <div class="car-body">
                <div class="car-name"><?= htmlspecialchars($v['nom']) ?></div>
                <div class="car-specs"><?= $v['nombre_places'] ?> places · <?= htmlspecialchars($v['type_boite']) ?> · <?= number_format($v['prix_jour'], 0, ',', ' ') ?> Ar/j</div>
                <div class="locataire-box">
                    <div class="lbl">Locataire</div>
                    <div class="name"><?= htmlspecialchars($v['client_prenom'] . ' ' . $v['client_nom']) ?></div>
                    <div class="meta"><?= htmlspecialchars($v['client_tel']) ?> · <?= htmlspecialchars($v['client_email']) ?></div>
                    <div class="dates">Du <?= $v['date_debut'] ?> au <?= $v['date_fin'] ?></div>
                </div>
                <div class="card-actions">
                    <a href="/admin/capteurs.php?id=<?= $v['id'] ?>" class="btn-detail">Carte &amp; Capteurs →</a>
                    <form method="POST" action="/admin/supprimer_reservation.php" class="form-delete"
                          onsubmit="return confirm('Annuler cette réservation en cours ?')">
                        <input type="hidden" name="res_id" value="<?= $v['res_id'] ?>">
                        <button type="submit" class="btn-delete" title="Annuler la réservation">🗑 Annuler</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($futures): ?>
    <div class="section-title">
        Réservations à venir
        <span class="section-count"><?= count($futures) ?> réservation<?= count($futures) > 1 ? 's' : '' ?></span>
    </div>
    <div class="reservations-list">
        <?php foreach ($futures as $r): ?>
        <div class="res-row">
            <img src="/<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['nom']) ?>" class="res-img">
            <div class="res-voiture">
                <div class="res-nom"><?= htmlspecialchars($r['nom']) ?></div>
                <div class="res-specs"><?= number_format($r['prix_jour'], 0, ',', ' ') ?> Ar/j · <?= htmlspecialchars($r['type_boite']) ?></div>
            </div>
            <div class="res-dates">
                <div class="res-debut"><?= (new DateTime($r['date_debut']))->format('d/m/Y') ?></div>
                <div class="res-arrow">→</div>
                <div class="res-fin"><?= (new DateTime($r['date_fin']))->format('d/m/Y') ?></div>
            </div>
            <div class="res-client">
                <div class="res-client-name"><?= htmlspecialchars($r['client_prenom'] . ' ' . $r['client_nom']) ?></div>
                <div class="res-client-meta"><?= htmlspecialchars($r['client_email']) ?></div>
                <?php if ($r['client_tel']): ?>
                <div class="res-client-meta"><?= htmlspecialchars($r['client_tel']) ?></div>
                <?php endif; ?>
            </div>
            <span class="badge future">Réservé</span>
            <form method="POST" action="/admin/supprimer_reservation.php" class="form-delete"
                  onsubmit="return confirm('Supprimer cette réservation ?')">
                <input type="hidden" name="res_id" value="<?= $r['res_id'] ?>">
                <button type="submit" class="btn-delete-row" title="Supprimer">🗑</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($disponibles): ?>
    <div class="section-title" style="margin-top:36px">Disponibles</div>
    <div class="car-grid">
        <?php foreach ($disponibles as $v): ?>
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

<!-- Modale ajout véhicule -->
<div id="modal-voiture" class="admin-modal-overlay">
    <div class="admin-modal">
        <button class="admin-modal-close" id="btn-close-modal-voiture">&#x2715;</button>
        <h2 class="admin-modal-title">Ajouter un véhicule</h2>
        <form method="POST" action="/admin/ajouter_voiture.php" enctype="multipart/form-data" class="admin-form">
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label>Nom du véhicule *</label>
                    <input type="text" name="nom" required placeholder="ex : Toyota Yaris">
                </div>
                <div class="admin-form-group">
                    <label>Prix / jour (Ar) *</label>
                    <input type="number" name="prix_jour" required min="1" placeholder="ex : 60000">
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label>Nombre de places</label>
                    <input type="number" name="nombre_places" min="1" max="9" value="5">
                </div>
                <div class="admin-form-group">
                    <label>Consommation (L/100km)</label>
                    <input type="text" name="consommation" placeholder="ex : 6.5">
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label>Type de boîte</label>
                    <select name="type_boite">
                        <option value="Manuelle">Manuelle</option>
                        <option value="Automatique">Automatique</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Photo du véhicule</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="input-file">
                </div>
            </div>
            <button type="submit" class="admin-btn-submit">Ajouter le véhicule</button>
        </form>
    </div>
</div>

<script>
const overlay   = document.getElementById('modal-voiture');
const btnOpen   = document.getElementById('btn-open-modal-voiture');
const btnClose  = document.getElementById('btn-close-modal-voiture');
btnOpen.addEventListener('click',  () => overlay.classList.add('open'));
btnClose.addEventListener('click', () => overlay.classList.remove('open'));
overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') overlay.classList.remove('open'); });
</script>

</body>
</html>
