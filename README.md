# DriveInsight — Système de gestion de parc automobile connecté

Projet de Fin d'Études · L3 Génie des Systèmes Automatisés
Institut de Technologie d'Antananarivo (ITA) · Madagascar · 2025

Application web de gestion d'un parc automobile avec suivi GPS et capteurs IoT en temps réel,
couplée à un module embarqué Particle Boron par véhicule.

---

## Fonctionnalités

### Côté client
- Page d'accueil avec grille de toutes les voitures disponibles
- Calendrier interactif par véhicule (Flatpickr) — dates réservées grisées automatiquement
- Calcul du tarif en temps réel à la sélection d'une plage de dates
- Réservation finalisée via une modale (sans changer de page)
- Double vérification de disponibilité : côté client (calendrier) et côté serveur (anti-race condition)

### Côté administrateur
- Tableau de bord avec 4 compteurs : total véhicules / en location / réservées / disponibles
- 3 sections distinctes : locations en cours · réservations à venir (toutes) · véhicules disponibles
- Suppression d'une réservation en cours ou à venir avec confirmation
- Ajout d'un véhicule au parc via modale (nom, tarif, caractéristiques, photo)
- Suivi temps réel par véhicule : position GPS sur carte (MapTiler) + état capteurs
- Messages flash de confirmation/erreur sur les actions

---

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.3 (serveur intégré) |
| Base de données | SQLite 3 via PDO |
| Frontend | HTML5 · CSS3 · JavaScript vanilla |
| Calendrier | Flatpickr (CDN) |
| Carte GPS | MapTiler SDK v2 |
| IoT | Particle Boron — firmware Arduino (`.ino`) |
| Capteurs | ADC analogique 12 bits — carburant, huile, batterie |
| Réseau IoT | Cellulaire Telma Madagascar (`telmanet`) |

---

## Statut : prototype de recherche

Ce projet est un **prototype académique**, pas un système prêt pour la production.
Plusieurs choix techniques sont délibérément simplifiés pour rester dans le périmètre
d'un projet de fin d'études de trois ans.

**Le point le plus important à comprendre sur la partie IoT :**
les capteurs de carburant, d'huile et de batterie sont des **capteurs analogiques externes**
câblés manuellement sur chaque véhicule. Dans la réalité, cette approche n'a aucun sens
opérationnel — elle est imprecise, fragile, longue à installer, et surtout inutile : toutes
ces données (et bien d'autres — régime moteur, température, codes défauts DTC, kilométrage
réel) sont **déjà disponibles** dans le calculateur du véhicule via le **bus CAN**, accessible
par le port **OBD-II** présent sur tout véhicule moderne.

La bonne architecture embarquée serait :

```
Particle Boron
  └── SPI → MCP2515 (contrôleur CAN) → TJA1050 (transceiver) → port OBD-II
```

Un seul connecteur standardisé, aucun câblage intrusif, et accès à 50+ paramètres
directement depuis l'ECU du véhicule. C'est la piste d'évolution prioritaire documentée
dans [`driveInsight.mdx`](./driveInsight.mdx).

Les autres limites connues du prototype : authentification en clair, serveur PHP mono-thread,
persistance IoT en fichiers JSON plats, aucun système de paiement réel, endpoints IoT
non authentifiés.

---

## Prérequis

- PHP 8.3+ avec l'extension `pdo_sqlite` activée
- Aucun autre logiciel requis (pas de XAMPP, pas de MySQL, pas de Node.js)

---

## Lancement

### Linux / Ubuntu
```bash
./start.sh
```

### Windows
Double-cliquer sur `start.bat`

Le script :
- Détecte automatiquement PHP sur le système
- Crée et initialise la base de données SQLite si absente ou vide
- Lance le serveur sur `http://localhost:8080`
- Ouvre le navigateur automatiquement

---

## Accès

| Page | URL |
|------|-----|
| Accueil client | `http://localhost:8080/` |
| Connexion admin | `http://localhost:8080/admin/login.php` |
| Tableau de bord admin | `http://localhost:8080/admin/index.php` |

