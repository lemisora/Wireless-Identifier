const connectButton = document.getElementById('connect');
    const disconnectButton = document.getElementById('disconnect');
    const output = document.getElementById('output');
    const usernameInput = document.getElementById('usernameInput');
    const themeToggle = document.getElementById('theme-toggle');

    // --- VARIABLES GLOBALES ---
    let port, reader, writer;
    let modoAsignacion = {
        activo: false,
        userId: null,
        userName: null
    };

    // --- LÓGICA DE CONEXIÓN SERIAL ---
    async function connectSerial() {
        try {
            port = await navigator.serial.requestPort({
                filters: [{ usbVendorId: 0x10C4 }, { usbVendorId: 0x1A86 }]
            });
            await port.open({ baudRate: 115200 });

            const decoder = new TextDecoderStream();
            port.readable.pipeTo(decoder.writable);
            reader = decoder.readable.getReader();

            const encoder = new TextEncoderStream();
            encoder.readable.pipeTo(port.writable);
            writer = encoder.writable.getWriter();

            updateUI(true);
            logOutput("✅ Conectado al dispositivo.");
            readLoop(); // Inicia el bucle de lectura
        } catch (err) {
            console.error("Error de conexión:", err);
            logOutput(`❌ Error: ${err.message}`);
            updateUI(false);
        }
    }

    async function disconnectSerial() {
        
    }

    // Lectura de datos del puerto serial
    async function readLoop() {
        let lineBuffer = '';
        while (true) {
            try {
                const { value, done } = await reader.read();
                if (done) {
                    reader.releaseLock();
                    break;
                }

                lineBuffer += value;

                let newlineIndex;
                while ((newlineIndex = lineBuffer.indexOf('\n')) !== -1) {
                    const line = lineBuffer.slice(0, newlineIndex).trim();
                    lineBuffer = lineBuffer.slice(newlineIndex + 1);

                    if (line) {
                        // Intentamos procesar la línea como JSON
                        try {
                            const data = JSON.parse(line);
                            // Si el mensaje viene del RFID, lo procesamos
                            if (data.origen === 'RFID') {
                                if (modoAsignacion.activo) {
                                    handleCardAssignment(data.uid);
                                } else {
                                    llamarApiDeLaravel(data.uid); 
                                }
                            }
                        } catch (e) {
                            // Si no es JSON, es un mensaje de texto normal del ESP32
                            logOutput(`> ${line}`);
                        }
                    }
                }
            } catch (err) {
                logOutput(`❌ Error de lectura: ${err}`);
                updateUI(false);
                break;
            }
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
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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
        }
    }

    async function handleCardAssignment(uid) {
        logOutput(`... Asignando tarjeta ${uid} a ${modoAsignacion.userName}...`);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/api/cards/assign', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ uid: uid, user_id: modoAsignacion.userId })
            });

            const result = await response.json();
            logOutput(response.ok ? `✅ ¡ÉXITO! ${result.message}` : `❌ Error al asignar: ${result.message}`);

        } catch (error) {
            logOutput('❌ Error crítico al conectar con el servidor.');
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ uid: uid })
                });
        
                const result = await response.json();
        
                // --- LÓGICA DE DECISIÓN CENTRALIZADA ---
                if (response.ok) {
                    // Si la API dice que el acceso es permitido.
                    logOutput(`✅ Éxito: ${result.message}. ¡Bienvenido, ${result.user_name}!`);
                    sendCommand('ACCESS_GRANTED');
                } else {
                    // Si la API dice que el acceso es denegado.
                    logOutput(`❌ Error: ${result.message}`);
                    sendCommand('ACCESS_DENIED');
                }
        
            } catch (error) {
                console.error('Error al llamar a la API:', error);
                logOutput('❌ Error crítico al conectar con el servidor.');
                // En caso de error de conexión, también denegamos el acceso físicamente.
                sendCommand('ACCESS_DENIED');
            }
    }

    async function sendCommand(command) {
        if (writer && port.writable) {
          await writer.write(command + '\n');
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
        connectButton.style.display = isConnected ? 'none' : 'inline-block';
        disconnectButton.style.display = isConnected ? 'inline-block' : 'none';
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', () => {
        // Conexión
        connectButton.addEventListener('click', connectSerial);
        disconnectButton.addEventListener('click', disconnectSerial);
    
        // Control de Módulo
        document.getElementById('led-green-on').addEventListener('click', () => sendCommand('LED_GREEN_ON'));
        document.getElementById('led-green-off').addEventListener('click', () => sendCommand('LED_GREEN_OFF'));
        document.getElementById('led-red-on').addEventListener('click', () => sendCommand('LED_RED_ON'));
        document.getElementById('led-red-off').addEventListener('click', () => sendCommand('LED_RED_OFF'));
    
        // Gestión de Usuarios
        document.getElementById('create-user-btn').addEventListener('click', createUser);
    
        // Tema
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            themeToggle.innerText = isDarkMode ? '🌙' : '☀️';
        });
    });
    