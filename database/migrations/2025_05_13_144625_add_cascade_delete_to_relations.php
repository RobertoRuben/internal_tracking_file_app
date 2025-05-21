<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Establecer eliminación en cascada para la relación documents->employee
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
        });

        // Establecer eliminación en cascada para las derivaciones desde usuarios
        Schema::table('derivations', function (Blueprint $table) {
            $table->dropForeign(['derivated_by_user_id']);
            $table->foreign('derivated_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Establecer acciones de nulo para relaciones con departamentos
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['created_by_department_id']);
            $table->foreign('created_by_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });

        Schema::table('derivations', function (Blueprint $table) {
            $table->dropForeign(['origin_department_id']);
            $table->dropForeign(['destination_department_id']);

            $table->foreign('origin_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('destination_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Restaurar configuraciones originales
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees');

            $table->dropForeign(['created_by_department_id']);
            $table->foreign('created_by_department_id')
                ->references('id')
                ->on('departments');
        });

        Schema::table('derivations', function (Blueprint $table) {
            $table->dropForeign(['derivated_by_user_id']);
            $table->foreign('derivated_by_user_id')
                ->references('id')
                ->on('users');

            $table->dropForeign(['origin_department_id']);
            $table->dropForeign(['destination_department_id']);

            $table->foreign('origin_department_id')
                ->references('id')
                ->on('departments');

            $table->foreign('destination_department_id')
                ->references('id')
                ->on('departments');
        });
    }
};
