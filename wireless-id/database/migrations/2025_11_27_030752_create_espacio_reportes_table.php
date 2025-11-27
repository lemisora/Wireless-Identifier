<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('espacio_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('dispositivo_id');
            $table->boolean('esta_ocupado');
            $table->boolean('sensor_izquierdo') -> default(false);
            $table->boolean('sensor_derecho') -> default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('espacio_reportes');
    }
};
