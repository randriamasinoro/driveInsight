<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php'); exit();
}
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php'); exit();
}

$nom       = trim($_POST['nom']           ?? '');
$prix      = intval($_POST['prix_jour']   ?? 0);
$places    = intval($_POST['nombre_places'] ?? 5);
$conso     = floatval(str_replace(',', '.', $_POST['consommation'] ?? '0'));
$boite     = in_array($_POST['type_boite'] ?? '', ['Manuelle', 'Automatique'])
             ? $_POST['type_boite'] : 'Manuelle';

if (!$nom || $prix <= 0) {
    header('Location: /admin/index.php?err=champs'); exit();
}

$imagePath = 'images/logo.jpeg'; // fallback si pas d'upload

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) {
        header('Location: /admin/index.php?err=format'); exit();
    }
    $slug     = preg_replace('/[^a-z0-9]+/', '-', strtolower($nom));
    $filename = $slug . '-' . time() . '.' . $ext;
    $dest     = __DIR__ . '/../images/' . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $imagePath = 'images/' . $filename;
    }
}

getDB()->prepare(
    "INSERT INTO voitures (nom, image, prix_jour, nombre_places, consommation, type_boite)
     VALUES (?, ?, ?, ?, ?, ?)"
)->execute([$nom, $imagePath, $prix, $places, $conso, $boite]);

header('Location: /admin/index.php?ok=voiture');
exit();
