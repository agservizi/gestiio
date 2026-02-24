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
            'blocco_contratti_verifica_cf_attivo',
            'blocco_contratti_cf_morosita',
            'blocco_contratti_cf_blacklist',
            'blocco_contratti_cf_credit_check',
        ])->delete();
    }
};
