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
        Schema::table('bot_leads', function (Blueprint $table) {
            $table->boolean('rejected')->default(false)->after('sub_id5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_leads', function (Blueprint $table) {
            $table->dropColumn('rejected');
        });
    }
};
