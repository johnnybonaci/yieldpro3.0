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
        Schema::table('media_alpha_responses', function (Blueprint $table) {
            $table->json('ping_request_data')->nullable()->after('ping_sent_at')->comment('Full ping request data');

            $table->json('post_request_data')->nullable()->after('post_sent_at')->comment('Full post request data');
            $table->date('date_history')->index('date');
            $table->renameColumn('phone', 'phone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_alpha_responses', function (Blueprint $table) {
            $table->dropColumn(['ping_request_data', 'post_request_data', 'date_history']);
            $table->renameColumn('phone_id', 'phone');
        });
    }
};
