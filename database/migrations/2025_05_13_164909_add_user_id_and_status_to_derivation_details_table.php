<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('derivation_details', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('derivation_details', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status']);
        });
    }
};