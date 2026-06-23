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
            $table->string('id', 2)->primary();
            $table->string('nome_nazione')->index();
            $table->char('gruppo', 1);
        });

        NazioneEuropaBrt::create(['id' => 'FR', 'nome_nazione' => 'Francia', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'NL', 'nome_nazione' => 'Olanda', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'AT', 'nome_nazione' => 'Austria', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'HR', 'nome_nazione' => 'Croazia', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'HU', 'nome_nazione' => 'Ungheria', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'DE', 'nome_nazione' => 'Germania', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'CH', 'nome_nazione' => 'Svizzera*', 'gruppo' => 'A']);
        NazioneEuropaBrt::create(['id' => 'SI', 'nome_nazione' => 'Slovenia', 'gruppo' => 'A']);

        NazioneEuropaBrt::create(['id' => 'ES', 'nome_nazione' => 'Spagna', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'BG', 'nome_nazione' => 'Bulgaria', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'BE', 'nome_nazione' => 'Belgio', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'PL', 'nome_nazione' => 'Polonia', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'CZ', 'nome_nazione' => 'Repubblica Ceca', 'gruppo' => 'B']);
        NazioneEuropaBrt::create(['id' => 'LU', 'nome_nazione' => 'Lussemburgo', 'gruppo' => 'B']);

        NazioneEuropaBrt::create(['id' => 'DK', 'nome_nazione' => 'Danimarca', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'PT', 'nome_nazione' => 'Portogallo', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'GR', 'nome_nazione' => 'Grecia', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'SK', 'nome_nazione' => 'Slovacchia', 'gruppo' => 'C']);
        NazioneEuropaBrt::create(['id' => 'RO', 'nome_nazione' => 'Romania', 'gruppo' => 'C']);

        NazioneEuropaBrt::create(['id' => 'FI', 'nome_nazione' => 'Finlandia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'EE', 'nome_nazione' => 'Estonia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'LV', 'nome_nazione' => 'Lettonia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'LT', 'nome_nazione' => 'Lituania', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'SE', 'nome_nazione' => 'Svezia', 'gruppo' => 'D']);
        NazioneEuropaBrt::create(['id' => 'IE', 'nome_nazione' => 'Irlanda', 'gruppo' => 'D']);

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
};
