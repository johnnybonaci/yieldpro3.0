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
        Schema::create('media_alpha_responses', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('placement_id')->index();
            $table->string('leadid_id')->nullable();

            // PING data
            $table->string('ping_id')->nullable();
            $table->json('ping_buyers')->nullable()->comment('Array of ping buyers');
            $table->decimal('ping_time', 8, 3)->nullable()->comment('Ping response time in seconds');
            $table->string('ping_status')->default('pending')->comment('pending, success, error');
            $table->text('ping_error')->nullable()->comment('Ping error if any');
            $table->timestamp('ping_sent_at')->nullable();
            $table->json('ping_raw_response')->nullable()->comment('Full ping response');

            // POST data
            $table->json('post_buyers')->nullable()->comment('Array of post buyers');
            $table->string('post_status')->default('pending')->comment('pending, succeeded, failed');
            $table->decimal('post_revenue', 10, 2)->nullable()->comment('Total post revenue');
            $table->decimal('post_time', 8, 3)->nullable()->comment('Post response time in seconds');
            $table->text('post_error')->nullable()->comment('Post error if any');
            $table->timestamp('post_sent_at')->nullable();
            $table->json('post_raw_response')->nullable()->comment('Full post response');

            // Calculated statistics
            $table->integer('total_buyers')->default(0)->comment('Total buyers who responded');
            $table->integer('accepted_buyers')->default(0)->comment('Buyers who accepted');
            $table->integer('rejected_buyers')->default(0)->comment('Buyers who rejected');
            $table->decimal('highest_bid', 10, 2)->nullable()->comment('Highest bid');
            $table->string('winning_buyer')->nullable()->comment('Winning buyer');

            // Metadata
            $table->string('status')->default('processing')->comment('processing, completed, failed');
            $table->timestamps();

            // Indexes
            $table->index(['placement_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['ping_status', 'post_status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_alpha_responses');
    }
};
