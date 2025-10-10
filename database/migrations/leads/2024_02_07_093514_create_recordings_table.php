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
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->string('url')->nullable();
            $table->string('record')->nullable();
            $table->text('transcript')->nullable();
            $table->json('multiple')->nullable();
            $table->json('qa_status')->nullable();
            $table->json('qa_td_status')->nullable();
            $table->boolean('status')->default(2);
            $table->boolean('billable')->default(0);
            $table->boolean('insurance')->default(2);
            // index
            $table->date('date_history')->index('date_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
