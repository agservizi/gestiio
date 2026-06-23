<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodotto_energia_generico', function (Blueprint $table) {
            $table->boolean('provenienza_mercato_libero')->default(0)->after('pod');
            $table->boolean('uso_non_professionale_luce')->default(0)->after('provenienza_mercato_libero');
            $table->string('consumo_annuo_luce')->nullable()->after('uso_non_professionale_luce');
            $table->string('potenza_contrattuale')->nullable()->after('consumo_annuo_luce');
            $table->string('livello_tensione')->nullable()->after('potenza_contrattuale');
            $table->string('attuale_societa_luce')->nullable()->after('livello_tensione');

            $table->boolean('uso_non_professionale_gas')->default(0)->after('pdr');
            $table->string('consumo_annuo_gas')->nullable()->after('uso_non_professionale_gas');
            $table->string('attuale_societa_gas')->nullable()->after('consumo_annuo_gas');
            $table->string('profilo_consumo')->nullable()->after('attuale_societa_gas');
            $table->string('posizione_contatore')->nullable()->after('profilo_consumo');
            $table->string('consumo_annuo')->nullable()->after('posizione_contatore');
            $table->string('matricola_contatore')->nullable()->after('consumo_annuo');
            $table->boolean('riscaldamento')->default(0)->after('matricola_contatore');
            $table->boolean('cottura_acqua_calda')->default(0)->after('riscaldamento');
        });

        Schema::table('prodotto_energia_illumia', function (Blueprint $table) {
            $table->boolean('provenienza_mercato_libero')->default(0)->after('pod');
            $table->boolean('uso_non_professionale_luce')->default(0)->after('provenienza_mercato_libero');
            $table->string('consumo_annuo_luce')->nullable()->after('uso_non_professionale_luce');
            $table->string('potenza_contrattuale')->nullable()->after('consumo_annuo_luce');
            $table->string('livello_tensione')->nullable()->after('potenza_contrattuale');
            $table->string('attuale_societa_luce')->nullable()->after('livello_tensione');

            $table->boolean('uso_non_professionale_gas')->default(0)->after('pdr');
            $table->string('consumo_annuo_gas')->nullable()->after('uso_non_professionale_gas');
            $table->string('attuale_societa_gas')->nullable()->after('consumo_annuo_gas');
            $table->string('profilo_consumo')->nullable()->after('attuale_societa_gas');
            $table->string('posizione_contatore')->nullable()->after('profilo_consumo');
            $table->string('consumo_annuo')->nullable()->after('posizione_contatore');
            $table->string('matricola_contatore')->nullable()->after('consumo_annuo');
            $table->boolean('riscaldamento')->default(0)->after('matricola_contatore');
            $table->boolean('cottura_acqua_calda')->default(0)->after('riscaldamento');
        });
    }

    public function down(): void
    {
        Schema::table('prodotto_energia_generico', function (Blueprint $table) {
            $table->dropColumn([
                'provenienza_mercato_libero',
                'uso_non_professionale_luce',
                'consumo_annuo_luce',
                'potenza_contrattuale',
                'livello_tensione',
                'attuale_societa_luce',
                'uso_non_professionale_gas',
                'consumo_annuo_gas',
                'attuale_societa_gas',
                'profilo_consumo',
                'posizione_contatore',
                'consumo_annuo',
                'matricola_contatore',
                'riscaldamento',
                'cottura_acqua_calda',
            ]);
        });

        Schema::table('prodotto_energia_illumia', function (Blueprint $table) {
            $table->dropColumn([
                'provenienza_mercato_libero',
                'uso_non_professionale_luce',
                'consumo_annuo_luce',
                'potenza_contrattuale',
                'livello_tensione',
                'attuale_societa_luce',
                'uso_non_professionale_gas',
                'consumo_annuo_gas',
                'attuale_societa_gas',
                'profilo_consumo',
                'posizione_contatore',
                'consumo_annuo',
                'matricola_contatore',
                'riscaldamento',
                'cottura_acqua_calda',
            ]);
        });
    }
};
