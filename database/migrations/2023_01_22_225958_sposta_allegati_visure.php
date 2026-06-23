<?php

use App\Models\AllegatoServizio;
use App\Models\AllegatoVisura;
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
        foreach (AllegatoVisura::get() as $record) {
            $allegato = new AllegatoServizio;
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
