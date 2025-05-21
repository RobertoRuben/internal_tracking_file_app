<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar valores de status en derivation_details existentes
        DB::statement("
            UPDATE derivation_details 
            SET status = 'Enviado' 
            WHERE status NOT IN ('Enviado', 'Recibido', 'Rechazado')
            AND status NOT IN ('Creado', 'Actualizado')
        ");
        
        // Insertar un detalle con estado "Enviado" para cada derivación que no tenga ningún detalle
        $derivations = DB::table('derivations')
            ->leftJoin('derivation_details', 'derivations.id', '=', 'derivation_details.derivation_id')
            ->whereNull('derivation_details.id')
            ->select('derivations.id', 'derivations.derivated_by_user_id')
            ->get();
            
        foreach ($derivations as $derivation) {
            DB::table('derivation_details')->insert([
                'derivation_id' => $derivation->id,
                'comments' => 'Documento derivado',
                'user_id' => $derivation->derivated_by_user_id,
                'status' => 'Enviado',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir este cambio fácilmente
    }
};
