<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luggage_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->string('luggage_deposit_id');
            $table->foreign('luggage_deposit_id')->references('id')->on('luggage_deposits')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('currency', 3)->default('EUR');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::table('luggage_deposits', function (Blueprint $table) {
            $table->foreign('cash_movement_id')->references('id')->on('luggage_cash_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('luggage_deposits', function (Blueprint $table) {
            $table->dropForeign(['cash_movement_id']);
        });

        Schema::dropIfExists('luggage_cash_movements');
    }
};
