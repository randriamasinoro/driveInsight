#!/bin/bash
cd "$(dirname "$0")"

echo "============================================"
echo "  Serveur Location de Voitures - Park DTTS  "
echo "============================================"
echo

# Détecter PHP
PHP_EXE=""
for candidate in /usr/bin/php /usr/local/bin/php; do
    if [ -x "$candidate" ]; then
        PHP_EXE="$candidate"
        break
    fi
done
if [ -z "$PHP_EXE" ]; then
    PHP_EXE="$(which php 2>/dev/null)"
fi

if [ -z "$PHP_EXE" ]; then
    echo "ERREUR : PHP introuvable."
    echo "Installez PHP avec : sudo apt install php php-sqlite3"
    exit 1
fi

# Vérifier l'extension PDO SQLite
if ! "$PHP_EXE" -m 2>/dev/null | grep -qi pdo_sqlite; then
    echo "AVERTISSEMENT : extension pdo_sqlite manquante."
    echo "Installez-la avec : sudo apt install php-sqlite3"
    echo
fi

# Créer les dossiers nécessaires
mkdir -p admin/data

# Initialiser la base de données si absente ou vide
ADMIN_COUNT=$("$PHP_EXE" -r "try { \$p = new PDO('sqlite:location_voiture.sqlite'); echo \$p->query('SELECT COUNT(*) FROM administrateurs')->fetchColumn(); } catch(Exception \$e) { echo 0; }" 2>/dev/null)
if [ ! -f "location_voiture.sqlite" ] || [ "$ADMIN_COUNT" = "0" ]; then
    echo "Initialisation de la base de données..."
    "$PHP_EXE" init_db.php > /dev/null
    echo "Base de données créée."
    echo
fi

echo "Serveur démarré sur : http://localhost:8080"
echo "Appuyez sur Ctrl+C pour arrêter."
echo

# Ouvrir le navigateur (non bloquant)
for browser in xdg-open gnome-open firefox chromium-browser; do
    if command -v "$browser" > /dev/null 2>&1; then
        "$browser" "http://localhost:8080" &
        break
    fi
done

"$PHP_EXE" -S localhost:8080
