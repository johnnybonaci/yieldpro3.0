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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('connected_to')->nullable();
            $table->string('caller_city')->nullable();
            $table->string('durations')->nullable();
            $table->string('status')->nullable();
            $table->decimal('revenue', 8, 2)->default(0);
            $table->decimal('payout', 8, 2)->default(0);
            $table->date('date_history');
            // Foreign Keys
            $table->foreignId('phone_id')->constrained('leads', 'phone')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('did_number_id')->constrained('did_numbers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('traffic_source_id')->constrained('traffic_sources')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
