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
        Schema::create('phone_room_logs', function (Blueprint $table) {
            $table->id('id');
            $table->string('phone_room_lead_id');
            $table->text('log')->nullable();
            $table->integer('status');
            // Foreigns keys
            $table->foreignId('phone_id')->constrained('leads', 'phone')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('phone_room_id')->constrained('phone_rooms')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_room_logs');
    }
};
