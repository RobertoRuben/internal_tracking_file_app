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
        // Eliminar el campo 'status' de la tabla derivations
        Schema::table('derivations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        // Modificar la restricción de clave foránea en charge_books para permitir eliminación en cascada
        Schema::table('charge_books', function (Blueprint $table) {
            // Primero eliminamos la restricción existente
            $table->dropForeign(['document_id']);
            
            // Luego la volvemos a crear con cascada en eliminación
            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar el campo 'status' en la tabla derivations
        Schema::table('derivations', function (Blueprint $table) {
            $table->string('status')->default('Pendiente')->after('derivated_by_user_id');
        });
        
        // Restaurar la restricción de clave foránea original en charge_books
        Schema::table('charge_books', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            
            $table->foreign('document_id')
                ->references('id')
                ->on('documents');
        });
    }
};
