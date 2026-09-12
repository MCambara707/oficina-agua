<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas_frecuentes', function (Blueprint $table) {
            $table->id();

            $table->string('pregunta', 255);
            $table->text('respuesta');

            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_frecuentes');
    }
};