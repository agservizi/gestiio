<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luggage_settings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('daily_rate', 10, 2)->default(5);
            $table->unsignedInteger('max_capacity')->default(50);
            $table->unsignedInteger('min_days')->default(1);
            $table->unsignedInteger('max_bags_per_booking')->default(10);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggage_settings');
    }
};
