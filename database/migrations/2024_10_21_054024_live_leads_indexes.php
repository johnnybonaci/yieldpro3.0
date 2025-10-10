<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_leads', function (Blueprint $table) {
            $table->index('sub_id');
            $table->index('pub_id');
            $table->index('pub_offer_id');
            $table->index('type');
            $table->index('publisher_id');
            $table->index('sub_id5');
            $table->index('campaign_name_id');
            $table->index('created_at_date');
            $table->index('created_at');

            $table->index(['sub_id', 'pub_id', 'pub_offer_id', 'type', 'publisher_id', 'sub_id5', 'campaign_name_id', 'created_at_date'], 'live_leads_full_index');
        });
    }

    public function down(): void
    {
        Schema::table('live_leads', function (Blueprint $table) {});
    }
};
