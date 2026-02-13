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
        $esiti = ['DA GESTIRE', 'CLIENTE NON ACQUISIBILE', 'CLIENTE ACQUISIBILE', 'FINALIZZATO'];

        foreach ($esiti as $e) {
            $record = new \App\Models\EsitoServizioFinanziario();
            $record->id=\Illuminate\Support\Str::slug($e);
            $record->nome=\Illuminate\Support\Str::of($e)->lower()->ucfirst();
            $record->attivo=1;
            $record->colore_hex='#333333';
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
