<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_stations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('daily_rate', 10, 2)->default(3);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('max_capacity')->default(100);
            $table->unsignedInteger('min_days')->default(1);
            $table->unsignedInteger('max_packages_per_booking')->default(5);
            $table->boolean('online_intake_enabled')->default(false);
            $table->boolean('api_enabled')->default(false);
            $table->string('api_key_hash')->nullable();
            $table->string('api_key_prefix', 12)->nullable();
            $table->timestamp('api_requested_at')->nullable();
            $table->timestamp('api_enabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_stations');
    }
};
