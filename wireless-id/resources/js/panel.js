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

    // Objeto para manejar la conexión actual (abstracción)
    const connectionHandler = {
        type: null, // 'serial' o 'bluetooth'
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
            const dataToSend = command + '\n';

            try {
                if (this.type === 'serial') {
                    await this.writer.write(dataToSend);
                } else if (this.type === 'bluetooth') {
                    const textEncoder = new TextEncoder();
                    await this.writer.writeValue(textEncoder.encode(dataToSend));
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
                    // Cierra streams y puerto para Web Serial
                    if (this.reader) {
                        await this.reader.cancel();
                        this.reader.releaseLock();
                    }
                    if (this.writer) {
                        await this.writer.close();
                        this.writer.releaseLock();
                    }
                    if (this.device) {
                        await this.device.close();
                    }
                } else if (this.type === 'bluetooth') {
                    // Desconecta del servidor GATT para Web Bluetooth
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

    // --- LÓGICA DE CONEXIÓN (SERIAL y BLUETOOTH) ---

    // Conectar por USB Serial
    async function connectSerial() {
        if (!navigator.serial) {
            alert("Tu navegador no es compatible con la Web Serial API. Prueba con Chrome o Edge.");
            return;
        }
        try {
            const port = await navigator.serial.requestPort({
                filters: [{ usbVendorId: 0x10C4 }, { usbVendorId: 0x1A86 }] // IDs comunes para ESP32/CH340
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
            readLoopSerial(); // Inicia el bucle de lectura específico para Serial
        } catch (err) {
            logOutput(`❌ Error de conexión USB: ${err.message}`);
            connectionHandler.reset();
            updateUI(false);
        }
    }

    // Conectar por Bluetooth
    async function connectBluetooth() {
        if (!navigator.bluetooth) {
            alert("Tu navegador no es compatible con la Web Bluetooth API. Prueba con Chrome o Edge.");
            return;
        }
        try {
            logOutput("Buscando dispositivos Bluetooth...");
            const SERVICE_UUID = '00001101-0000-1000-8000-00805f9b34fb'; // UUID para Serial Port Profile (SPP)

            const device = await navigator.bluetooth.requestDevice({
                filters: [{ namePrefix: 'ESP32_Lector_RFID' }],
                optionalServices: [SERVICE_UUID]
            });

            connectionHandler.type = 'bluetooth';
            connectionHandler.device = device;

            logOutput(`Conectando a ${device.name}...`);
            const server = await device.gatt.connect();
            const service = await server.getPrimaryService(SERVICE_UUID);
            const characteristics = await service.getCharacteristics();

            // Asumimos que la primera es RX (NOTIFY) y la segunda TX (WRITE)
            const rxCharacteristic = characteristics[0];
            const txCharacteristic = characteristics[1];

            connectionHandler.writer = txCharacteristic; // Característica para escribir (TX)

            await rxCharacteristic.startNotifications();
            rxCharacteristic.addEventListener('characteristicvaluechanged', handleBluetoothDataReceived);
            device.addEventListener('gattserverdisconnected', onBluetoothDisconnect);

            connectionHandler.isConnected = true;
            updateUI(true);
            logOutput(`✅ Conectado a ${device.name} por Bluetooth.`);
        } catch (err) {
            logOutput(`❌ Error de conexión Bluetooth: ${err.message}`);
            connectionHandler.reset();
            updateUI(false);
        }
    }

    // --- LÓGICA DE LECTURA DE DATOS ---

    // Bucle de lectura para Web Serial
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

    // Manejador de eventos para datos recibidos por Bluetooth
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

    // --- LÓGICA DE PROCESAMIENTO (GENÉRICA) ---
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
            // Si no es JSON, es un mensaje de estado
            logOutput(`> ${line}`);
        }
    }

    // --- LÓGICA DE LA APLICACIÓN (LLAMADAS A API, ETC.) ---
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

    // --- FUNCIONES AUXILIARES (UI) ---
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

    // Cargar el tema guardado
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.innerText = '🌙';
    }
});