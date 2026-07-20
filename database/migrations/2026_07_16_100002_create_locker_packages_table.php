<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_packages', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('station_id', 26)->nullable();
            $table->string('code')->unique();
            $table->string('qr_token', 64)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_code')->nullable();
            $table->text('notes')->nullable();
            $table->date('expected_pickup_date');
            $table->string('photo_path')->nullable();
            $table->timestamp('photo_taken_at')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signer_name')->nullable();
            $table->decimal('daily_rate', 10, 2)->default(3);
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('PRENOTATO');
            $table->string('source')->default('desk');
            $table->unsignedBigInteger('cash_movement_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('station_id')
                ->references('id')
                ->on('locker_stations')
                ->nullOnDelete();

            $table->index('status');
            $table->index('expected_pickup_date');
            $table->index(['station_id', 'status', 'received_at'], 'locker_packages_station_status_received_index');
            $table->index('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_packages');
    }
};
