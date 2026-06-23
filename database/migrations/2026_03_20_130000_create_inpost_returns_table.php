<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inpost_returns', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->string('customer_reference')->nullable();
            $table->string('remote_id')->nullable();
            $table->string('status')->nullable();
            $table->string('receiver_name');
            $table->string('receiver_email')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('point_id');
            $table->string('point_label')->nullable();
            $table->longText('payload_json')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inpost_returns');
    }
};
