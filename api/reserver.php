<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$id_voiture = intval($_POST['id_voiture'] ?? 0);
$date_debut = trim($_POST['date_debut'] ?? '');
$date_fin   = trim($_POST['date_fin']   ?? '');
$nom        = trim($_POST['nom']        ?? '');
$prenom     = trim($_POST['prenom']     ?? '');
$email      = trim($_POST['email']      ?? '');
$telephone  = trim($_POST['telephone']  ?? '');

if (!$id_voiture || !$date_debut || !$date_fin || !$nom || !$prenom || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
    exit;
}

$pdo = getDB();

// Vérifier que les dates ne chevauchent pas une réservation existante
$check = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE id_voiture = ? AND (? < date_fin AND ? > date_debut)");
$check->execute([$id_voiture, $date_debut, $date_fin]);
if ($check->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ces dates ne sont plus disponibles. Veuillez en choisir d\'autres.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
$stmt->execute([$email]);
$client = $stmt->fetch();

if ($client) {
    $id_client = $client['id'];
} else {
    $pdo->prepare("INSERT INTO clients (nom, prenom, telephone, email) VALUES (?, ?, ?, ?)")
        ->execute([$nom, $prenom, $telephone, $email]);
    $id_client = $pdo->lastInsertId();
}

$ok = $pdo->prepare("INSERT INTO reservations (id_voiture, id_client, date_debut, date_fin) VALUES (?, ?, ?, ?)")
          ->execute([$id_voiture, $id_client, $date_debut, $date_fin]);

if ($ok) {
    $stmt = $pdo->prepare("SELECT nom FROM voitures WHERE id = ?");
    $stmt->execute([$id_voiture]);
    $nomVoiture = $stmt->fetchColumn();
    $debut = (new DateTime($date_debut))->format('d/m/Y');
    $fin   = (new DateTime($date_fin))->format('d/m/Y');
    echo json_encode([
        'success' => true,
        'message' => "Votre {$nomVoiture} est réservée du {$debut} au {$fin}. Nous avons hâte de vous accueillir !"
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la réservation, veuillez réessayer.']);
}
