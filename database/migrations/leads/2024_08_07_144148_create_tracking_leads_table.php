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
        Schema::create('tracking_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('phone', false)->primary();
            $table->string('first_name')->nullable()->default('null');
            $table->string('last_name')->nullable()->default('null');
            $table->string('email')->nullable()->default('null@api.com');
            $table->string('type');
            $table->string('zip_code')->nullable()->default('90086');
            $table->string('state')->nullable()->default('CA');
            $table->string('ip')->nullable()->default('127:0:0:1');
            $table->decimal('cpl', 8, 3)->default(0);
            $table->json('data')->nullable();
            $table->uuid('yp_lead_id');
            $table->string('universal_lead_id')->nullable();
            $table->string('trusted_form')->nullable();
            // Foreign Keys
            $table->foreignUlid('campaign_name_id', 255)->nullable()->constrained('lead_metrics', 'campaign_name')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('sub_id')->constrained('subs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pub_id')->constrained('pubs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cc_id')->nullable()->constrained('pub_lists')->cascadeOnUpdate()->nullOnDelete();
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
        Schema::dropIfExists('tracking_leads');
    }
};
