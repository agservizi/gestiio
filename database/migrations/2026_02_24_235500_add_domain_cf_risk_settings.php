<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'blocco_contratti_telefonia_verifica_cf_attivo' => '0',
            'blocco_contratti_telefonia_cf_morosita' => '',
            'blocco_contratti_telefonia_cf_blacklist' => '',
            'blocco_contratti_telefonia_cf_credit_check' => '',
            'blocco_contratti_telefonia_cf_morosita_per_gestore' => '',
            'blocco_contratti_telefonia_cf_blacklist_per_gestore' => '',
            'blocco_contratti_telefonia_cf_credit_check_per_gestore' => '',
            'blocco_contratti_energia_verifica_cf_attivo' => '0',
            'blocco_contratti_energia_cf_morosita' => '',
            'blocco_contratti_energia_cf_blacklist' => '',
            'blocco_contratti_energia_cf_credit_check' => '',
            'blocco_contratti_energia_cf_morosita_per_gestore' => '',
            'blocco_contratti_energia_cf_blacklist_per_gestore' => '',
            'blocco_contratti_energia_cf_credit_check_per_gestore' => '',
        ];

        foreach ($defaults as $name => $val) {
            $exists = DB::table('settings')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            DB::table('settings')->insert([
                'name' => $name,
                'val' => $val,
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('name', [
            'blocco_contratti_telefonia_verifica_cf_attivo',
            'blocco_contratti_telefonia_cf_morosita',
            'blocco_contratti_telefonia_cf_blacklist',
            'blocco_contratti_telefonia_cf_credit_check',
            'blocco_contratti_telefonia_cf_morosita_per_gestore',
            'blocco_contratti_telefonia_cf_blacklist_per_gestore',
            'blocco_contratti_telefonia_cf_credit_check_per_gestore',
            'blocco_contratti_energia_verifica_cf_attivo',
            'blocco_contratti_energia_cf_morosita',
            'blocco_contratti_energia_cf_blacklist',
            'blocco_contratti_energia_cf_credit_check',
            'blocco_contratti_energia_cf_morosita_per_gestore',
            'blocco_contratti_energia_cf_blacklist_per_gestore',
            'blocco_contratti_energia_cf_credit_check_per_gestore',
        ])->delete();
    }
};
