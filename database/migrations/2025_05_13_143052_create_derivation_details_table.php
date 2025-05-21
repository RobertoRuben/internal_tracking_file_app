<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('derivation_details', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('derivation_id')->constrained('derivations')->cascadeOnDelete();
            $table->text('comments');
            $table->timestampsTz();
            $table->index('derivation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derivation_details');
    }
};
