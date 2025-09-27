#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

// --- PINES ---
#define SS_PIN 5
#define RST_PIN 22
#define LED_VERDE 2
#define LED_ROJO 4

MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- VARIABLES GLOBALES ---
bool enrollModeActive = false;
unsigned long ledOnTime = 0; // Para controlar el tiempo que el LED permanece encendido
const long ledDuration = 1500; // El LED se mostrará por 1.5 segundos

void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_ROJO, LOW);
  
  delay(500);
  Serial.println("Sistema RFID y Panel Web listos (Lógica Centralizada).");
}
void loop() {
  // ESCUCHAR COMANDOS DEL PANEL WEB ===
  if (Serial.available() > 0) {
    String command = Serial.readStringUntil('\n');
    command.trim();

    // Comandos para encender/apagar LEDs directamente desde el panel
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
    } else if (command == "ACCESS_GRANTED") {
      digitalWrite(LED_VERDE, HIGH);
      digitalWrite(LED_ROJO, LOW);
      ledOnTime = millis(); // Inicia el temporizador para apagar el LED
      Serial.println("OK: Accionando LED verde.");
    } 
    else if (command == "ACCESS_DENIED") {
      digitalWrite(LED_ROJO, HIGH);
      digitalWrite(LED_VERDE, LOW);
      ledOnTime = millis(); // Inicia el temporizador para apagar el LED
      Serial.println("OK: Accionando LED rojo.");
    }
    // Comandos de modo de enrolamiento
    else if (command == "ENROLL_START") {
      enrollModeActive = true;
      Serial.println("OK: Modo de enrolamiento activado.");
    } else if (command == "ENROLL_STOP") {
      enrollModeActive = false;
      Serial.println("OK: Modo de enrolamiento desactivado.");
    } else {
      Serial.println("Error: Comando web no reconocido -> " + command);
    }
  }

  // === ESCANEAR TARJETAS RFID ===
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidScannedStr = getUIDString(mfrc522.uid.uidByte, mfrc522.uid.size);
    
    // Ya no se valida aquí. Simplemente se empaqueta y se envía.
    JsonDocument doc;
    doc["uid"] = uidScannedStr;
    doc["origen"] = "RFID";
    
    serializeJson(doc, Serial);
    Serial.println();
    
    // Pausa muy breve para evitar lecturas múltiples de la misma tarjeta
    delay(250); 
    
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
  }
  
  // Si el temporizador del LED está activo, comprueba si ya pasó el tiempo
  if (ledOnTime > 0 && millis() - ledOnTime > ledDuration) {
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_ROJO, LOW);
    ledOnTime = 0; // Resetea el temporizador
  }

  // Lógica de parpadeo para el modo de enrolamiento
  if (enrollModeActive) {
    // Parpadea los LEDs para indicar que está esperando una tarjeta para enrolar
    digitalWrite(LED_ROJO, millis() % 1000 < 500);
    digitalWrite(LED_VERDE, millis() % 1000 < 500);
  }
}

// Función auxiliar para leer UID
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