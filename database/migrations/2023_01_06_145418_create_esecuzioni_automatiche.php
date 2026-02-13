<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esecuzioni_automatiche', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedTinyInteger('una_volta_al_giorno')->nullable();
            $table->unsignedTinyInteger('una_volta_al_mese')->nullable();
        });

        $r = new \App\Models\EsecuzioneAutomatica();
        $r->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esecuzioni_automatiche');
    }
};
