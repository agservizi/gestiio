<?php

use App\Models\NazioneEuropaBrt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('brt_nazioni_europa');

        \Schema::create('brt_nazioni_europa', function (Blueprint $table) {
            $table->string('id', 3)->primary();
            $table->string('nome_nazione')->index();
            $table->char('gruppo', 1);
        });

        NazioneEuropaBrt::create(['id' => 'FRA', 'nome_nazione' => 'Francia', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'NLD', 'nome_nazione' => 'Olanda', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'AUT', 'nome_nazione' => 'Austria', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'HRV', 'nome_nazione' => 'Croazia', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'HUN', 'nome_nazione' => 'Ungheria', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'DEU', 'nome_nazione' => 'Germania', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'CHE', 'nome_nazione' => 'Svizzera*', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'SVN', 'nome_nazione' => 'Slovenia', 'gruppo' => 'A']);

        NazioneEuropaBrt::create(['id' => 'ESP', 'nome_nazione' => 'Spagna', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'BGR', 'nome_nazione' => 'Bulgaria', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'BEL', 'nome_nazione' => 'Belgio', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'POL', 'nome_nazione' => 'Polonia', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'CZE', 'nome_nazione' => 'Repubblica Ceca', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'LUX', 'nome_nazione' => 'Lussemburgo', 'gruppo' => 'B']);

        NazioneEuropaBrt::create(['id' => 'DNK', 'nome_nazione' => 'Danimarca', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'PRT', 'nome_nazione' => 'Portogallo', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'GRC', 'nome_nazione' => 'Grecia', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'SVK', 'nome_nazione' => 'Slovacchia', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'ROU', 'nome_nazione' => 'Romania', 'gruppo' => 'C']);

        NazioneEuropaBrt::create(['id' => 'FIN', 'nome_nazione' => 'Finlandia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'EST', 'nome_nazione' => 'Estonia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'LVA', 'nome_nazione' => 'Lettonia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'LTU', 'nome_nazione' => 'Lituania', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'SWE', 'nome_nazione' => 'Svezia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'IRL', 'nome_nazione' => 'Irlanda', 'gruppo' => 'D']);

        Schema::create('brt_listino_europa', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->decimal('da_peso', 4, 1);
            $table->decimal('a_peso', 4, 1)->nullable();
            $table->decimal('gruppo_a');
            $table->decimal('gruppo_b');
            $table->decimal('gruppo_c');
            $table->decimal('gruppo_d');
            $table->string('tipo')->index();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brt_nazioni_europa');
        Schema::dropIfExists('brt_listino_europa');
    }
};
