<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('correlativos_documentos', function (Blueprint $table) {
            $table->id();

            $table->string('tipo', 30)->unique();

            $table->unsignedBigInteger('ultimo_numero')
                ->default(0);

            $table->timestamps();
        });

        /*
         * AQ-71:
         * Se crea el correlativo independiente para recibos.
         *
         * Si ya existen recibos históricos, se inicia desde el
         * ID máximo existente para no comenzar nuevamente desde 1.
         *
         * A partir de este punto, el correlativo será administrado
         * exclusivamente por correlativos_documentos.
         */
        $ultimoExistente = (int) (DB::table('recibos')->max('id') ?? 0);

        DB::table('correlativos_documentos')->insert([
            'tipo' => 'RECIBO',
            'ultimo_numero' => $ultimoExistente,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlativos_documentos');
    }
};