#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>
#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEServer.h>
#include <BLE2902.h>

// --- PINES ---
#define SS_PIN    5
#define RST_PIN   22
#define LED_VERDE 2
#define LED_ROJO  4

// --- OBJETOS ---
MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- CONFIGURACIÓN BLE ---
#define SERVICE_UUID        "4fafc201-1fb5-459e-8fcc-c5c9c331914b"
#define CHARACTERISTIC_UUID_RX "beb5483e-36e1-4688-b7f5-ea07361b26a8"
#define CHARACTERISTIC_UUID_TX "c33f2111-8a00-4240-a0b8-a73383a1cb27"

BLECharacteristic *pCharacteristicTX;
BLECharacteristic *pCharacteristicRX;
bool deviceConnected = false;

// --- VARIABLES GLOBALES ---
bool enrollModeActive = false;
unsigned long ledOnTime = 0;
const long ledDuration = 1500;

// Declaración anticipada (prototipo) de la función
void handleCommand(String command);

// --- CLASES DE CALLBACKS BLE ---
class MyServerCallbacks: public BLEServerCallbacks {
    void onConnect(BLEServer* pServer) {
      deviceConnected = true;
      Serial.println("Dispositivo conectado.");
    }
    void onDisconnect(BLEServer* pServer) {
      deviceConnected = false;
      Serial.println("Dispositivo desconectado.");
      BLEDevice::startAdvertising(); 
    }
};

class MyCallbacks: public BLECharacteristicCallbacks {
    void onWrite(BLECharacteristic *pCharacteristic) {
      // Recibimos directamente como un String de Arduino para coincidir con el tipo devuelto
      String command = pCharacteristic->getValue();

      if (command.length() > 0) {
        command.trim();
        
        Serial.print("Comando recibido por BLE: ");
        Serial.println(command);
        handleCommand(command);
      }
    }
};

// --- SETUP ---
void setup() {
  Serial.begin(115200);
  Serial.println("Iniciando sistema...");

  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_ROJO, LOW);

  BLEDevice::init("ESP32_Lector_RFID_BLE");
  BLEServer *pServer = BLEDevice::createServer();
  pServer->setCallbacks(new MyServerCallbacks());
  BLEService *pService = pServer->createService(SERVICE_UUID);
  
  pCharacteristicTX = pService->createCharacteristic(
                      CHARACTERISTIC_UUID_TX,
                      BLECharacteristic::PROPERTY_NOTIFY
                    );
  pCharacteristicTX->addDescriptor(new BLE2902());

  pCharacteristicRX = pService->createCharacteristic(
                      CHARACTERISTIC_UUID_RX,
                      BLECharacteristic::PROPERTY_WRITE
                    );
  pCharacteristicRX->setCallbacks(new MyCallbacks());

  pService->start();
  
  BLEAdvertising *pAdvertising = BLEDevice::getAdvertising();
  pAdvertising->addServiceUUID(SERVICE_UUID);
  pAdvertising->setScanResponse(true);
  BLEDevice::startAdvertising();
  
  Serial.println("Servidor BLE iniciado. Esperando conexión...");
}

// --- LOOP PRINCIPAL ---
void loop() {
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidScannedStr = getUIDString(mfrc522.uid.uidByte, mfrc522.uid.size);
    JsonDocument doc;
    doc["uid"] = uidScannedStr;
    doc["origen"] = "RFID";
    String jsonOutput;
    serializeJson(doc, jsonOutput);

    if (deviceConnected) {
        pCharacteristicTX->setValue(jsonOutput.c_str());
        pCharacteristicTX->notify();
        Serial.print("UID enviado por BLE: ");
        Serial.println(jsonOutput);
    }
    
    delay(250);
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
  }

  if (ledOnTime > 0 && millis() - ledOnTime > ledDuration) {
    digitalWrite(LED_VERDE, LOW);
    digitalWrite(LED_ROJO, LOW);
    ledOnTime = 0;
  }
  if (enrollModeActive) {
    digitalWrite(LED_ROJO, millis() % 1000 < 500);
    digitalWrite(LED_VERDE, millis() % 1000 < 500);
  }
}

// --- FUNCIONES AUXILIARES ---
void handleCommand(String command) {
    if (command == "LED_RED_ON") {
        digitalWrite(LED_ROJO, HIGH);
    } else if (command == "LED_RED_OFF") {
        digitalWrite(LED_ROJO, LOW);
    } else if (command == "LED_GREEN_ON") {
        digitalWrite(LED_VERDE, HIGH);
    } else if (command == "LED_GREEN_OFF") {
        digitalWrite(LED_VERDE, LOW);
    } else if (command == "ACCESS_GRANTED") {
        digitalWrite(LED_VERDE, HIGH);
        digitalWrite(LED_ROJO, LOW);
        ledOnTime = millis();
    } else if (command == "ACCESS_DENIED") {
        digitalWrite(LED_ROJO, HIGH);
        digitalWrite(LED_VERDE, LOW);
        ledOnTime = millis();
    } else if (command == "ENROLL_START") {
        enrollModeActive = true;
    } else if (command == "ENROLL_STOP") {
        enrollModeActive = false;
        digitalWrite(LED_ROJO, LOW);
        digitalWrite(LED_VERDE, LOW);
    } else {
        Serial.println("Comando BLE no reconocido.");
    }
}

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