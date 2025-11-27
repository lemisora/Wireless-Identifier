<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EspacioReporte;
use Illuminate\Support\Facades\Log;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar que los datos vengan correctos
        // El ESP32 envía: { "dispositivo_id": "...", "ocupado": true, "detalle_sensores": { "izquierda": 1, ... } }
        $validated = $request->validate([
            "dispositivo_id" => "required|string",
            "ocupado" => "required|boolean",
            "detalle_sensores" => "required|array",
            "detalle_sensores.izquierda" => "required|boolean",
            "detalle_sensores.derecha" => "required|boolean",
        ]);

        try {
            // 2. Crear el registro en la BD
            // Mapeamos el JSON anidado a las columnas planas de la tabla
            $reporte = EspacioReporte::create([
                "dispositivo_id" => $request->dispositivo_id,
                "esta_ocupado" => $request->ocupado,
                "sensor_izquierdo" => $request->input(
                    "detalle_sensores.izquierda",
                ),
                "sensor_derecho" => $request->input("detalle_sensores.derecha"),
            ]);

            // 3. Responder al ESP32 (Código 201 = Creado)
            return response()->json(
                [
                    "mensaje" => "Reporte guardado con éxito",
                    "id" => $reporte->id,
                ],
                201,
            );
        } catch (\Exception $e) {
            // Si algo falla, registramos el error en storage/logs/laravel.log
            Log::error("Error guardando reporte IoT: " . $e->getMessage());

            return response()->json(
                [
                    "error" => "Error interno del servidor",
                ],
                500,
            );
        }
    }

    public function latest()
    {
        // Busca el último registro usando tu modelo EspacioReporte
        $ultimoReporte = EspacioReporte::latest()->first();

        if (!$ultimoReporte) {
            // Retornamos una estructura vacía por defecto para que el JS no falle
            return response()->json([
                "esta_ocupado" => false,
                "sensor_izquierdo" => false,
                "sensor_derecho" => false,
                "created_at" => null,
            ]);
        }

        return response()->json($ultimoReporte);
    }
}
