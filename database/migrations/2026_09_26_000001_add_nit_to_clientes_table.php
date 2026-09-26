<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('dpi', 20)->nullable()->change();
            $table->string('nit', 20)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (DB::table('clientes')->whereNotNull('nit')->orWhereNull('dpi')->exists()) {
            throw new RuntimeException('No se puede retirar NIT mientras existan clientes que lo utilizan o carezcan de DPI.');
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['nit']);
            $table->dropColumn('nit');
            $table->string('dpi', 20)->nullable(false)->change();
        });
    }
};
