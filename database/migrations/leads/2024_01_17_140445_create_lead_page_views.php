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
        Schema::create('lead_page_views', function (Blueprint $table) {
            $table->id();

            $table->string('ip');
            $table->string('campaign_name')->nullable();
            $table->text('url');
            $table->date('date_history')->index('date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_page_views');
    }
};
