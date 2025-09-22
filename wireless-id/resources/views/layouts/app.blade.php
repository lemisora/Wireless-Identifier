<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>{{ $title ?? 'Mi Proyecto RFID' }}</title>
    
        {{-- Carga los assets de CSS y JS compilados por Vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body>
        {{-- Aquí es donde se renderizará el contenido de tus componentes --}}
        {{ $slot }}
        
        @stack('scripts')   {{-- Para scripts personalizados --}}
    </body>
</html>
