<?php

use App\Models\EsitoServizioFinanziario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $esiti = ['DA GESTIRE', 'CLIENTE NON ACQUISIBILE', 'CLIENTE ACQUISIBILE', 'FINALIZZATO'];

        foreach ($esiti as $e) {
            $record = new EsitoServizioFinanziario;
            $record->id = Str::slug($e);
            $record->nome = Str::of($e)->lower()->ucfirst();
            $record->attivo = 1;
            $record->colore_hex = '#333333';
            $record->save();
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
