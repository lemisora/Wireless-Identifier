#include "BluetoothSerial.h"

BluetoothSerial SerialBT;
const int ledPin = 2; // Pin del LED

void setup() {
  pinMode(ledPin, OUTPUT);
  Serial.begin(115200);
  SerialBT.begin("ESP32_LED_Control");
  Serial.println("Listo para recibir comandos.");
}


void loop() {
  if (SerialBT.available()) {
    char command = SerialBT.read();
    if (command == '1') {
      digitalWrite(ledPin, HIGH); // Encender LED
      SerialBT.println("LED encendido");
    } else if (command == '0') {
      digitalWrite(ledPin, LOW); // Apagar LED
      SerialBT.println("LED apagado");
    }
  }
}