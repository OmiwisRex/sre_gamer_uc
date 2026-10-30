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
        Schema::create('asociaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('dispositivo_id')->nullable()->default(null)->
                constrained('dispositivos')->restrictOnDelete();
            $table->foreignId('evento_id')->nullable()->default(null)->
                constrained('eventos')->restrictOnDelete();
            $table->foreignId('reserva_id')->nullable()->default(null)->
                constrained('reservas')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->timestamp('inicio')->useCurrent();
            $table->timestamp('final')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asociaciones');
    }
};
