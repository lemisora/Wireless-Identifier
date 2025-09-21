#include <WiFi.h>
#include <ArduinoJson.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

// 🔧 Reemplaza con tus credenciales de Wi-Fi
const char* ssid = "Tu_SSID";
const char* password = "Tu_Password";

// 🌐 Reemplaza con la URL de tu servidor remoto o endpoint de la base de datos (Ejemplo de Firebase o una API REST)
const char* serverUrl = "http://api.tu-servidor.com/data";

// 💡 Pines de conexión del módulo RFID
#define RST_PIN 4  // Pin de reset del RC522
#define SS_PIN 5   // Pin SDA (Slave Select) del RC522

MFRC522 mfrc522(SS_PIN, RST_PIN);  // Crea la instancia del módulo

// 💡 Pin del LED que quieres controlar
const int ledPin = 2; 

void setup() {
  Serial.begin(115200); 
  pinMode(ledPin, OUTPUT);
  digitalWrite(ledPin, LOW);

  // Inicializa el SPI y el módulo RFID
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("Lector RFID inicializado. Acércale una tarjeta...");

  // Intenta conectar a la red Wi-Fi
  WiFi.begin(ssid, password);
  Serial.println("Conectando a Wi-Fi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }
  Serial.println("\n✅ Conectado a Wi-Fi.");
  Serial.print("IP del ESP32: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  // Comprueba si hay datos disponibles para leer desde el puerto serial
  if (Serial.available() > 0) {
    String command = Serial.readStringUntil('\n');
    command.trim();

    // Procesa el comando recibido
    if (command == "LED_ON") {
      digitalWrite(ledPin, HIGH);
      Serial.println("LED Encendido.");
    } else if (command == "LED_OFF") {
      digitalWrite(ledPin, LOW);
      Serial.println("LED Apagado.");
    } else if (command == "GET_STATUS") {
      Serial.println(digitalRead(ledPin) == HIGH ? "Estado: ON" : "Estado: OFF");
    } else if (command.startsWith("SAVE_USER:")) {
      String username = command.substring(10);
      Serial.print("Comando de guardado recibido. Usuario: ");
      Serial.println(username);
      sendDataToServer("username", username);
    } else {
      Serial.print("Comando no reconocido: ");
      Serial.println(command);
    }
  }

  // Comprueba si hay una tarjeta RFID nueva
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidString = "";
    Serial.print("UID de la tarjeta: ");
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      if(mfrc522.uid.uidByte[i] < 0x10) {
        uidString += "0";
      }
      uidString += String(mfrc522.uid.uidByte[i], HEX);
    }
    uidString.toUpperCase();
    Serial.println(uidString);

    // Envía el UID a través del puerto serial para que la web lo capture
    String webMessage = "RFID_UID:" + uidString;
    Serial.println(webMessage);

    // Envía los datos al servidor remoto
    sendDataToServer("rfid_uid", uidString);

    mfrc522.PICC_HaltA(); // Detiene la lectura para evitar lecturas duplicadas
  }
}

// 🌐 Función para enviar datos a un servidor remoto
void sendDataToServer(String key, String value) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc[key] = value;
    String jsonOutput;
    serializeJson(doc, jsonOutput);

    int httpResponseCode = http.POST(jsonOutput);

    if (httpResponseCode > 0) {
      Serial.print("Código de respuesta del servidor: ");
      Serial.println(httpResponseCode);
      String payload = http.getString();
      Serial.print("Respuesta del servidor: ");
      Serial.println(payload);
    } else {
      Serial.print("Error en la petición HTTP: ");
      Serial.println(httpResponseCode);
    }
    http.end();
  } else {
    Serial.println("Error: No hay conexión a Wi-Fi.");
  }
}