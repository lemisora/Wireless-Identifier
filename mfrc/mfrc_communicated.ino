#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

// --- PINES Y CONFIGURACIÓN RFID ---
#define SS_PIN 5
#define RST_PIN 22
#define LED_VERDE 2
#define LED_ROJO 4 // Este LED será usado por el panel web y la lógica RFID.

MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- SECCIÓN DE UIDS AUTORIZADOS ---
#define NUM_TARJETAS_AUTORIZADAS 2
#define LONGITUD_MAX_UID 7
byte UIDS_AUTORIZADOS[NUM_TARJETAS_AUTORIZADAS][LONGITUD_MAX_UID] = {
  {0xA6, 0x39, 0x8E, 0xF7, 0x00, 0x00, 0x00},
  {0x00, 0x05, 0x00, 0x03, 0x7A, 0x53, 0x00}
};

// --- VARIABLES GLOBALES PARA EL PANEL WEB ---
String savedUser = "Nadie";

void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_ROJO, LOW);
  
  delay(500);
  Serial.println("Sistema RFID y Panel Web listos.");
}

void loop() {
  // === PARTE 1: ESCUCHAR COMANDOS DEL PANEL WEB ===
  if (Serial.available() > 0) {
    String command = Serial.readStringUntil('\n');
    command.trim();

    if (command == "LED_RED_ON") {
      digitalWrite(LED_ROJO, HIGH);
      Serial.println("OK: LED Rojo Encendido.");
    } else if (command == "LED_RED_OFF") {
      digitalWrite(LED_ROJO, LOW);
      Serial.println("OK: LED Rojo Apagado.");
    } else if (command == "LED_GREEN_ON") {
      digitalWrite(LED_VERDE, HIGH);
      Serial.println("OK: LED Verde Encendido.");
    } else if (command == "LED_GREEN_OFF") {
      digitalWrite(LED_VERDE, LOW);
      Serial.println("OK: LED Verde Apagado.");
    } else if (command == "GET_STATUS") {
      bool ledState = digitalRead(LED_ROJO);
      String statusMessage = "Estado del sistema: OK | LED Rojo: ";
      statusMessage += (ledState ? "ENCENDIDO" : "APAGADO");
      ledState = digitalRead(LED_VERDE);
      statusMessage += "\n OK | LED Verde: ";
      statusMessage += (ledState ? "ENCENDIDO" : "APAGADO");
      statusMessage += " | Usuario: " + savedUser;
      Serial.println(statusMessage);
    } 
    else if (command.startsWith("SAVE_USER:")) {
      savedUser = command.substring(10);
      Serial.println("OK: Usuario '" + savedUser + "' guardado.");
    } 
    else {
      Serial.println("Error: Comando web no reconocido -> " + command);
    }
  }

  // === PARTE 2: ESCANEAR TARJETAS RFID ===
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidScannedStr = getUIDString(mfrc522.uid.uidByte, mfrc522.uid.size);
    bool accesoPermitido = false;

    for (int i = 0; i < NUM_TARJETAS_AUTORIZADAS; i++) {
      if (compararUID(mfrc522.uid.uidByte, mfrc522.uid.size, UIDS_AUTORIZADOS[i])) {
        accesoPermitido = true;
        break;
      }
    }

    JsonDocument doc;
    doc["uid"] = uidScannedStr;
    doc["origen"] = "RFID"; // Añadimos un campo para diferenciarlo de las respuestas del panel

    if (accesoPermitido) {
      doc["status"] = "PERMITIDO";
      digitalWrite(LED_VERDE, HIGH);
      digitalWrite(LED_ROJO, LOW);
    } else {
      doc["status"] = "DENEGADO";
      digitalWrite(LED_VERDE, LOW);
      digitalWrite(LED_ROJO, HIGH);
    }
    
    serializeJson(doc, Serial);
    Serial.println();

    // Pequeña pausa para mostrar el resultado en los LEDs.
    // Reducido a 500ms para mantener el panel web responsivo.
    delay(500); 
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_ROJO, LOW);
    
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
  }
}

// --- FUNCIONES AUXILIARES DE RFID (SIN CAMBIOS) ---
String getUIDString(byte uid[], byte size) {
  String result = "";
  for (byte i = 0; i < size; i++) {
    if (uid[i] < 0x10) result += "0";
    result += String(uid[i], HEX);
    if (i < size - 1) result += ":";
  }
  result.toUpperCase();
  return result;
}

bool compararUID(byte uidScanned[], byte sizeScanned, byte uidAutorizado[]) {
  for (byte i = 0; i < sizeScanned; i++) {
    if (uidScanned[i] != uidAutorizado[i]) return false;
  }
  return true;
}