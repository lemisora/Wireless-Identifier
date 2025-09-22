#include <SPI.h>
#include <MFRC522.h>

#define SS_PIN 5
#define RST_PIN 22

#define LED_VERDE 2
#define LED_AMARILLO 4

MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- SECCIÓN DE UIDS AUTORIZADOS ---

// 1. Define cuántas tarjetas autorizadas tienes.
#define NUM_TARJETAS_AUTORIZADAS 2

// 2. Define la longitud MÁXIMA que puede tener un UID (las tarjetas suelen tener 4 o 7 bytes).
//    Usamos 7 para cubrir ambos casos.
#define LONGITUD_MAX_UID 7

// 3. Crea el arreglo 2D de UIDs.
//    Si un UID es más corto (ej. 4 bytes), rellena el resto con ceros.
byte UIDS_AUTORIZADOS[NUM_TARJETAS_AUTORIZADAS][LONGITUD_MAX_UID] = {
  {0xA6, 0x39, 0x8E, 0xF7, 0x00, 0x00, 0x00}, // UID de 4 bytes (rellenado con 0)
  {0x15, 0x85, 0x02, 0x53, 0x7F, 0x53, 0x00}  // UID de 7 bytes
};


void setup() {
  Serial.begin(115200);
  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_AMARILLO, OUTPUT);

  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARILLO, LOW);
  
  Serial.println("Sistema listo. Escanea una tarjeta RFID...");
}

void loop() {
  // Se verifica si hay una nueva tarjeta presente Y se lee su serie.
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50); // Pequeña pausa para no saturar el loop
    return;
  }

  Serial.print("UID escaneado: ");
  imprimirUID(mfrc522.uid.uidByte, mfrc522.uid.size);
  
  bool accesoPermitido = false; // Por defecto, el acceso está DENEGADO.

  // Recorremos CADA UNO de los UIDs autorizados para compararlos
  for (int i = 0; i < NUM_TARJETAS_AUTORIZADAS; i++) {
    // La función compararUID se encarga de la lógica compleja
    if (compararUID(mfrc522.uid.uidByte, mfrc522.uid.size, UIDS_AUTORIZADOS[i])) {
      accesoPermitido = true;
      break; // Si encontramos una coincidencia, no es necesario seguir buscando.
    }
  }

  // Se actúa según el resultado de la búsqueda
  if (accesoPermitido) {
    Serial.println(" >> ACCESO PERMITIDO");
    digitalWrite(LED_VERDE, HIGH);
    digitalWrite(LED_AMARILLO, LOW);
  } else {
    Serial.println(" >> ACCESO DENEGADO");
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_AMARILLO, HIGH);
  }

  delay(2000); // Mantenemos el LED encendido por 2 segundos
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARILLO, LOW);
  
  mfrc522.PICC_HaltA();      // Detiene la comunicación con la tarjeta actual
  mfrc522.PCD_StopCrypto1(); // Detiene la encriptación
}

/**
 * @brief Compara el UID escaneado con un UID autorizado.
 * @param uidScanned Puntero al array de bytes del UID escaneado.
 * @param sizeScanned Tamaño del UID escaneado.
 * @param uidAutorizado Puntero al array de bytes del UID autorizado de nuestra lista.
 * @return true si los UIDs son idénticos, false en caso contrario.
 */
bool compararUID(byte uidScanned[], byte sizeScanned, byte uidAutorizado[]) {
  // Compara byte por byte. Si algún byte es diferente, los UIDs no coinciden.
  for (byte i = 0; i < sizeScanned; i++) {
    if (uidScanned[i] != uidAutorizado[i]) {
      return false;
    }
  }
  // Si el bucle termina sin encontrar diferencias, los UIDs son iguales.
  return true;
}

/**
 * @brief Imprime el UID en el monitor serial en formato HEX.
 */
void imprimirUID(byte uid[], byte size) {
  for (byte i = 0; i < size; i++) {
    Serial.print(uid[i] < 0x10 ? " 0" : " ");
    Serial.print(uid[i], HEX);
  }
}