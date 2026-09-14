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
        Schema::create('favorite_places', function (Blueprint $table) {
            $table->id();

            $table->string('place_id');

            $table->string('name')->nullable();

            $table->text('address')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->unique(['place_id', 'ip_address']);
            $table->index('place_id');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_places');
    }
};