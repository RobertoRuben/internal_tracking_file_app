<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dni')->unique();
            $table->string('names');
            $table->string('paternal_surname');
            $table->string('maternal_surname');
            $table->string('gender');
            $table->unsignedBigInteger('phone_number')->nullable();
            $table->foreignId('department_id')
                ->constrained('departments')->nullOnDelete();
            $table->timestampsTz();
            $table->index('department_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
