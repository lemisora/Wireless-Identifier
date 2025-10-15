document.addEventListener('DOMContentLoaded', () => {
    // --- REFERENCIAS A ELEMENTOS DEL DOM ---
    const connectSerialButton = document.getElementById('connect-serial-btn');
    const connectBtButton = document.getElementById('connect-bt-btn');
    const disconnectButton = document.getElementById('disconnect-btn');
    const output = document.getElementById('output');
    const usernameInput = document.getElementById('usernameInput');
    const themeToggle = document.getElementById('theme-toggle');

    // --- VARIABLES GLOBALES Y DE ESTADO ---
    let modoAsignacion = {
        activo: false,
        userId: null,
        userName: null
    };

    // ---  UUIDs PARA LA CONEXIÓN BLE ---
    // Deben coincidir EXACTAMENTE con los del código del ESP32
    const BLE_SERVICE_UUID = "4fafc201-1fb5-459e-8fcc-c5c9c331914b";
    const BLE_CHAR_UUID_RX = "beb5483e-36e1-4688-b7f5-ea07361b26a8"; // Web -> ESP32 (Escribir)
    const BLE_CHAR_UUID_TX = "c33f2111-8a00-4240-a0b8-a73383a1cb27"; // ESP32 -> Web (Notificar)

    // Objeto para manejar la conexión actual (abstracción)
    const connectionHandler = {
        type: null,
        device: null,
        writer: null,
        reader: null,
        isConnected: false,

        // Método genérico para enviar datos
        send: async function(command) {
            if (!this.isConnected || !this.writer) {
                console.error("No se puede enviar el comando, no hay conexión activa.");
                return;
            }
            // Para BLE, no se necesita el salto de línea, pero no afecta.
            // Para Serial, es indispensable. Lo dejamos por compatibilidad.
            const dataToSend = command + '\n';

            try {
                if (this.type === 'serial') {
                    await this.writer.write(dataToSend);
                } else if (this.type === 'bluetooth') {
                    const textEncoder = new TextEncoder();
                    // Usamos writeValue sin el \n para BLE, aunque el ESP32 lo ignora.
                    await this.writer.writeValue(textEncoder.encode(command));
                }
            } catch (error) {
                logOutput(`❌ Error al enviar comando: ${error.message}`);
                this.disconnect();
            }
        },

        // Método genérico para desconectar
        disconnect: async function() {
            if (!this.isConnected) return;

            try {
                if (this.type === 'serial') {
                    if (this.reader) { await this.reader.cancel(); this.reader.releaseLock(); }
                    if (this.writer) { await this.writer.close(); this.writer.releaseLock(); }
                    if (this.device) { await this.device.close(); }
                } else if (this.type === 'bluetooth') {
                    if (this.device && this.device.gatt.connected) {
                        this.device.gatt.disconnect();
                    }
                }
            } catch (error) {
                logOutput(`⚠️ Hubo un problema al desconectar: ${error.message}`);
            } finally {
                this.reset();
                logOutput("🔌 Dispositivo desconectado.");
                updateUI(false);
            }
        },

        // Resetea el estado del manejador
        reset: function() {
            this.type = null;
            this.device = null;
            this.writer = null;
            this.reader = null;
            this.isConnected = false;
        }
    };

    // --- LÓGICA DE CONEXIÓN ---

    // Conectar por USB Serial
    async function connectSerial() {
        if (!navigator.serial) {
            alert("Tu navegador no es compatible con la Web Serial API. Prueba con Chrome o Edge.");
            return;
        }
        try {
            const port = await navigator.serial.requestPort({
                filters: [{ usbVendorId: 0x10C4 }, { usbVendorId: 0x1A86 }]
            });
            await port.open({ baudRate: 115200 });

            connectionHandler.type = 'serial';
            connectionHandler.device = port;
            const decoder = new TextDecoderStream();
            port.readable.pipeTo(decoder.writable);
            connectionHandler.reader = decoder.readable.getReader();
            const encoder = new TextEncoderStream();
            encoder.readable.pipeTo(port.writable);
            connectionHandler.writer = encoder.writable.getWriter();
            connectionHandler.isConnected = true;

            updateUI(true);
            logOutput("✅ Conectado al dispositivo por USB.");
            readLoopSerial();
        } catch (err) {
            logOutput(`❌ Error de conexión USB: ${err.message}`);
            connectionHandler.reset();
            updateUI(false);
        }
    }


    async function connectBluetooth() {
        if (!navigator.bluetooth) {
            alert("Tu navegador no es compatible con la Web Bluetooth API. Prueba con Chrome o Edge.");
            return;
        }
        try {
            logOutput("Buscando dispositivos Bluetooth LE...");
            
            const device = await navigator.bluetooth.requestDevice({
                filters: [{ namePrefix: 'ESP32_Lector_RFID_BLE' }],
                optionalServices: [BLE_SERVICE_UUID] // Solicita acceso al servicio BLE
            });

            connectionHandler.type = 'bluetooth';
            connectionHandler.device = device;

            logOutput(`Conectando a ${device.name}...`);
            const server = await device.gatt.connect();
            const service = await server.getPrimaryService(BLE_SERVICE_UUID);

            // Obtiene las características por su UUID específico
            const rxCharacteristic = await service.getCharacteristic(BLE_CHAR_UUID_RX);
            const txCharacteristic = await service.getCharacteristic(BLE_CHAR_UUID_TX);

            connectionHandler.writer = rxCharacteristic; // Guardamos la característica para escribir

            await txCharacteristic.startNotifications();
            txCharacteristic.addEventListener('characteristicvaluechanged', handleBluetoothDataReceived);
            device.addEventListener('gattserverdisconnected', onBluetoothDisconnect);

            connectionHandler.isConnected = true;
            updateUI(true);
            logOutput(`✅ Conectado a ${device.name} por Bluetooth LE.`);
        } catch (err) {
            logOutput(`❌ Error de conexión Bluetooth: ${err.message}`);
            connectionHandler.reset();
            updateUI(false);
        }
    }

    // --- LÓGICA DE LECTURA DE DATOS ---
    async function readLoopSerial() {
        let lineBuffer = '';
        while (connectionHandler.isConnected && connectionHandler.type === 'serial') {
            try {
                const { value, done } = await connectionHandler.reader.read();
                if (done) break;
                lineBuffer += value;
                let newlineIndex;
                while ((newlineIndex = lineBuffer.indexOf('\n')) !== -1) {
                    const line = lineBuffer.slice(0, newlineIndex).trim();
                    lineBuffer = lineBuffer.slice(newlineIndex + 1);
                    if (line) processReceivedData(line);
                }
            } catch (err) {
                logOutput(`❌ Error de lectura: ${err.message}`);
                connectionHandler.disconnect();
                break;
            }
        }
    }

    function handleBluetoothDataReceived(event) {
        const textDecoder = new TextDecoder();
        const line = textDecoder.decode(event.target.value).trim();
        if (line) processReceivedData(line);
    }

    function onBluetoothDisconnect() {
        if (connectionHandler.isConnected) {
            connectionHandler.reset();
            logOutput("🔌 Dispositivo Bluetooth desconectado.");
            updateUI(false);
        }
    }

    // --- LÓGICA DE PROCESAMIENTO Y APLICACIÓN ---
    function processReceivedData(line) {
        try {
            const data = JSON.parse(line);
            if (data.origen === 'RFID') {
                if (modoAsignacion.activo) {
                    handleCardAssignment(data.uid);
                } else {
                    llamarApiDeLaravel(data.uid);
                }
            }
        } catch (e) {
            logOutput(`> ${line}`);
        }
    }

    async function createUser() {
        const username = usernameInput.value.trim();
        if (!username) {
            alert("Por favor, introduce un nombre de usuario.");
            return;
        }
        logOutput(`... Creando usuario: ${username}...`);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/api/users', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: username })
            });
            const result = await response.json();
            if (response.ok) {
                modoAsignacion.activo = true;
                modoAsignacion.userId = result.user.id;
                modoAsignacion.userName = result.user.name;
                logOutput(`✅ Usuario '${modoAsignacion.userName}' creado. ID: ${modoAsignacion.userId}`);
                logOutput(`⏳ POR FAVOR, ESCANEA LA TARJETA RFID PARA ASIGNARLA.`);
                sendCommand('ENROLL_START');
                usernameInput.value = '';
            } else {
                logOutput(`❌ Error al crear usuario: ${result.message}`);
            }
        } catch (error) {
            logOutput('❌ Error crítico al conectar con el servidor.');
            console.error(error);
        }
    }

    async function handleCardAssignment(uid) {
        logOutput(`... Asignando tarjeta ${uid} a ${modoAsignacion.userName}...`);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/api/cards/assign', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ uid: uid, user_id: modoAsignacion.userId })
            });
            const result = await response.json();
            logOutput(response.ok ? `✅ ¡ÉXITO! ${result.message}` : `❌ Error al asignar: ${result.message}`);
        } catch (error) {
            logOutput('❌ Error crítico al conectar con el servidor.');
            console.error(error);
        } finally {
            modoAsignacion.activo = false;
            sendCommand('ENROLL_STOP');
        }
    }

    async function llamarApiDeLaravel(uid) {
        logOutput(`... Verificando UID ${uid} con el servidor...`);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/api/log-access', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ uid: uid })
            });
            const result = await response.json();
            if (response.ok) {
                logOutput(`✅ Éxito: ${result.message}. ¡Bienvenido, ${result.user_name}!`);
                sendCommand('ACCESS_GRANTED');
            } else {
                logOutput(`❌ Acceso Denegado: ${result.message}`);
                sendCommand('ACCESS_DENIED');
            }
        } catch (error) {
            console.error('Error al llamar a la API:', error);
            logOutput('❌ Error crítico al conectar con el servidor.');
            sendCommand('ACCESS_DENIED');
        }
    }

    async function sendCommand(command) {
        if (connectionHandler.isConnected) {
            await connectionHandler.send(command);
            logOutput(`< Enviado: ${command}`);
        } else {
            logOutput("❌ Error: No hay conexión. Conecta primero.");
        }
    }

    function logOutput(message) {
        output.innerText += message + '\n';
        output.scrollTop = output.scrollHeight;
    }

    function updateUI(isConnected) {
        connectSerialButton.style.display = isConnected ? 'none' : 'inline-block';
        connectBtButton.style.display = isConnected ? 'none' : 'inline-block';
        disconnectButton.style.display = isConnected ? 'inline-block' : 'none';
    }

    // --- EVENT LISTENERS ---
    connectSerialButton.addEventListener('click', connectSerial);
    connectBtButton.addEventListener('click', connectBluetooth);
    disconnectButton.addEventListener('click', () => connectionHandler.disconnect());

    document.getElementById('led-green-on').addEventListener('click', () => sendCommand('LED_GREEN_ON'));
    document.getElementById('led-green-off').addEventListener('click', () => sendCommand('LED_GREEN_OFF'));
    document.getElementById('led-red-on').addEventListener('click', () => sendCommand('LED_RED_ON'));
    document.getElementById('led-red-off').addEventListener('click', () => sendCommand('LED_RED_OFF'));
    document.getElementById('create-user-btn').addEventListener('click', createUser);

    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDarkMode = document.body.classList.contains('dark-mode');
        themeToggle.innerText = isDarkMode ? '🌙' : '☀️';
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    });

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.innerText = '🌙';
    }
});