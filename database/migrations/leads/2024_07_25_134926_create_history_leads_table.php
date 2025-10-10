<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('history_leads', function (Blueprint $table) {
            $table->id();
            $table->json('before_h');
            $table->json('after_h');
            // Foreigns keys
            $table->foreignId('phone_id')->constrained('leads', 'phone')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_leads');
    }
};
