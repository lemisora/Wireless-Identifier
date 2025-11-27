<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EspacioReporte extends Model
{
    use HasFactory;
    
    protected $table = 'espacio_reportes';

    protected $fillable = [
        'dispositivo_id',
        'esta_ocupado',
        'sensor_izquierdo',
        'sensor_derecho'
    ];
    
    protected $casts = [
        'esta_ocupado' => 'boolean',
        'sensor_izquierdo' => 'boolean',
        'sensor_derecho' => 'boolean'
    ];
}
