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
            $table->foreignId('sub_id2')->after('pub_id')->nullable()->constrained('pubs')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('cc_id')->after('pub_id')->nullable()->constrained('pub_lists')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('cpl', 8, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_id2');
            $table->dropConstrainedForeignId('cc_id');
            $table->decimal('cpl', 8, 2)->change();
        });
    }
};
