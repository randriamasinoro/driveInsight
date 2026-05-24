# Park DTTS — Système de Location de Voitures

Projet universitaire L3 DTSS — Application web de gestion d'un parc automobile avec suivi GPS et capteurs IoT en temps réel.

## Fonctionnalités

**Côté client**
- Sélection de dates et vérification de disponibilité
- Fiche détaillée de chaque véhicule (prix, caractéristiques)
- Formulaire de réservation et paiement

**Côté admin**
- Tableau de bord : état de tous les véhicules (disponible / loué)
- Suivi en temps réel des capteurs (carburant, huile, batterie)
- Carte GPS en temps réel (MapTiler + Particle Boron)
- Informations du locataire actif
- Historique de maintenance

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend   | PHP 8.3 (serveur intégré) |
| Base de données | SQLite 3 (via PDO) |
| Frontend  | HTML5, CSS3 vanilla, JS vanilla |
| Carte GPS | MapTiler SDK v2 |
| IoT       | Particle Boron (Arduino / firmware `.ino`) |

## Installation & Lancement

### Prérequis
- PHP 8.3+ avec extension `pdo_sqlite` activée
- Aucun autre logiciel requis (pas de XAMPP, pas de MySQL)

### 1. Cloner le projet
```bash
git clone <url-du-repo>
cd location_voiture
```

### 2. Démarrer le serveur
Double-cliquer sur **`start.bat`**

Le script :
- Détecte automatiquement PHP sur le système
- Crée la base de données SQLite si elle n'existe pas
- Lance le serveur sur `http://localhost:8080`
- Ouvre le navigateur automatiquement

### 3. Initialiser la base de données (première fois)
Ouvrir `http://localhost:8080/init_db.php`

Si XAMPP est actif avec une base MySQL existante, les données sont migrées automatiquement.

## Accès

| Page | URL |
|------|-----|
| Accueil (client) | `http://localhost:8080/` |
| Admin — login | `http://localhost:8080/admin/login.php` |
| Admin — dashboard | `http://localhost:8080/admin/index.php` |

**Identifiants admin par défaut :** `admin` / `admin`
> Changer le mot de passe après la première connexion.

## Structure du projet

```
location_voiture/
├── index.html              # Accueil — sélection des dates
├── db.php                  # Connexion PDO SQLite (partagée)
├── init_db.php             # Initialisation / migration BDD
├── start.bat               # Démarrage du serveur (Windows)
├── styles.css              # Styles côté client
├── script.js               # JS côté client
├── images/                 # Photos des véhicules
├── firmware/
│   └── GPSandSensorData.ino  # Firmware Particle Boron (GPS + capteurs)
├── client/
│   ├── disponibilite.php   # Voitures disponibles
│   ├── details_voiture.php # Fiche véhicule
│   ├── payment.php         # Formulaire de paiement
│   └── confirmation.php    # Confirmation de réservation
├── admin/
│   ├── index.php           # Tableau de bord
│   ├── login.php           # Connexion admin
│   ├── logout.php          # Déconnexion
│   ├── capteurs.php        # Suivi véhicule (GPS + capteurs)
│   ├── styles.css          # Styles admin
│   ├── api/
│   │   ├── location.php    # Endpoint GPS (Boron → serveur)
│   │   └── capteurs.php    # Endpoint capteurs (Boron → serveur)
│   └── data/
│       ├── coordinates_*.json  # Position GPS par véhicule
│       └── distances_*.json    # Niveaux capteurs par véhicule
└── location_voiture.sqlite # Base de données (non versionné)
```

## Firmware Particle Boron

Le fichier `firmware/GPSandSensorData.ino` est flashé sur une carte **Particle Boron** embarquée dans chaque véhicule. Il envoie automatiquement :

- La **position GPS** toutes les 10 secondes
- Les **données capteurs** (carburant, huile, batterie) toutes les 5 secondes

### Configuration avant flashage

Chaque carte doit avoir son propre identifiant véhicule (ligne 17 du firmware) :
```cpp
const char* id_voiture = "1"; // ← changer pour chaque carte : 1, 2, 3...
```

L'IP du serveur doit correspondre à la machine qui fait tourner `start.bat` :
```cpp
const char* serverName = "192.168.1.101"; // ← IP locale du PC serveur
```

### Broches utilisées

| Broche | Capteur |
|--------|---------|
| Pin 2  | Niveau carburant (analogique) |
| Pin 14 | Niveau huile moteur (analogique) |
| Pin 15 | Tension batterie (analogique) |
| RX=1 / TX=3 | Module GPS (Serial1) |

### Opérateur mobile (Madagascar)
APN configuré pour **Telma** (`telmanet`).

## API pour le Boron

### Envoyer la position GPS
```
GET /admin/api/location.php?id_voiture=1&latitude=-18.8958&longitude=47.5425
```

### Envoyer les données capteurs
```
GET /admin/api/capteurs.php?id_voiture=1&distance1=75&distance2=90&distance3=13
```

| Paramètre  | Description          | Unité |
|------------|----------------------|-------|
| distance1  | Niveau carburant     | %     |
| distance2  | Niveau huile moteur  | %     |
| distance3  | Tension batterie     | V     |

## Base de données

```sql
voitures        -- Véhicules du parc
clients         -- Clients ayant réservé
reservations    -- Réservations (id_voiture, id_client, dates)
administrateurs -- Comptes admin
maintenances    -- Historique d'entretien
capteurs        -- Historique des mesures IoT
```

## Auteur

Elisa Randriamasinoro — L3 DTSS
