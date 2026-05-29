<?php
require_once __DIR__ . '/db.php';
$voitures = getDB()->query("SELECT * FROM voitures ORDER BY prix_jour ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Park DTTS — Location de Voitures</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>

<nav class="site-nav">
    <a href="/" class="nav-brand">
        <img src="/images/logo.jpeg" alt="Park DTTS">
        <span class="name">Park <span>DTTS</span></span>
    </a>
    <div class="nav-links">
        <a href="/admin/login.php" class="btn-admin">Admin</a>
    </div>
</nav>

<main class="home-main">
    <div class="home-header">
        <h1>Nos véhicules disponibles</h1>
        <p>Cliquez sur le calendrier d'une voiture, choisissez vos dates — les jours déjà réservés apparaissent grisés.</p>
    </div>

    <div class="cars-grid">
        <?php foreach ($voitures as $v): ?>
        <div class="car-card" data-id="<?= $v['id'] ?>" data-price="<?= $v['prix_jour'] ?>" data-name="<?= htmlspecialchars($v['nom'], ENT_QUOTES) ?>">
            <div class="car-card__img">
                <img src="/<?= htmlspecialchars($v['image']) ?>" alt="<?= htmlspecialchars($v['nom']) ?>" loading="lazy">
            </div>
            <div class="car-card__body">
                <div class="car-card__header">
                    <h2><?= htmlspecialchars($v['nom']) ?></h2>
                    <span class="car-price"><?= number_format($v['prix_jour'], 0, ',', ' ') ?> Ar<small>/j</small></span>
                </div>
                <div class="car-specs">
                    <span class="spec">&#128100; <?= $v['nombre_places'] ?> places</span>
                    <span class="spec">&#9981; <?= $v['consommation'] ?>L/100</span>
                    <span class="spec">&#9881; <?= htmlspecialchars($v['type_boite']) ?></span>
                </div>
                <div class="car-calendar">
                    <label class="cal-label">Choisir vos dates</label>
                    <input type="text" class="date-input" placeholder="ex : 10 juin → 15 juin" readonly>
                </div>
                <div class="car-summary">
                    <div class="summary-info">
                        <span class="summary-dates"></span>
                        <span class="summary-total"></span>
                    </div>
                    <button class="btn-reserver" type="button">Réserver</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modale de réservation -->
<div id="modal-overlay" class="modal-overlay">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-car-name">
        <button class="modal-close" id="modal-close" aria-label="Fermer">&#x2715;</button>

        <div class="modal-car-info">
            <h2 id="modal-car-name"></h2>
            <p id="modal-dates"></p>
            <p class="modal-total" id="modal-total"></p>
        </div>

        <form id="modal-form" novalidate>
            <input type="hidden" name="id_voiture" id="modal-id-voiture">
            <input type="hidden" name="date_debut"  id="modal-date-debut">
            <input type="hidden" name="date_fin"    id="modal-date-fin">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" required placeholder="Dupont" autocomplete="family-name">
                </div>
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" required placeholder="Marie" autocomplete="given-name">
                </div>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="email@exemple.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="telephone" placeholder="+261 34 00 000 00" autocomplete="tel">
            </div>
            <div id="modal-error" class="error-message" role="alert"></div>
            <button type="submit" id="modal-submit">Confirmer la réservation</button>
        </form>

        <div id="modal-success" class="modal-success">
            <div class="success-icon">&#127881;</div>
            <h2>Réservation confirmée !</h2>
            <p id="modal-success-msg"></p>
            <button type="button" id="modal-ok" class="btn-ok">Fermer</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script src="/script.js"></script>
</body>
</html>
