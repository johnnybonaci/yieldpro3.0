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
        Schema::create('lead_metrics', function (Blueprint $table) {
            $table->string('campaign_name')->primary();
            $table->string('utm_source')->nullable()->default('null');
            $table->string('utm_medium')->nullable()->default('null');
            $table->string('utm_content')->nullable()->default('null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_metrics');
    }
};
