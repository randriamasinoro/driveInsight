<?php
// Reçoit les données des capteurs depuis l'ESP32
require_once __DIR__ . '/../../db.php';

$id_voiture = isset($_GET['id_voiture']) ? intval($_GET['id_voiture']) : null;
$distance1  = isset($_GET['distance1'])  ? intval($_GET['distance1'])  : null;
$distance2  = isset($_GET['distance2'])  ? intval($_GET['distance2'])  : null;
$distance3  = isset($_GET['distance3'])  ? intval($_GET['distance3'])  : null;

if (!$id_voiture || $distance1 === null || $distance2 === null || $distance3 === null) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

try {
    getDB()->prepare(
        "INSERT INTO capteurs (id_voiture, niveau_carburant, niveau_huile, etat_batterie, date_mesure)
         VALUES (?, ?, ?, ?, datetime('now'))"
    )->execute([$id_voiture, $distance1, $distance2, $distance3]);

    $data = ['distance1' => $distance1, 'distance2' => $distance2, 'distance3' => $distance3, 'date_mesure' => date('Y-m-d H:i:s')];
    file_put_contents(__DIR__ . '/../data/distances_' . $id_voiture . '.json', json_encode($data));

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
