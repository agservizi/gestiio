<?php

namespace Tests\Unit;

use App\Http\Services\GaraEvalService;
use App\Models\Gara;
use App\Models\GaraRisultato;
use App\Models\GaraSoglia;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GaraEvalServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('gara_risultati');
        Schema::dropIfExists('gara_soglie');
        Schema::dropIfExists('gara_agente');
        Schema::dropIfExists('gare');
        Schema::dropIfExists('contratti');
        Schema::dropIfExists('produzioni_operatori');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_ricalcola_assegna_premio_su_soglia(): void
    {
        $userId = 7;
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'g@test.local',
            'password' => bcrypt('x'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $garaId = DB::table('gare')->insertGetId([
            'nome' => 'Gara test',
            'canale' => 'telefonia',
            'anno' => 2026,
            'mese' => 10,
            'attivo' => 1,
            'ambito' => 'tutti',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gara_soglie')->insert([
            'gara_id' => $garaId,
            'da_valore' => 2,
            'a_valore' => null,
            'premio' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meseKey = '10_2026';
        DB::table('contratti')->insert([
            ['agente_id' => $userId, 'mese_pagamento' => $meseKey, 'pagato' => 0, 'esito_finale' => 'ok', 'created_at' => now(), 'updated_at' => now()],
            ['agente_id' => $userId, 'mese_pagamento' => $meseKey, 'pagato' => 0, 'esito_finale' => 'ok', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $out = app(GaraEvalService::class)->ricalcolaMese(2026, 10, $userId);

        $this->assertGreaterThanOrEqual(1, $out['updated']);
        $r = GaraRisultato::query()->where('gara_id', $garaId)->where('user_id', $userId)->first();
        $this->assertNotNull($r);
        $this->assertSame(2, (int) $r->valore_raggiunto);
        $this->assertEquals(40.0, (float) $r->premio);
        $this->assertEquals(40.0, app(GaraEvalService::class)->importoGareUser($userId, 2026, 10));
    }

    public function test_soglia_zero_senza_contratti(): void
    {
        $userId = 8;
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'g2@test.local',
            'password' => bcrypt('x'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $garaId = DB::table('gare')->insertGetId([
            'nome' => 'Gara empty',
            'canale' => 'telefonia',
            'anno' => 2026,
            'mese' => 10,
            'attivo' => 1,
            'ambito' => 'tutti',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('gara_soglie')->insert([
            'gara_id' => $garaId,
            'da_valore' => 1,
            'a_valore' => null,
            'premio' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(GaraEvalService::class)->ricalcolaMese(2026, 10, $userId);

        $this->assertNull(
            GaraRisultato::query()->where('gara_id', $garaId)->where('user_id', $userId)->first()
        );
    }

    protected function setUpSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('password');
            $t->timestamps();
        });

        Schema::create('gare', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->string('canale');
            $t->unsignedSmallInteger('anno');
            $t->unsignedTinyInteger('mese');
            $t->boolean('attivo')->default(1);
            $t->string('ambito')->default('tutti');
            $t->unsignedBigInteger('listino_id')->nullable();
            $t->unsignedBigInteger('gestore_id')->nullable();
            $t->unsignedBigInteger('telefonia_offerta_id')->nullable();
            $t->unsignedBigInteger('tipo_contratto_id')->nullable();
            $t->string('tipo_attivazione')->nullable();
            $t->boolean('is_business')->nullable();
            $t->timestamps();
        });

        Schema::create('gara_soglie', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('gara_id');
            $t->unsignedInteger('da_valore');
            $t->unsignedInteger('a_valore')->nullable();
            $t->decimal('premio', 10, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('gara_agente', function (Blueprint $t) {
            $t->unsignedBigInteger('gara_id');
            $t->unsignedBigInteger('user_id');
        });

        Schema::create('gara_risultati', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('gara_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedSmallInteger('anno');
            $t->unsignedTinyInteger('mese');
            $t->unsignedInteger('valore_raggiunto')->default(0);
            $t->unsignedBigInteger('gara_soglia_id')->nullable();
            $t->decimal('premio', 10, 2)->default(0);
            $t->unsignedBigInteger('fattura_proforma_id')->nullable();
            $t->timestamp('liquidato_at')->nullable();
            $t->timestamps();
        });

        Schema::create('contratti', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agente_id')->nullable();
            $t->string('mese_pagamento')->nullable();
            $t->boolean('pagato')->default(0);
            $t->string('esito_finale')->nullable();
            $t->unsignedBigInteger('telefonia_offerta_id')->nullable();
            $t->unsignedBigInteger('tipo_contratto_id')->nullable();
            $t->string('categoria_pratica')->nullable();
            $t->timestamps();
        });

        Schema::create('produzioni_operatori', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedBigInteger('user_id');
            $t->unsignedSmallInteger('anno');
            $t->unsignedTinyInteger('mese');
            $t->unsignedBigInteger('fattura_proforma_id')->nullable();
            $t->timestamps();
        });
    }
}
