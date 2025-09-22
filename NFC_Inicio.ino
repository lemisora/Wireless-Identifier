#include <SPI.h>
#include <MFRC522.h>
// Se recomienda instalar esta librería desde el gestor de Arduino para manejar JSON
// Busca e instala "ArduinoJson" de Benoit Blanchon
#include <ArduinoJson.h>

#define SS_PIN 5
#define RST_PIN 22

#define LED_VERDE 2
#define LED_AMARILLO 4

MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- SECCIÓN DE UIDS AUTORIZADOS ---
#define NUM_TARJETAS_AUTORIZADAS 2
#define LONGITUD_MAX_UID 7
byte UIDS_AUTORIZADOS[NUM_TARJETAS_AUTORIZADAS][LONGITUD_MAX_UID] = {
  {0xA6, 0x39, 0x8E, 0xF7, 0x00, 0x00, 0x00},
  {0x15, 0x89, 0x01, 0x53, 0x7F, 0x53, 0x00}
};

void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_AMARILLO, OUTPUT);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARILLO, LOW);
  
  // <-- CAMBIO: Enviar un JSON de estado inicial
  Serial.println("{\"status\":\"LISTO\", \"uid\":\"-\"}");
}

void loop() {
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // <-- CAMBIO: Obtener el UID como un String
  String uidScannedStr = getUIDString(mfrc522.uid.uidByte, mfrc522.uid.size);
  
  bool accesoPermitido = false;
  for (int i = 0; i < NUM_TARJETAS_AUTORIZADAS; i++) {
    if (compararUID(mfrc522.uid.uidByte, mfrc522.uid.size, UIDS_AUTORIZADOS[i])) {
      accesoPermitido = true;
      break;
    }
  }

  // <-- CAMBIO: Construir y enviar el JSON con el resultado
  JsonDocument doc; // Usa la librería ArduinoJson para seguridad y eficiencia
  doc["uid"] = uidScannedStr;

  if (accesoPermitido) {
    doc["status"] = "PERMITIDO";
    digitalWrite(LED_VERDE, HIGH);
    digitalWrite(LED_AMARILLO, LOW);
  } else {
    doc["status"] = "DENEGADO";
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_AMARILLO, HIGH);
  }
  
  serializeJson(doc, Serial);
  Serial.println(); // Envía un salto de línea para que JS sepa que el mensaje terminó

  delay(2000);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARILLO, LOW);
  
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
}

/**
 * @brief Convierte un array de bytes de UID a un String en formato HEX.
 * @return Un String como "A6:39:8E:F7".
 */
String getUIDString(byte uid[], byte size) {
  String result = "";
  for (byte i = 0; i < size; i++) {
    if (uid[i] < 0x10) {
      result += "0";
    }
    result += String(uid[i], HEX);
    if (i < size - 1) {
      result += ":";
    }
  }
  result.toUpperCase();
  return result;
}

bool compararUID(byte uidScanned[], byte sizeScanned, byte uidAutorizado[]) {
  for (byte i = 0; i < sizeScanned; i++) {
    if (uidScanned[i] != uidAutorizado[i]) {
      return false;
    }
  }
  return true;
}

// La función imprimirUID ya no es necesaria, la reemplaza getUIDString