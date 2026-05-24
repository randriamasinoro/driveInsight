<?php
if (!isset($_POST['id_voiture'], $_POST['dateDebut'], $_POST['dateFin'])) {
    die("Données manquantes.");
}

require_once __DIR__ . '/../db.php';

$id_voiture = intval($_POST['id_voiture']);
$dateDebut  = $_POST['dateDebut'];
$dateFin    = $_POST['dateFin'];

$stmt = getDB()->prepare("SELECT prix_jour FROM voitures WHERE id = ?");
$stmt->execute([$id_voiture]);
$voiture = $stmt->fetch();

if (!$voiture) {
    die("Voiture non trouvée.");
}

$prix_jour    = $voiture['prix_jour'];
$nombre_jours = (new DateTime($dateDebut))->diff(new DateTime($dateFin))->days;
$total        = $nombre_jours * $prix_jour;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement — Park DTTS</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div class="container1">
        <header>
            <img src="/images/logo.jpeg" alt="Logo" class="logo">
        </header>
        <main>
            <h2>Informations de Paiement</h2>
            <p>Montant total à payer : <span><?php echo number_format($total, 2); ?> Ar</span></p>
            <form action="/client/confirmation.php" method="POST">
                <input type="hidden" name="id_voiture" value="<?php echo $id_voiture; ?>">
                <input type="hidden" name="dateDebut"  value="<?php echo htmlspecialchars($dateDebut); ?>">
                <input type="hidden" name="dateFin"    value="<?php echo htmlspecialchars($dateFin); ?>">

                <h3>Informations Personnelles</h3>
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required>

                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" required>

                <label for="telephone">Téléphone :</label>
                <input type="tel" id="telephone" name="telephone" required>

                <label for="email">Adresse e-mail :</label>
                <input type="email" id="email" name="email" required>

                <h3>Informations de Carte de Crédit</h3>
                <label for="numero_carte">Numéro de carte Visa :</label>
                <input type="text" id="numero_carte" name="numero_carte" pattern="\d{16}" required>

                <label for="nom_titulaire">Nom du titulaire :</label>
                <input type="text" id="nom_titulaire" name="nom_titulaire" required>

                <label for="expiration">Date d'expiration (MM/AAAA) :</label>
                <input type="text" id="expiration" name="expiration" pattern="\d{2}/\d{4}" required>

                <label for="cvv">CVV :</label>
                <input type="text" id="cvv" name="cvv" pattern="\d{3}" required>

                <button type="submit">Valider le Paiement</button>
            </form>
        </main>
    </div>
</body>
</html>
