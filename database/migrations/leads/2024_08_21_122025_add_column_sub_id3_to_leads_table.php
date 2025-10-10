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
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignUlid('sub_id3', 255)->after('sub_id2')->nullable()->constrained('lead_metrics', 'campaign_name')->cascadeOnUpdate()->nullOnDelete();
            $table->string('sub_id4')->after('sub_id3')->nullable();
            $table->string('sub_id5')->after('sub_id2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_id3');
            $table->dropColumn('sub_id4');
            $table->dropColumn('sub_id5');
        });
    }
};
