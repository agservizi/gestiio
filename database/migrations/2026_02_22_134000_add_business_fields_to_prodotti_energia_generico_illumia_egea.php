<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prodotto_energia_generico', function (Blueprint $table) {
            $table->string('partita_iva')->nullable()->after('nome');
            $table->string('forma_giuridica')->nullable()->after('partita_iva');
            $table->string('cellulare')->nullable()->after('forma_giuridica');
            $table->string('fax')->nullable()->after('cellulare');
            $table->string('nome_cognome_referente')->nullable()->after('fax');
            $table->string('codice_fiscale_referente')->nullable()->after('nome_cognome_referente');
            $table->string('telefono_referente')->nullable()->after('codice_fiscale_referente');
        });

        Schema::table('prodotto_energia_illumia', function (Blueprint $table) {
            $table->string('partita_iva')->nullable()->after('nome');
            $table->string('forma_giuridica')->nullable()->after('partita_iva');
            $table->string('cellulare')->nullable()->after('forma_giuridica');
            $table->string('fax')->nullable()->after('cellulare');
            $table->string('nome_cognome_referente')->nullable()->after('fax');
            $table->string('codice_fiscale_referente')->nullable()->after('nome_cognome_referente');
            $table->string('telefono_referente')->nullable()->after('codice_fiscale_referente');
        });

        Schema::table('prodotto_energia_egea', function (Blueprint $table) {
            $table->string('partita_iva')->nullable()->after('nome');
            $table->string('forma_giuridica')->nullable()->after('partita_iva');
            $table->string('cellulare')->nullable()->after('forma_giuridica');
            $table->string('fax')->nullable()->after('cellulare');
            $table->string('nome_cognome_referente')->nullable()->after('fax');
            $table->string('codice_fiscale_referente')->nullable()->after('nome_cognome_referente');
            $table->string('telefono_referente')->nullable()->after('codice_fiscale_referente');
        });
    }

    public function down(): void
    {
        Schema::table('prodotto_energia_generico', function (Blueprint $table) {
            $table->dropColumn([
                'partita_iva',
                'forma_giuridica',
                'cellulare',
                'fax',
                'nome_cognome_referente',
                'codice_fiscale_referente',
                'telefono_referente',
            ]);
        });

        Schema::table('prodotto_energia_illumia', function (Blueprint $table) {
            $table->dropColumn([
                'partita_iva',
                'forma_giuridica',
                'cellulare',
                'fax',
                'nome_cognome_referente',
                'codice_fiscale_referente',
                'telefono_referente',
            ]);
        });

        Schema::table('prodotto_energia_egea', function (Blueprint $table) {
            $table->dropColumn([
                'partita_iva',
                'forma_giuridica',
                'cellulare',
                'fax',
                'nome_cognome_referente',
                'codice_fiscale_referente',
                'telefono_referente',
            ]);
        });
    }
};

