<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('doc_code')->unique();
            $table->unsignedBigInteger('registration_number');
            $table->string('name');
            $table->text('path');
            $table->text('subject');
            $table->integer('pages');
            $table->foreignId('registered_by_user_id')->constrained('users');
            $table->boolean('is_derived');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('created_by_department_id')
                ->constrained('departments');
            $table->timestampsTz();

            $table->index('registration_number');
            $table->unique(
                ['registration_number','created_by_department_id'],
                'documents_regnum_dept_unique'
            );
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
