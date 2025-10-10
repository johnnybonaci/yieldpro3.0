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
        Schema::create('convertions', function (Blueprint $table) {
            $table->id();
            $table->boolean('outside')->default(false);
            $table->string('status')->nullable()->default('No Contact');
            $table->decimal('revenue', 8, 2)->default(0);
            $table->decimal('cpl', 8, 2)->default(0);
            $table->unsignedInteger('durations')->default(0);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('converted')->default(0);
            $table->unsignedInteger('answered')->default(0);
            $table->string('terminating_phone')->nullable();
            // Foreigns keys
            $table->foreignId('did_number_id')->constrained('did_numbers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('phone_id')->constrained('leads', 'phone')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('buyers')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('traffic_source_id')->constrained('traffic_sources')->cascadeOnUpdate()->restrictOnDelete();
            // index
            $table->date('date_history')->index('date_history');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convertions');
    }
};
