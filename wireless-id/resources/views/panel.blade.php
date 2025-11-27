<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Panel de Control RFID</title>
  @vite(['resources/css/app.css', 'resources/js/panel.js'])
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
  <div class="header">
    <h1>Panel de Control </h1>
    <button id="theme-toggle">☀️</button>
  </div>

  <div class="card" id="connection-controls">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-zap"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
      Conexión del Dispositivo
    </div>
    <div class="button-group">
      <button id="connect-serial-btn">Conectar por USB</button>
      <button id="connect-bt-btn">Conectar por Bluetooth</button>
      <button id="disconnect-btn" style="display:none;">Desconectar</button>
    </div>
  </div>
  
  <div class="card">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-sun"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y1="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y1="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y1="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y1="4.22"></line></svg>
            Control del Foco
        </div>
        
        <div class="card-subtitle">Control Manual</div>
        <div class="button-group">
            <button id="foco-turn-on-btn">Encender Foco (Manual)</button>
            <button id="foco-turn-off-btn">Apagar Foco (Manual)</button>
        </div>
  
        <div class="card-subtitle">Control Automático</div>
        <div class="button-group">
            <button id="foco-toggle-auto-btn">Activar/Desactivar Modo Automático</button>
        </div>
        <div class="input-group">
            <input type="number" id="threshold-input" placeholder="Umbral LDR (ej: 500)" min="0" max="4095">
            <button id="set-threshold-btn">Fijar Umbral</button>
        </div>
    </div>
  
  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-cpu"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><path d="M15 2v2"></path><path d="M15 20v2"></path><path d="M2 15h2"></path><path d="M20 15h2"></path><path d="M15 2h2"></path><path d="M15 20h2"></path><path d="M2 15h2"></path><path d="M20 15h2"></path></svg>
      Control de Módulo
    </div>
    <div class="button-group">
      <button id="led-green-on">Encender LED Verde</button>
      <button id="led-green-off">Apagar LED Verde</button>
      <button id="led-red-on">Encender LED Rojo</button>
      <button id="led-red-off">Apagar LED Rojo</button>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Gestión de Usuarios
    </div>
    <div class="input-group">
      <input type="text" id="usernameInput" placeholder="Ej: JuanPerez" maxlength="20">
      <button id="create-user-btn">Guardar</button>
    </div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-terminal">
        <polyline points="4 14 12 20 20 14"></polyline> <line x1="12" y1="8" x2="12" y2="20"></line>
      </svg>
      Registro de Consola
    </div>
    <pre id="output"></pre>
  </div>
</div>

</body>
</html>