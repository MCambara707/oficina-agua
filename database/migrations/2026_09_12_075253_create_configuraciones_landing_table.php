<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_landing', function (Blueprint $table) {
            $table->id();

            // Información principal de la landing.
            $table->string('titulo_principal', 150)->nullable();
            $table->text('descripcion_principal')->nullable();

            // Quiénes somos.
            $table->text('historia')->nullable();
            $table->text('mision')->nullable();
            $table->text('vision')->nullable();
            $table->text('diferenciadores')->nullable();

            // Información de contacto.
            $table->string('telefono', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->text('direccion')->nullable();
            $table->string('horario', 255)->nullable();

            // Permite habilitar o deshabilitar la configuración pública.
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_landing');
    }
};