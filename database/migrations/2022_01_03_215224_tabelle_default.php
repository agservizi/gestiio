<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TabelleDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->upRegistroLogin();
        $this->upRegistroSegnalazioni();
        $this->upComuni();

        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('val')->nullable();
            $table->char('type', 20)->default('string');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function upRegistroLogin()
    {
        Schema::create('registro_login', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email');
            $table->ipAddress('ip');
            $table->string('dominio', 50)->nullable();
            $table->boolean('riuscito')->default(0);
            $table->boolean('remember')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('user_agent')->nullable();

        });
        Schema::create('registro_login_impersona', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->foreign('registro_id')->references('id')->on('registro_login')->onDelete('cascade');
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function upRegistroSegnalazioni()
    {

        Schema::create('registro_segnalazioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('user_id');
            $table->string('titolo');
            $table->text('testo')->nullable();
            $table->text('url')->nullable();
            $table->unsignedSmallInteger('urgenza')->default(0)->index();
            $table->unsignedSmallInteger('risolto')->default(0)->index();
        });

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function upComuni()
    {
        //


        Schema::create('elenco_comuni', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('regione_id')->default(null);
            $table->unsignedBigInteger('provincia_id')->default(null);
            $table->string('comune')->default(null);
            $table->string('cap', 5)->nullable();
            $table->string('codice_catastale', 4)->default(null);
            $table->char('targa', 4)->default('');
            $table->boolean('soppresso')->default(0)->index();
            $table->point('location')->nullable();
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();


        });


        Schema::create('elenco_province', function (Blueprint $table) {
            $table->id();
            $table->string('provincia')->default(null);
            $table->string('sigla_automobilistica')->default(null);
            $table->unsignedBigInteger('id_regione')->default(null);
            $table->string('regione')->default(null);
            $table->point('location')->nullable();


        });

        Schema::create('elenco_nazioni', function (Blueprint $table) {

            $table->string('alpha2', 2)->primary();
            $table->string('alpha3', 3);
            $table->string('langEN', 45);
            $table->string('langIT', 45)->index();
            $table->string('nazionalitaEN', 45)->nullable();
            $table->string('nazionalitaIT', 50)->nullable()->index();


        });


    }

}
