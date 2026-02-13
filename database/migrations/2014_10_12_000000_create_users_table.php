<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->index();
            $table->string('cognome')->index();
            $table->string('email')->index();
            $table->string('telefono')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('ultimo_accesso')->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->string('iban')->nullable();
            $table->boolean('paga_con_paypal')->default(0);
            $table->json('extra')->nullable();



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
