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
        Schema::create('bot_leads_duplicates', function (Blueprint $table) {
            $table->unsignedBigInteger('phone', false)->primary();
            $table->string('first_name')->nullable()->default('null');
            $table->string('last_name')->nullable()->default('null');
            $table->string('email')->nullable()->default('null@api.com');
            $table->string('type');
            $table->string('zip_code')->nullable()->default('90086');
            $table->string('state')->nullable();
            $table->json('ip')->nullable();
            $table->string('universal_lead_id')->nullable();
            $table->string('campaign_name_id')->nullable();
            $table->string('trusted_form')->nullable();
            $table->integer('tries')->nullable();
            $table->integer('pub_id')->nullable();
            $table->string('sub_id5')->nullable();
            $table->string('dob')->nullable();
            // Index
            $table->date('date_history')->index('date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_leads_duplicates');
    }
};
