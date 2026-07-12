<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luggage_deposits', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('code')->unique();
            $table->string('qr_token', 64)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->unsignedSmallInteger('bag_count')->default(1);
            $table->json('bag_tags')->nullable();
            $table->text('notes')->nullable();
            $table->date('booking_date');
            $table->dateTime('expected_check_in')->nullable();
            $table->dateTime('expected_check_out')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->decimal('daily_rate', 10, 2)->default(5);
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('PRENOTATO');
            $table->string('source')->default('SPORTELLO');
            $table->unsignedBigInteger('cash_movement_id')->nullable()->unique();
            $table->timestamps();

            $table->index('status');
            $table->index('booking_date');
            $table->index('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luggage_deposits');
    }
};
