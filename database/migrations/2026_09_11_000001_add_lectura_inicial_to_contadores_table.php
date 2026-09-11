<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega la base física sin inferir valores para contadores existentes.
     * El CHECK permite NULL y cero, y rechaza bases negativas en MariaDB.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE contadores '
            . 'ADD COLUMN lectura_inicial DECIMAL(12,3) NULL, '
            . 'ADD CONSTRAINT chk_contadores_lectura_inicial_no_negativa '
            . 'CHECK (lectura_inicial IS NULL OR lectura_inicial >= 0)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE contadores '
            . 'DROP CONSTRAINT chk_contadores_lectura_inicial_no_negativa, '
            . 'DROP COLUMN lectura_inicial'
        );
    }
};