**Identifiants admin par défaut :** `admin` / `admin`

---

## Structure du projet

```
driveInsight/
├── index.php                   # Accueil — grille de voitures + calendriers
├── db.php                      # Connexion PDO SQLite (partagée)
├── init_db.php                 # Initialisation / migration BDD
├── start.sh                    # Démarrage serveur Linux
├── start.bat                   # Démarrage serveur Windows
├── styles.css                  # Styles côté client
├── script.js                   # Flatpickr + modale de réservation
├── images/                     # Photos des véhicules
│
├── api/
│   ├── disponibilite.php       # GET ?id_voiture=X → plages réservées (JSON)
│   └── reserver.php            # POST → crée la réservation (JSON)
│
├── client/                     # Pages de l'ancien flux (conservées)
│   ├── disponibilite.php
│   ├── details_voiture.php
│   ├── payment.php
│   └── confirmation.php
│
├── admin/
│   ├── index.php               # Tableau de bord
│   ├── login.php               # Connexion
│   ├── logout.php
│   ├── capteurs.php            # Suivi GPS + capteurs par véhicule
│   ├── ajouter_voiture.php     # Traitement ajout véhicule
│   ├── supprimer_reservation.php # Traitement suppression réservation
│   ├── styles.css
│   └── api/
│       ├── location.php        # Endpoint GPS  (Boron → serveur)
│       └── capteurs.php        # Endpoint capteurs (Boron → serveur)
│
├── firmware/
│   └── GPSandSensorData.ino    # Firmware Particle Boron
│
└── location_voiture.sqlite     # Base de données (non versionné)
```

---

## Base de données

```sql
voitures        -- Véhicules du parc (nom, image, prix_jour, places, conso, boite)
clients         -- Clients ayant réservé (nom, prenom, email, telephone)
reservations    -- Réservations (id_voiture, id_client, date_debut, date_fin)
administrateurs -- Comptes admin (username, password)
maintenances    -- Historique d'entretien
capteurs        -- Historique des mesures IoT
```

---

## API cliente

### Disponibilité d'un véhicule
```
GET /api/disponibilite.php?id_voiture=1
→ [{"from":"2026-07-10","to":"2026-07-15"}, ...]
```
Utilisé par Flatpickr pour griser les dates indisponibles.

### Créer une réservation
```
POST /api/reserver.php
Body : id_voiture, date_debut, date_fin, nom, prenom, email, telephone
→ {"success": true, "message": "..."}
```

---

## Firmware Particle Boron

Le fichier `firmware/GPSandSensorData.ino` est flashé sur une carte Particle Boron
embarquée dans chaque véhicule.

**Données envoyées :**
- Position GPS (module NMEA) — toutes les 10 secondes
- Capteurs analogiques (carburant, huile, batterie) — toutes les 5 secondes

**Configuration avant flashage (`firmware/GPSandSensorData.ino`) :**
```cpp
const char* id_voiture = "1";           // ← identifiant unique par véhicule
const char* serverName = "192.168.1.x"; // ← IP locale du serveur
```

**Broches utilisées :**

| Broche | Capteur |
|--------|---------|
| Pin 2 | Niveau carburant (analogique) |
| Pin 14 | Niveau huile moteur (analogique) |
| Pin 15 | Tension batterie (analogique) |
| RX=1 / TX=3 | Module GPS (Serial1) |

**APN configuré pour Telma Madagascar :** `telmanet`

### Endpoint GPS
```
GET /admin/api/location.php?id_voiture=1&latitude=-18.8958&longitude=47.5425
```

### Endpoint capteurs
```
GET /admin/api/capteurs.php?id_voiture=1&distance1=75&distance2=90&distance3=13
```

| Paramètre | Description | Unité |
|-----------|-------------|-------|
| distance1 | Niveau carburant | % |
| distance2 | Niveau huile moteur | % |
| distance3 | Tension batterie | V |

---

## Auteur

Elisa Randriamasinoro — L3 Génie des Systèmes Automatisés · ITA Antananarivo · 2025
