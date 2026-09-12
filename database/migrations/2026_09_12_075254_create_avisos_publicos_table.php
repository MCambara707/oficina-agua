<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_publicos', function (Blueprint $table) {
            $table->id();

            $table->string('titulo', 150);
            $table->text('contenido');

            // Vigencia opcional del aviso.
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_publicos');
    }
};