<?php
// Reçoit les coordonnées GPS depuis l'ESP32
$id_voiture = isset($_GET['id_voiture']) ? intval($_GET['id_voiture']) : null;
$latitude   = isset($_GET['latitude'])   ? floatval($_GET['latitude'])  : null;
$longitude  = isset($_GET['longitude'])  ? floatval($_GET['longitude']) : null;

if (!$id_voiture || $latitude === null || $longitude === null) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

$file = __DIR__ . '/../data/coordinates_' . $id_voiture . '.json';
file_put_contents($file, json_encode(['latitude' => $latitude, 'longitude' => $longitude]));

echo json_encode(['success' => true]);
