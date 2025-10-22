/*
 Control de LEDs con Mando a Distancia IR en ESP32
 - LED Verde: Pin D23
 - LED Rojo: Pin D22
 - LED Amarillo: Pin D21
 - Receptor IR: Pin D15
*/

#include <IRremote.hpp>

// --- Definición de Pines ---
const int RECEPTOR_IR_PIN = 15;
const int LED_VERDE_PIN = 23;
const int LED_ROJO_PIN = 22;
const int LED_AMARILLO_PIN = 21;

// --- Códigos de tu control remoto (ajústalos según tus pruebas) ---
const uint32_t BOTON_1 = 0xF30CFF00;  // LED Verde ON
const uint32_t BOTON_2 = 0xE718FF00;  // LED Verde OFF
const uint32_t BOTON_4 = 0xF708FF00;    // LED Rojo ON
const uint32_t BOTON_5 = 0xE31CFF00;    // LED Rojo OFF
const uint32_t BOTON_7 = 0xBD42FF00;    // LED Amarillo ON
const uint32_t BOTON_8 = 0xAD52FF00;    // LED Amarillo OFF

void setup() {
  Serial.begin(115200);

  // --- Inicialización de Pines de los LEDs ---
  pinMode(LED_VERDE_PIN, OUTPUT);
  pinMode(LED_ROJO_PIN, OUTPUT);
  pinMode(LED_AMARILLO_PIN, OUTPUT);

  // Apagar todos los LEDs al inicio
  digitalWrite(LED_VERDE_PIN, LOW);
  digitalWrite(LED_ROJO_PIN, LOW);
  digitalWrite(LED_AMARILLO_PIN, LOW);

  // Iniciar el receptor IR
  IrReceiver.begin(RECEPTOR_IR_PIN, DISABLE_LED_FEEDBACK);

  Serial.println("=================================");
  Serial.println("Control de LEDs con IR iniciado");
  Serial.println("Esperando señales...");
  Serial.println("=================================");
}

void loop() {
  if (IrReceiver.decode()) {

    // Ignorar señales de repetición
    if (IrReceiver.decodedIRData.flags & IRDATA_FLAGS_IS_REPEAT) {
      IrReceiver.resume();
      return;
    }

    // Obtener el código recibido
    uint32_t codigo = IrReceiver.decodedIRData.decodedRawData;

    Serial.print("Código HEX recibido: 0x");
    Serial.println(codigo, HEX);

    // Control de LEDs según el código recibido
    switch (codigo) {
      // --- Control del LED Verde ---
      case BOTON_1:
        digitalWrite(LED_VERDE_PIN, HIGH);
        Serial.println("LED Verde: ENCENDIDO");
        break;

      case BOTON_2:
        digitalWrite(LED_VERDE_PIN, LOW);
        Serial.println("LED Verde: APAGADO");
        break;

      // --- Control del LED Rojo ---
      case BOTON_4:
        digitalWrite(LED_ROJO_PIN, HIGH);
        Serial.println("LED Rojo: ENCENDIDO");
        break;

      case BOTON_5:
        digitalWrite(LED_ROJO_PIN, LOW);
        Serial.println("LED Rojo: APAGADO");
        break;

      // --- Control del LED Amarillo ---
      case BOTON_7:
        digitalWrite(LED_AMARILLO_PIN, HIGH);
        Serial.println("LED Amarillo: ENCENDIDO");
        break;

      case BOTON_8:
        digitalWrite(LED_AMARILLO_PIN, LOW);
        Serial.println("LED Amarillo: APAGADO");
        break;

      default:
        Serial.println("Botón no asignado");
        break;
    }

    IrReceiver.resume(); // Preparar para la siguiente señal
  }
}

