<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('phone_id')->nullable();
            $table->unsignedBigInteger('convertion_id')->unique()->nullable();
            $table->boolean('outside')->nullable();
            $table->integer('answered')->nullable();
            $table->string('status')->nullable()->default('No Contact');
            $table->decimal('revenue')->nullable();
            $table->decimal('cpl')->nullable();
            $table->integer('durations')->nullable();
            $table->integer('calls')->nullable();
            $table->integer('converted')->nullable();
            $table->string('terminating_phone')->nullable();
            $table->unsignedBigInteger('did_number_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('buyer_name')->nullable();
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->string('offer_name')->nullable();

            $table->string('lead_first_name')->nullable();
            $table->string('lead_last_name')->nullable();
            $table->string('lead_email')->nullable();
            $table->string('lead_type')->nullable();
            $table->unsignedBigInteger('lead_sub_id')->nullable();
            $table->unsignedBigInteger('lead_pub_id')->nullable();
            $table->unsignedBigInteger('lead_publisher_id')->nullable();
            $table->string('lead_campaign_name_id')->nullable();
            $table->date('lead_created_at_date')->nullable();
            $table->date('lead_updated_at_date')->nullable();
            $table->decimal('lead_cpl', 8, 3)->nullable();
            $table->string('lead_sub_id5')->nullable();

            $table->unsignedBigInteger('traffic_source_id')->nullable();
            $table->string('traffic_source_name')->nullable();
            $table->date('td_created_at_date')->nullable();
            $table->dateTime('td_created_at')->nullable();
            $table->dateTime('td_updated_at')->nullable();

            $table->unsignedBigInteger('recording_id')->nullable();
            $table->string('url')->nullable();
            $table->string('record')->nullable();
            $table->text('transcript')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->integer('ai_sale_status')->nullable();
            $table->integer('ai_insurance_status')->nullable();
            $table->integer('ai_status')->nullable();
            $table->string('state')->nullable();
            $table->json('ai_qa_analysis')->nullable();
            $table->json('td_qa_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
