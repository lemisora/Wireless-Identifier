<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Aula IoT</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Estilos específicos para esta vista (Transiciones) -->
    <style>
        .transition-colors { transition: background-color 0.5s ease, border-color 0.5s ease; }
        .transition-transform { transition: transform 0.5s ease; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center justify-center font-sans p-4">

    <!-- Tarjeta Principal -->
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden p-8 space-y-6 transform transition-all hover:shadow-2xl">
        
        <!-- Encabezado -->
        <div class="text-center border-b border-gray-100 pb-4">
            <h2 class="text-gray-400 font-bold tracking-widest uppercase text-xs">Sistema de Monitoreo</h2>
            <h1 class="text-4xl font-extrabold text-slate-800 mt-1">Salón de Hardware</h1>
            <p class="text-xs text-gray-400 mt-1">Facultad de Ciencias de la Computación</p>
        </div>

        <!-- Estado Principal (Dinámico) -->
        <div id="status-card" class="transition-colors rounded-xl p-8 text-center bg-gray-100 border-2 border-transparent">
            <div id="status-icon" class="text-7xl mb-2 transition-transform hover:scale-110">⏳</div>
            <h3 id="status-text" class="text-3xl font-black text-gray-400">Cargando...</h3>
            
            <div class="mt-4 flex justify-center items-center space-x-2">
                <span class="relative flex h-3 w-3">
                  <span id="ping-dot" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                  <span id="static-dot" class="relative inline-flex rounded-full h-3 w-3 bg-gray-500"></span>
                </span>
                <p id="last-update" class="text-xs font-mono text-gray-500 opacity-80">Esperando datos...</p>
            </div>
        </div>

        <!-- Detalles de Sensores -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Sensor Izquierdo -->
            <div id="card-left" class="bg-slate-50 p-4 rounded-xl text-center border border-slate-200 transition-colors">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sensor Izquierdo</span>
                <div class="flex items-center justify-center space-x-2">
                    <span id="icon-left" class="text-xl opacity-50 grayscale">📡</span>
                    <span id="sensor-left" class="text-sm font-bold text-slate-500">Inactivo</span>
                </div>
            </div>
            
            <!-- Sensor Derecho -->
            <div id="card-right" class="bg-slate-50 p-4 rounded-xl text-center border border-slate-200 transition-colors">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sensor Derecho</span>
                <div class="flex items-center justify-center space-x-2">
                    <span id="icon-right" class="text-xl opacity-50 grayscale">📡</span>
                    <span id="sensor-right" class="text-sm font-bold text-slate-500">Inactivo</span>
                </div>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="text-center pt-2 space-y-2">
            <p class="text-[10px] text-gray-400">
                ID Dispositivo: <span id="device-id" class="font-mono text-gray-600">Cargando...</span>
            </p>
            <div id="connection-badge" class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-50 text-yellow-600 text-[10px] font-bold border border-yellow-100">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-2 animate-pulse"></span>
                Conectando API...
            </div>
        </div>
    </div>

    <!-- Lógica JS (Fetch API) -->
    <script>
        // URL de tu API en Laravel
        const API_URL = '/api/estado-actual';

        // Referencias al DOM
        const ui = {
            card: document.getElementById('status-card'),
            text: document.getElementById('status-text'),
            icon: document.getElementById('status-icon'),
            time: document.getElementById('last-update'),
            ping: document.getElementById('ping-dot'),
            dot: document.getElementById('static-dot'),
            connBadge: document.getElementById('connection-badge'),
            
            deviceId: document.getElementById('device-id'),
            left: { 
                card: document.getElementById('card-left'), 
                text: document.getElementById('sensor-left'), 
                icon: document.getElementById('icon-left') 
            },
            right: { 
                card: document.getElementById('card-right'), 
                text: document.getElementById('sensor-right'), 
                icon: document.getElementById('icon-right') 
            }
        };

        async function fetchData() {
            try {
                const response = await fetch(API_URL);
                if (!response.ok) throw new Error('Error API');
                
                const data = await response.json();
                updateUI(data);
                
                // Indicador de conexión exitosa
                ui.connBadge.className = "inline-flex items-center px-2 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold border border-emerald-100";
                ui.connBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> En línea';

            } catch (error) {
                console.error('Error:', error);
                // Indicador de error
                ui.connBadge.className = "inline-flex items-center px-2 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-bold border border-red-100";
                ui.connBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-2"></span> Error de conexión';
            }
        }

        function updateUI(data) {
            console.log(data);
            const now = new Date();
            ui.time.innerText = `Actualizado: ${now.toLocaleTimeString()}`;
            
            ui.deviceId.innerText = data.dispositivo_id || 'Sin Datos';

            // 1. Lógica Principal (Ocupado/Libre)
            if (data.esta_ocupado) {
                setOccupiedState();
            } else {
                setFreeState();
            }

            // 2. Lógica Sensores
            updateSensorState(ui.left, data.sensor_izquierdo);
            updateSensorState(ui.right, data.sensor_derecho);
        }

        function setOccupiedState() {
            ui.card.className = "transition-colors rounded-xl p-8 text-center bg-red-50 border-2 border-red-500 shadow-[0_0_20px_rgba(239,68,68,0.3)]";
            ui.text.innerText = "OCUPADO";
            ui.text.className = "text-3xl font-black text-red-600 tracking-wider";
            ui.icon.innerText = "🚫";
            
            ui.ping.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75";
            ui.dot.className = "relative inline-flex rounded-full h-3 w-3 bg-red-600";
        }

        function setFreeState() {
            ui.card.className = "transition-colors rounded-xl p-8 text-center bg-emerald-50 border-2 border-emerald-500 shadow-[0_0_20px_rgba(16,185,129,0.2)]";
            ui.text.innerText = "DISPONIBLE";
            ui.text.className = "text-3xl font-black text-emerald-600 tracking-wider";
            ui.icon.innerText = "✅";

            ui.ping.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75";
            ui.dot.className = "relative inline-flex rounded-full h-3 w-3 bg-emerald-600";
        }

        function updateSensorState(el, isActive) {
            if (isActive) {
                el.card.className = "bg-red-50 p-4 rounded-xl text-center border border-red-200 transition-colors shadow-sm";
                el.text.innerText = "Detectando";
                el.text.className = "text-sm font-bold text-red-600";
                el.icon.className = "text-xl text-red-500 animate-pulse"; 
            } else {
                el.card.className = "bg-slate-50 p-4 rounded-xl text-center border border-slate-200 transition-colors";
                el.text.innerText = "Sin actividad";
                el.text.className = "text-sm font-bold text-slate-400";
                el.icon.className = "text-xl opacity-30 grayscale"; 
            }
        }

        // Iniciar ciclo de actualización (cada 2 segundos)
        fetchData();
        setInterval(fetchData, 2000);

    </script>
</body>
</html>