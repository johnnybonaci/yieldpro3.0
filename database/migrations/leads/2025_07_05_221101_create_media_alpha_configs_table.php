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
        Schema::create('media_alpha_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_token');
            $table->string('placement_id');
            $table->integer('version');
            $table->string('base_url');
            $table->string('ping_endpoint');
            $table->string('post_endpoint');
            $table->string('source_url');
            $table->json('tcpa_config');
            $table->json('default_mapping')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'placement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_alpha_configs');
    }
};
