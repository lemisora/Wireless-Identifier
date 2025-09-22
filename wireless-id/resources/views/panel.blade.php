<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Panel de Control Web Serial</title>
  {{-- @vite['resources/css/app.css', 'resources/js/app.js'] --}}
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #007BFF;
      --secondary-color: #f0f2f5;
      --card-bg-color: #ffffff;
      --text-color: #333;
      --border-color: #e0e0e0;
      --shadow-color: rgba(0, 0, 0, 0.1);
    }
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      background: linear-gradient(135deg, #f0f2f5, #e1e4e8);
      color: var(--text-color);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      transition: background-color 0.5s ease;
    }
    body.dark-mode {
      --secondary-color: #2c2f33;
      --card-bg-color: #36393f;
      --text-color: #e0e0e0;
      --border-color: #444;
      --shadow-color: rgba(0, 0, 0, 0.4);
      background: linear-gradient(135deg, #1c1e21, #242629);
    }
    .container {
      width: 90%;
      max-width: 800px;
      padding: 40px;
      background-color: var(--card-bg-color);
      border-radius: 12px;
      box-shadow: 0 10px 30px var(--shadow-color);
      transition: all 0.5s ease;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
    }
    h1 {
      font-weight: 500;
      font-size: 2.2em;
      margin: 0;
      color: var(--primary-color);
    }
    #theme-toggle {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.5em;
      color: var(--text-color);
      transition: transform 0.3s ease;
    }
    #theme-toggle:hover {
      transform: scale(1.1);
    }
    .card {
      background-color: var(--secondary-color);
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border-color);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    .card-title {
      font-weight: 600;
      margin-top: 0;
      margin-bottom: 15px;
      font-size: 1.3em;
      display: flex;
      align-items: center;
    }
    .card-title svg {
      margin-right: 12px;
      color: var(--primary-color);
      transition: transform 0.3s ease;
    }
    .card:hover .card-title svg {
      transform: rotate(5deg);
    }
    .button-group {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }
    button {
      flex: 1;
      min-width: 150px;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      background-color: var(--primary-color);
      color: white;
      font-size: 1em;
      cursor: pointer;
      transition: transform 0.1s ease, background-color 0.3s ease;
      position: relative;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    button:active {
      transform: translateY(2px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    button:hover {
      background-color: #0056b3;
    }
    button:disabled {
      background-color: #ccc !important;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    #disconnect {
      background-color: #dc3545;
    }
    #disconnect:hover {
      background-color: #c82333;
    }
    input[type="text"] {
      flex: 1;
      min-width: 200px;
      padding: 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 1em;
      background-color: var(--card-bg-color);
      color: var(--text-color);
      transition: all 0.3s ease;
    }
    input[type="text"]:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    }
    .input-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }
    #output {
      background-color: #1c1e21;
      color: #0f0;
      padding: 20px;
      max-height: 250px;
      overflow-y: auto;
      white-space: pre-wrap;
      word-wrap: break-word;
      font-family: 'Courier New', monospace;
      border-radius: 8px;
      line-height: 1.5;
      box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    @media (max-width: 600px) {
      .container {
        padding: 20px;
      }
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      h1 {
        margin-bottom: 20px;
      }
      .button-group, .input-group {
        flex-direction: column;
      }
      input[type="text"] {
        width: 100%;
        margin-bottom: 10px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>Panel de Control </h1>
    <button id="theme-toggle">☀️</button>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-zap"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
      Conexión Serial
    </div>
    <div class="button-group">
      <button id="connect">Conectar Dispositivo</button>
      <button id="disconnect" style="display:none;">CONECTADO</button>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-cpu"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><path d="M15 2v2"></path><path d="M15 20v2"></path><path d="M2 15h2"></path><path d="M20 15h2"></path><path d="M15 2h2"></path><path d="M15 20h2"></path><path d="M2 15h2"></path><path d="M20 15h2"></path></svg>
      Control de Módulo
    </div>
    <div class="button-group">
      <button onclick="sendCommand('LED_GREEN_ON')">Encender LED Verde</button>
      <button onclick="sendCommand('LED_GREEN_OFF')">Apagar LED Verde</button>
      <button onclick="sendCommand('LED_RED_ON')">Encender LED Rojo</button>
      <button onclick="sendCommand('LED_RED_OFF')">Apagar LED Rojo</button>
      <button onclick="sendCommand('GET_STATUS')">Obtener Estado</button>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Gestión de Usuarios
    </div>
    <div class="input-group">
      <input type="text" id="usernameInput" placeholder="Ej: JuanPerez" maxlength="20">
      <button onclick="createUser()">Guardar</button>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-terminal"><polyline points="4 17 10 23 16 17"></polyline><line x1="12" y1="12" x2="12" y2="23"></line></svg>
      Registro de Consola
    </div>
    <pre id="output"></pre>
  </div>
</div>

<script>
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
        // ... (código de desconexión sin cambios)
    }

    // --- LECTURA DE DATOS DEL PUERTO (AQUÍ ESTÁ LA MODIFICACIÓN) ---
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
                                    llamarApiDeLaravel(data.uid, data.status);
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

    async function llamarApiDeLaravel(uid, status) {
        // No llamamos a la API si el acceso fue denegado en el ESP32
        if (status !== 'PERMITIDO') return;

        logOutput(`... Enviando UID ${uid} a la base de datos...`);

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

            if (response.ok) {
                logOutput(`✅ Éxito: Bienvenido, ${result.user_name}!`);
            } else {
                logOutput(`❌ Error de BD: ${result.message}`);
            }

        } catch (error) {
            console.error('Error al llamar a la API:', error);
            logOutput('❌ Error crítico al conectar con el servidor.');
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

    // --- EVENT LISTENERS ---
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDarkMode = document.body.classList.contains('dark-mode');
        themeToggle.innerText = isDarkMode ? '🌙' : '☀️';
    });
    connectButton.addEventListener('click', connectSerial);
    disconnectButton.addEventListener('click', disconnectSerial);
</script>

</body>
</html>
