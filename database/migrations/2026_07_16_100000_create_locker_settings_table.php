<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_settings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->decimal('daily_rate', 10, 2)->default(3);
            $table->unsignedInteger('max_capacity')->default(100);
            $table->unsignedInteger('min_days')->default(1);
            $table->unsignedInteger('max_packages_per_booking')->default(5);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_settings');
    }
};
