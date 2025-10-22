/*
  Control de Foco con ESP32 y Bluetooth Low Energy (BLE)

  Este código crea un servidor BLE que expone una característica
  a la que un cliente (como un celular) puede escribir.

  - Escribir "on" en la característica enciende el relevador.
  - Escribir "off" en la característica apaga el relevador.
*/

// 1. Incluir las librerías necesarias para BLE
#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEServer.h>

// 2. Definir el pin del relevador
const int relayPin = 13; // Usando el pin 13 que tú definiste

// 3. UUIDs únicos para el servicio y la característica
#define SERVICE_UUID        "4fafc201-1fb5-459e-8fcc-c5c9c331914b"
#define CHARACTERISTIC_UUID "beb5483e-36e1-4688-b7f5-ea07361b26a8"

// Variable para saber si un dispositivo está conectado
bool deviceConnected = false;

// 4. Clase de Callbacks para la Característica
// Esta clase contiene el código que se ejecuta cuando un cliente escribe un valor.
class MyCharacteristicCallbacks: public BLECharacteristicCallbacks {
    void onWrite(BLECharacteristic *pCharacteristic) {
      // Obtener el valor enviado por el cliente
      String value = pCharacteristic->getValue();
      
      // Asegurarse de que el valor no esté vacío
      if (value.length() > 0) {
        Serial.print("Comando recibido: ");
        Serial.println(value.c_str());

        // Procesar el comando
        if (value == "on") {
          Serial.println("Encendiendo el foco...");
          digitalWrite(relayPin, LOW); // Activa el relevador (lógica invertida)
        }
        else if (value == "off") {
          Serial.println("Apagando el foco...");
          digitalWrite(relayPin, HIGH); // Desactiva el relevador
        }
        else {
          Serial.println("Comando no reconocido.");
        }
      }
    }
};

// Clase para manejar eventos de conexión y desconexión del servidor
class MyServerCallbacks: public BLEServerCallbacks {
    void onConnect(BLEServer* pServer) {
      deviceConnected = true;
      Serial.println("Dispositivo conectado");
    }

    void onDisconnect(BLEServer* pServer) {
      deviceConnected = false;
      Serial.println("Dispositivo desconectado");
      // Reinicia el 'advertising' para que otros dispositivos puedan encontrarlo
      pServer->getAdvertising()->start(); 
    }
};


void setup() {
  Serial.begin(115200);
  Serial.println("Iniciando ESP32 como servidor BLE...");

  // Configurar el pin del relevador como salida y apagarlo al inicio
  pinMode(relayPin, OUTPUT);
  digitalWrite(relayPin, HIGH);

  // 5. Iniciar y configurar el dispositivo BLE
  BLEDevice::init("ESP32 Foco");
  
  // Crear el servidor BLE
  BLEServer *pServer = BLEDevice::createServer();
  pServer->setCallbacks(new MyServerCallbacks());

  // Crear el servicio BLE
  BLEService *pService = pServer->createService(SERVICE_UUID);

  // Crear la característica BLE
  BLECharacteristic *pCharacteristic = pService->createCharacteristic(
                                         CHARACTERISTIC_UUID,
                                         BLECharacteristic::PROPERTY_WRITE
                                       );
  
  // Asignar los callbacks a la característica
  pCharacteristic->setCallbacks(new MyCharacteristicCallbacks());

  // Iniciar el servicio
  pService->start();

  // 6. Empezar a "anunciarse" (advertising)
  // Esto permite que otros dispositivos BLE encuentren el ESP32
  BLEAdvertising *pAdvertising = BLEDevice::getAdvertising();
  pAdvertising->addServiceUUID(SERVICE_UUID);
  pAdvertising->setScanResponse(true);
  pAdvertising->setMinPreferred(0x06);
  pAdvertising->setMinPreferred(0x12);
  BLEDevice::startAdvertising();
  
  Serial.println("Servidor BLE iniciado. Esperando clientes...");
}

void loop() {
  delay(2000);
}