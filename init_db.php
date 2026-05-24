<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Initialisation de la base de données</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; }
        .ok  { color: green; } .err { color: red; } .info { color: #555; }
        pre  { background: #f4f4f4; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<h1>Initialisation — Location de Voitures</h1>
<?php
require_once __DIR__ . '/db.php';

$pdo = getDB();

// ── Création des tables ──────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS voitures (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nom           TEXT    NOT NULL,
    image         TEXT,
    prix_jour     REAL,
    nombre_places INTEGER,
    consommation  REAL,
    type_boite    TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS clients (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    nom       TEXT NOT NULL,
    prenom    TEXT,
    telephone TEXT,
    email     TEXT UNIQUE
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    id_voiture  INTEGER NOT NULL,
    id_client   INTEGER NOT NULL,
    date_debut  TEXT NOT NULL,
    date_fin    TEXT NOT NULL,
    FOREIGN KEY (id_voiture) REFERENCES voitures(id),
    FOREIGN KEY (id_client)  REFERENCES clients(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS administrateurs (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS maintenances (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    id_voiture       INTEGER NOT NULL,
    date_maintenance TEXT,
    description      TEXT,
    FOREIGN KEY (id_voiture) REFERENCES voitures(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS capteurs (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    id_voiture       INTEGER NOT NULL,
    niveau_carburant INTEGER,
    niveau_huile     INTEGER,
    etat_batterie    INTEGER,
    date_mesure      TEXT,
    FOREIGN KEY (id_voiture) REFERENCES voitures(id)
)");

echo '<p class="ok">✓ Tables créées (ou déjà existantes).</p>';

// ── Migration depuis MySQL si XAMPP est actif ────────────────────────────────
$migrated = false;
try {
    $mysql = new mysqli('localhost', 'root', '', 'location_voiture');
    if (!$mysql->connect_error) {
        echo '<p class="info">MySQL détecté — migration des données en cours...</p>';

        $tables = ['voitures', 'clients', 'reservations', 'administrateurs', 'maintenances', 'capteurs'];
        foreach ($tables as $table) {
            $res = $mysql->query("SELECT COUNT(*) AS n FROM $table");
            if (!$res) { continue; }
            $count = $res->fetch_assoc()['n'];
            if ($count == 0) { continue; }

            $rows = $mysql->query("SELECT * FROM $table");
            $inserted = 0;
            while ($row = $rows->fetch_assoc()) {
                $cols = implode(', ', array_keys($row));
                $placeholders = implode(', ', array_fill(0, count($row), '?'));
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO $table ($cols) VALUES ($placeholders)");
                $stmt->execute(array_values($row));
                $inserted++;
            }
            echo "<p class='ok'>✓ $table : $inserted ligne(s) importée(s).</p>";
        }
        $mysql->close();
        $migrated = true;
    }
} catch (Exception $e) {
    // MySQL non disponible, on continue avec les données par défaut
}

// ── Données par défaut si aucune migration ───────────────────────────────────
if (!$migrated) {
    echo '<p class="info">MySQL non disponible — insertion des données par défaut.</p>';

    // Admin par défaut (à changer !)
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO administrateurs (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', 'admin']);
    echo '<p class="ok">✓ Administrateur créé : <strong>admin / admin</strong></p>';

    // Voitures exemples
    $voitures = [
        ['Renault Clio',   'images/clio.jpg',              50000, 5, 5.5,  'Manuelle'],
        ['Ford Focus',     'images/ford focus.jpeg',       70000, 5, 6.0,  'Manuelle'],
        ['Audi A3',        'images/audi a3.jpg',          120000, 5, 5.8,  'Automatique'],
        ['BMW Série 3',    'images/bmw serie 3.jpeg',     150000, 5, 6.5,  'Automatique'],
        ['Mercedes',       'images/mercedess.jpg',        180000, 5, 7.0,  'Automatique'],
        ['Toyota RAV4',    'images/toyota-rav4_2.jpg',    100000, 7, 7.5,  'Automatique'],
        ['Peugeot 208',    'images/208.png',               45000, 5, 4.8,  'Manuelle'],
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO voitures (nom, image, prix_jour, nombre_places, consommation, type_boite) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($voitures as $v) {
        $stmt->execute($v);
    }
    echo '<p class="ok">✓ ' . count($voitures) . ' voitures exemples insérées.</p>';
}

echo '<hr>';
echo '<p><strong>Base de données prête !</strong> Fichier : <code>location_voiture.sqlite</code></p>';
echo '<p><a href="index.html">→ Aller à l\'accueil</a></p>';
?>
</body>
</html>
