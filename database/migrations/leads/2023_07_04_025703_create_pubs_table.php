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
        Schema::create('pubs', function (Blueprint $table) {
            $table->id('id');
            // Foreigns keys
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pub_list_id')->constrained('pub_lists')->cascadeOnUpdate()->restrictOnDelete();
            $table->json('setup');
            $table->json('interleave')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pubs');
    }
};
