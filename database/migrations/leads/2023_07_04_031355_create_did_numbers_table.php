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
        Schema::create('did_numbers', function (Blueprint $table) {
            $table->unsignedBigInteger('id', false)->primary();
            $table->string('description')->nullable();
            $table->string('campaign_name')->nullable();
            // Foreigns keys
            $table->foreignId('sub_id')->nullable()->constrained('subs')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('pub_id')->nullable()->constrained('pubs')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('traffic_source_id')->nullable()->constrained('traffic_sources')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->cascadeOnUpdate()->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('did_numbers');
    }
};
