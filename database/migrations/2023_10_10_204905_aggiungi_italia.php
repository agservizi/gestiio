<?php

use App\Models\NazioneEuropaBrt;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        NazioneEuropaBrt::create(['id' => 'ITA', 'nome_nazione' => 'Italia', 'gruppo' => ' ']);

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
