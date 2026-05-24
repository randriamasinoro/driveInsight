#include <Particle.h>
#include <HttpClient.h>
#include <TinyGPS++.h>

// Déclaration des objets GPS et Serial
TinyGPSPlus gps;
const int RX_PIN = 1; // RX du Boron
const int TX_PIN = 3; // TX du Boron

// Broches des capteurs
const int SENSOR_PIN_FUEL    = 2;  // Capteur niveau carburant
const int SENSOR_PIN_OIL     = 14; // Capteur niveau huile
const int SENSOR_PIN_BATTERY = 15; // Capteur tension batterie

// URL du serveur
const char* serverName = "192.168.1.101";
const char* id_voiture = "1"; // ← changer pour chaque carte embarquée

HttpClient http;
http_request_t request;
http_response_t response;
http_header_t headers[] = {
    { "Content-Type", "application/x-www-form-urlencoded" },
    { NULL, NULL }
};

// Temporisateurs
unsigned long previousMillisGPS     = 0;
unsigned long previousMillisSensors = 0;
const long intervalGPS     = 10000; // 10 secondes
const long intervalSensors = 5000;  // 5 secondes

void setup() {
    pinMode(SENSOR_PIN_FUEL,    INPUT);
    pinMode(SENSOR_PIN_OIL,     INPUT);
    pinMode(SENSOR_PIN_BATTERY, INPUT);
    Serial.begin(115200);
    Serial1.begin(9600, SERIAL_8N1, RX_PIN, TX_PIN);

    // Bug 1 corrigé : APN configuré AVANT la connexion
    Particle.cellular.setAPN("telmanet");
    Particle.cellular.setName("Telma Internet");

    Particle.connect();
    while (!Particle.connected()) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("Connecté au réseau cellulaire");
}

void loop() {
    unsigned long currentMillis = millis();

    while (Serial1.available() > 0) {
        gps.encode(Serial1.read());
    }

    if (currentMillis - previousMillisGPS >= intervalGPS) {
        previousMillisGPS = currentMillis;
        envoyerDonneesGPS();
    }

    if (currentMillis - previousMillisSensors >= intervalSensors) {
        previousMillisSensors = currentMillis;
        envoyerDonneesCapteurs();
    }
}

void envoyerDonneesGPS() {
    if (gps.location.isUpdated()) {
        float latitude  = gps.location.lat();
        float longitude = gps.location.lng();

        String gpsPath = "/admin/api/location.php?id_voiture=" + String(id_voiture)
                       + "&latitude="  + String(latitude,  6)
                       + "&longitude=" + String(longitude, 6);

        request.hostname = serverName;
        request.port     = 8080;
        request.path     = gpsPath;

        http.get(request, response, headers);

        if (response.status == 200) {
            Serial.println("Données GPS envoyées avec succès !");
        } else {
            Serial.println("Erreur lors de l'envoi des données GPS");
        }
    }
}

void envoyerDonneesCapteurs() {
    // Bug 4 corrigé : mapping analogRead (0–4095) vers les bonnes unités
    int distance1 = map(analogRead(SENSOR_PIN_FUEL),    0, 4095, 0, 100); // carburant en %
    int distance2 = map(analogRead(SENSOR_PIN_OIL),     0, 4095, 0, 100); // huile en %
    int distance3 = map(analogRead(SENSOR_PIN_BATTERY), 0, 4095, 0, 15);  // tension en V (0–15V)

    // Bug 3 corrigé : noms des paramètres alignés avec l'API (distance1/2/3)
    String sensorPath = "/admin/api/capteurs.php?id_voiture=" + String(id_voiture)
                      + "&distance1=" + String(distance1)
                      + "&distance2=" + String(distance2)
                      + "&distance3=" + String(distance3);

    request.hostname = serverName;
    request.port     = 8080;
    request.path     = sensorPath;

    http.get(request, response, headers);

    // Bug 2 corrigé : response.status (pas response.status_code)
    if (response.status == 200) {
        Serial.println("Données capteurs envoyées avec succès !");
    } else {
        Serial.println("Erreur lors de l'envoi des données capteurs");
    }
}
