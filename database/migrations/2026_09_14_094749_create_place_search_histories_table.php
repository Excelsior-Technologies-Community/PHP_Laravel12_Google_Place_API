<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('place_search_histories', function (Blueprint $table) {
            $table->id();

            $table->string('query');

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index('query');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_search_histories');
    }
};