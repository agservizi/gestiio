<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_agent_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('billing_month');
            $table->decimal('amount', 8, 2);
            $table->unsignedBigInteger('movimento_portafoglio_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'billing_month']);
        });

        Schema::create('locker_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->string('locker_package_id', 26);
            $table->foreign('locker_package_id')->references('id')->on('locker_packages')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('currency', 3)->default('EUR');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::table('locker_packages', function (Blueprint $table) {
            $table->foreign('cash_movement_id')->references('id')->on('locker_cash_movements')->nullOnDelete();
            $table->foreign('received_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locker_packages', function (Blueprint $table) {
            $table->dropForeign(['cash_movement_id']);
            $table->dropForeign(['received_by_user_id']);
        });

        Schema::dropIfExists('locker_cash_movements');
        Schema::dropIfExists('locker_agent_subscriptions');
    }
};
