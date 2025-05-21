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
        Schema::table('derivation_details', function (Blueprint $table) {
            // Añadir índice para mejorar rendimiento de búsquedas por estado
            $table->index('status');
        });
        
        // Cambiar los valores a uno de los tres estados permitidos
        DB::statement("
            UPDATE derivation_details 
            SET status = 'Enviado' 
            WHERE status NOT IN ('Enviado', 'Recibido', 'Rechazado', 'Creado', 'Actualizado')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('derivation_details', function (Blueprint $table) {
            // Eliminar el índice
            $table->dropIndex(['status']);
        });
    }
};
