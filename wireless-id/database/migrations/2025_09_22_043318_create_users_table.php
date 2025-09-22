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
        Schema::create('users', function (Blueprint $table) {
            $table->id();   // Id del usuario
            $table->string('name'); // Nombre del usuario
            $table->foreignId('card_id')->nullable()->unique()->constrained('cards');   // Tarjeta que le pertenece
            $table->string('password');
            $table->timestamps();   // Tiempo de añadido
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
