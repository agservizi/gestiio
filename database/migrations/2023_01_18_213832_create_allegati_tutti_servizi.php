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
        Schema::create('allegati_tutti_servizi', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
            $table->string('thumbnail')->nullable();
            $table->boolean('per_cliente')->default(0)->index();

            //$table->unsignedBigInteger('allegato_id')->nullable();
            //$table->string('allegato_type')->nullable();
            //$table->index(['allegato_id', 'allegato_type']);
            $table->morphs('allegato');
        });

        Schema::table('servizi_finanziari', function (Blueprint $table) {
            $table->string('uid');
        });


        foreach (\App\Models\ServizioFinanziario::get() as $s) {
            $s->uid = \Illuminate\Support\Str::uuid();
            $s->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('allegati_tutti_servizi');
        Schema::table('servizi_finanziari', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

    }
};
