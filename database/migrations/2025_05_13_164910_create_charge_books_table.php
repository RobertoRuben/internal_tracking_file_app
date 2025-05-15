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
        Schema::create('charge_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('sender_department_id');
            $table->unsignedBigInteger('sender_user_id');
            $table->unsignedBigInteger('receiver_user_id');
            $table->unsignedBigInteger('department_id');
            $table->text('notes')->nullable();
            $table->unsignedInteger('registration_number');
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents');
            $table->foreign('sender_department_id')->references('id')->on('departments');
            $table->foreign('sender_user_id')->references('id')->on('users');
            $table->foreign('receiver_user_id')->references('id')->on('users');
            $table->foreign('department_id')->references('id')->on('departments');
            
            $table->unique(['department_id', 'registration_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_books');
    }
};