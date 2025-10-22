#include <Arduino.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLE2902.h>

// UUIDs personalizadas para el servicio y la característica
#define SERVICE_UUID        "4fafc201-1fb5-459e-8fcc-c5c9c331914b"
#define CHARACTERISTIC_UUID "beb5483e-36e1-4688-b7f5-ea07361b26a8"

const int LED_VERDE_PIN = 2;

BLECharacteristic *pCharacteristic;
bool deviceConnected = false;
String incomingBLE = "";

// --- Callbacks para conexión y desconexión ---
class MyServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    Serial.println("Dispositivo conectado por BLE");
    deviceConnected = true;
  }

  void onDisconnect(BLEServer* pServer) {
    Serial.println("Dispositivo desconectado");
    deviceConnected = false;
    // Reanudar publicidad para reconexión
    pServer->getAdvertising()->start();
  }
};

// --- Callbacks para lectura/escritura en la característica ---
class MyCallbacks : public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic *pCharacteristic) {
    String rawValue = pCharacteristic->getValue();
    String cleanValue = "";

    // Filtramos caracteres imprimibles
    for (size_t i = 0; i < rawValue.length(); i++) {
      char c = rawValue[i];
      if (c >= 32 && c <= 126) {
        cleanValue += c;
      }
    }

    if (cleanValue.length() > 0) {
      Serial.print("BLE → Serial: ");
      Serial.println(cleanValue);
      incomingBLE = cleanValue;
    }
  }
};

void setup() {
  // --- Inicialización de Pines de los LEDs ---
  pinMode(LED_VERDE_PIN, OUTPUT);

  Serial.begin(115200);
  Serial.println("Iniciando Terminal BLE...");

  // Inicializar BLE
  BLEDevice::init("ESP32_Nix_BLE_Terminal");

  BLEServer *pServer = BLEDevice::createServer();
  pServer->setCallbacks(new MyServerCallbacks());

  // Crear servicio BLE
  BLEService *pService = pServer->createService(SERVICE_UUID);

  // Crear característica BLE
  pCharacteristic = pService->createCharacteristic(
                      CHARACTERISTIC_UUID,
                      BLECharacteristic::PROPERTY_READ   |
                      BLECharacteristic::PROPERTY_WRITE  |
                      BLECharacteristic::PROPERTY_NOTIFY | // Necesario para enviar Serial -> BLE
                      BLECharacteristic::PROPERTY_INDICATE
                    );

  pCharacteristic->setCallbacks(new MyCallbacks());
  pCharacteristic->addDescriptor(new BLE2902());

  pService->start();

  // Comenzar a anunciar el servicio BLE
  BLEAdvertising *pAdvertising = BLEDevice::getAdvertising();
  pAdvertising->addServiceUUID(SERVICE_UUID);
  pAdvertising->start();

  Serial.println("Esperando conexión BLE...");
}

void loop() {
  // --- Serial → BLE ---
  if (deviceConnected && Serial.available()) {
    String outgoing = "";
    while (Serial.available()) {
      outgoing += (char)Serial.read();
    }
    outgoing.trim(); // elimina \r\n al final

    if (outgoing.length() > 0) {
      Serial.print("Serial → BLE: ");
      Serial.println(outgoing);
      
      // EL PROBLEMA CASI SIEMPRE ES ESTE PASO:
      // Para que pCharacteristic->notify() funcione, el cliente
      // BLE (la app en tu celular) DEBE ESTAR SUSCRITO (habilitar Notificaciones).
      pCharacteristic->setValue(outgoing.c_str());
      pCharacteristic->notify(); // Envía al celular
    }
  }

  // --- BLE → Serial (Recepción de comandos) ---
if (incomingBLE.length() > 0) {
  Serial.print("Comando recibido: ");
  Serial.println(incomingBLE);

  // Comparamos el comando recibido (ignorando mayúsculas/minúsculas)
  if (incomingBLE.equalsIgnoreCase("on")) {
    digitalWrite(LED_VERDE_PIN, HIGH); // Encender LED
    Serial.println("LED Encendido");
  } else if (incomingBLE.equalsIgnoreCase("off")) {
    digitalWrite(LED_VERDE_PIN, LOW);  // Apagar LED
    Serial.println("LED Apagado");
  }

  // Limpiamos la variable para no procesar el mismo comando otra vez
  incomingBLE = "";
}

  delay(10);
}