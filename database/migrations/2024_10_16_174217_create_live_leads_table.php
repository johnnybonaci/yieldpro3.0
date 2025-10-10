<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_leads', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique()->index()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('state')->nullable();
            $table->string('type')->nullable();
            $table->string('cpl')->nullable();
            $table->string('campaign_name_id')->nullable();
            $table->unsignedBigInteger('jornaya_id')->nullable();
            $table->string('jornaya_lead_id')->nullable();
            $table->string('jornaya_trusted_form')->nullable();
            $table->unsignedBigInteger('sub_id')->nullable();
            $table->string('sub_name')->nullable();
            $table->unsignedBigInteger('pub_id')->nullable();
            $table->unsignedBigInteger('pub_offer_id')->nullable();
            $table->string('pub_offer_name')->nullable();
            $table->unsignedBigInteger('publisher_id')->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('original_type')->nullable();
            $table->string('original_campaign_name_id')->nullable();
            $table->unsignedBigInteger('original_pub_id')->nullable();
            $table->unsignedBigInteger('original_pub_offer_id')->nullable();
            $table->string('original_pub_offer_name')->nullable();
            $table->unsignedBigInteger('original_publisher_id')->nullable();
            $table->string('original_publisher_name')->nullable();
            $table->string('sub_id5')->nullable();
            $table->date('updated_at_date')->nullable();
            $table->date('created_at_date')->nullable();
            $table->string('convertion_status')->nullable();
            $table->unsignedBigInteger('convertion_traffic_source_id')->nullable();
            $table->string('convertion_traffic_source_name')->nullable();
            $table->date('convertion_updated_at_date')->nullable();
            $table->dateTime('convertion_created_at')->nullable();
            $table->dateTime('convertion_updated_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_leads');
    }
};
