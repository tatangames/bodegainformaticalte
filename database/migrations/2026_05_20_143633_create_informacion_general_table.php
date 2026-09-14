<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AJUSTES DE 1 FILA
     */
    public function up(): void
    {
        Schema::create('informacion_general', function (Blueprint $table) {
            $table->id();

            // REPORTE PIXELES DISTANCIAS
            $table->integer('px_firmas');
            $table->integer('px_observaciones');
            $table->boolean('salto_pagina');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informacion_general');
    }
};
