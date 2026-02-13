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
        foreach (\App\Models\AllegatoVisura::get() as $record) {
            $allegato = new \App\Models\AllegatoServizio();
            $allegato->allegato_type = 'App\Models\Visura';
            $allegato->allegato_id = $record->visura_id;
            $allegato->per_cliente = $record->per_cliente;
            $allegato->thumbnail = $record->thumbnail;
            $allegato->tipo_file = $record->tipo_file;
            $allegato->dimensione_file = $record->dimensione_file;
            $allegato->path_filename = $record->path_filename;
            $allegato->filename_originale = $record->filename_originale;
            $allegato->uid = $record->uid;
            $allegato->saveQuietly();
        }
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
