<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
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
            // Legacy global keys kept for backward compatibility fallback.
            'blocco_contratti_verifica_cf_attivo' => '0',
            'blocco_contratti_cf_morosita' => '',
            'blocco_contratti_cf_blacklist' => '',
            'blocco_contratti_cf_credit_check' => '',
        ];

        foreach ($defaults as $name => $val) {
            $existing = DB::table('settings')->where('name', $name)->first();

            if ($existing) {
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

    /**
     * Reverse the migrations.
     */
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
            'blocco_contratti_verifica_cf_attivo',
            'blocco_contratti_cf_morosita',
            'blocco_contratti_cf_blacklist',
            'blocco_contratti_cf_credit_check',
        ])->delete();
    }
};
