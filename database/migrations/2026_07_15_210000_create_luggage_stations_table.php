<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luggage_stations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('daily_rate', 10, 2)->default(5);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('max_capacity')->default(50);
            $table->unsignedInteger('min_days')->default(1);
            $table->unsignedInteger('max_bags_per_booking')->default(10);
            $table->boolean('online_booking_enabled')->default(false);
            $table->boolean('api_enabled')->default(false);
            $table->string('api_key_hash')->nullable();
            $table->string('api_key_prefix', 12)->nullable();
            $table->timestamp('api_requested_at')->nullable();
            $table->timestamp('api_enabled_at')->nullable();
            $table->timestamps();
        });

        Schema::table('luggage_deposits', function (Blueprint $table) {
            $table->string('station_id', 26)->nullable()->after('id');
            $table->foreign('station_id')
                ->references('id')
                ->on('luggage_stations')
                ->nullOnDelete();
            $table->index(['station_id', 'booking_date', 'status'], 'luggage_deposits_station_booking_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('luggage_deposits', function (Blueprint $table) {
            $table->dropIndex('luggage_deposits_station_booking_status_index');
            $table->dropForeign(['station_id']);
            $table->dropColumn('station_id');
        });

        Schema::dropIfExists('luggage_stations');
    }
};
