<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inpost_pickups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->string('customer_reference')->nullable();
            $table->string('remote_id')->nullable();
            $table->string('status')->nullable();
            $table->date('pickup_date');
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone');
            $table->string('street');
            $table->string('building_number')->nullable();
            $table->string('post_code');
            $table->string('city');
            $table->string('country_code', 2)->default('PL');
            $table->unsignedInteger('parcel_count')->default(1);
            $table->string('note', 500)->nullable();
            $table->longText('payload_json')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inpost_pickups');
    }
};
