<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$id = intval($_GET['id_voiture'] ?? 0);
if (!$id) { echo json_encode([]); exit; }

$stmt = getDB()->prepare("SELECT date_debut, date_fin FROM reservations WHERE id_voiture = ?");
$stmt->execute([$id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ranges = array_map(fn($r) => ['from' => $r['date_debut'], 'to' => $r['date_fin']], $rows);
echo json_encode($ranges);
