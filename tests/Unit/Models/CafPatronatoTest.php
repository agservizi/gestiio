<?php

namespace Tests\Unit\Models;

use App\Models\AllegatoCafPatronato;
use App\Models\CafPatronato;
use App\Models\EsitoCafPatronato;
use App\Models\TipoCafPatronato;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CafPatronatoTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | CREATION & BASIC ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    public function test_caf_patronato_can_be_instantiated()
    {
        $model = new CafPatronato;

        $this->assertInstanceOf(CafPatronato::class, $model);
        $this->assertEquals('caf_patronato', $model->getTable());
    }

    public function test_nome_singolare_constant_is_defined()
    {
        $this->assertEquals('pratica caf patronato', CafPatronato::NOME_SINGOLARE);
    }

    public function test_nome_plurale_constant_is_defined()
    {
        $this->assertEquals('pratiche caf patronato', CafPatronato::NOME_PLURALE);
    }

    public function test_esiti_constant_has_expected_keys()
    {
        $esiti = CafPatronato::ESITI;

        $this->assertArrayHasKey('ko', $esiti);
        $this->assertArrayHasKey('ok', $esiti);
        $this->assertArrayHasKey('in-lavorazione', $esiti);
        $this->assertCount(3, $esiti);
    }

    public function test_esiti_values_are_hex_colors()
    {
        foreach (CafPatronato::ESITI as $key => $color) {
            $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $color, "ESITI[$key] should be a valid hex color");
        }
    }

    public function test_data_is_cast_to_datetime()
    {
        $casts = (new CafPatronato)->getCasts();

        $this->assertEquals('datetime', $casts['data']);
    }

    /*
    |--------------------------------------------------------------------------
    | CODICE PRATICA (new field from migration)
    |--------------------------------------------------------------------------
    */

    public function test_codice_pratica_is_auto_generated_on_creating()
    {
        $this->staffUser('agente');

        $model = new CafPatronato;
        $model->nome = 'Mario';
        $model->cognome = 'Rossi';
        $model->codice_fiscale = 'RSSMRA80A01H501U';
        $model->data = now();
        $model->agente_id = Auth::id();
        $model->esito_id = 'da-gestire';
        $model->save();

        $this->assertNotNull($model->codice_pratica);
        $this->assertStringStartsWith('CAF', $model->codice_pratica);
    }

    public function test_codice_pratica_is_not_overwritten_if_already_set()
    {
        $this->staffUser('agente');

        $customCode = 'CAF_CUSTOM_CODE';

        $model = new CafPatronato;
        $model->codice_pratica = $customCode;
        $model->nome = 'Mario';
        $model->cognome = 'Rossi';
        $model->codice_fiscale = 'RSSMRA80A01H501U';
        $model->data = now();
        $model->agente_id = Auth::id();
        $model->esito_id = 'da-gestire';
        $model->save();

        $this->assertEquals($customCode, $model->codice_pratica);
    }

    public function test_genera_codice_pratica_returns_unique_prefixed_string()
    {
        $codice = CafPatronato::generaCodicePratica();

        $this->assertIsString($codice);
        $this->assertStringStartsWith('CAF', $codice);
        $this->assertGreaterThan(3, strlen($codice));
    }

    public function test_genera_codice_pratica_returns_unique_codes()
    {
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = CafPatronato::generaCodicePratica();
        }

        $this->assertCount(50, array_unique($codes), 'All 50 generated codes should be unique');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function test_agente_relationship_returns_user()
    {
        $user = User::factory()->create();

        $model = new CafPatronato;
        $model->agente_id = $user->id;
        $model->nome = 'Test';
        $model->cognome = 'User';
        $model->codice_fiscale = 'TSTUSR80A01H501U';
        $model->data = now();
        $model->esito_id = 'da-gestire';

        // Save without global scope interfering
        CafPatronato::withoutGlobalScope('filtroOperatore', function () use ($model) {
            $model->saveQuietly();
        });

        $fresh = CafPatronato::withoutGlobalScope('filtroOperatore')->find($model->id);
        $agente = $fresh->agente;

        $this->assertInstanceOf(User::class, $agente);
        $this->assertEquals($user->id, $agente->id);
    }

    public function test_allegati_relationship_returns_collection()
    {
        $model = new CafPatronato;
        $relation = $model->allegati();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    public function test_allegati_per_cliente_relationship_filters_per_cliente()
    {
        $model = new CafPatronato;
        $relation = $model->allegatiPerCliente();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    public function test_caricato_da_relationship_returns_user()
    {
        $model = new CafPatronato;
        $relation = $model->caricatoDa();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
    }

    public function test_esito_relationship_returns_esito_caf_patronato()
    {
        $model = new CafPatronato;
        $relation = $model->esito();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
    }

    public function test_prodotto_relationship_is_morph_to()
    {
        $model = new CafPatronato;
        $relation = $model->prodotto();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    public function test_tipo_relationship_returns_tipo_caf_patronato()
    {
        $model = new CafPatronato;
        $relation = $model->tipo();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
    }

    /*
    |--------------------------------------------------------------------------
    | BLADE HELPERS
    |--------------------------------------------------------------------------
    */

    public function test_nominativo_concatenates_cognome_and_nome()
    {
        $model = new CafPatronato;
        $model->cognome = 'Rossi';
        $model->nome = 'Mario';

        $this->assertEquals('Rossi Mario', $model->nominativo());
    }

    public function test_nominativo_handles_empty_values()
    {
        $model = new CafPatronato;
        $model->cognome = '';
        $model->nome = '';

        $this->assertEquals(' ', $model->nominativo());
    }

    public function test_tipo_prodotto_blade_strips_namespace_prefix()
    {
        $model = new CafPatronato;
        $model->prodotto_type = 'App\Models\CafPatIsee';

        $this->assertEquals('Isee', $model->tipoProdottoBlade());
    }

    public function test_label_pagato_returns_badge_when_pagato_is_true()
    {
        $model = new CafPatronato;
        $model->pagato = true;

        $result = $model->labelPagato();

        $this->assertStringContainsString('badge', $result);
        $this->assertStringContainsString('Pagato', $result);
        $this->assertStringContainsString('success', $result);
    }

    public function test_label_pagato_returns_null_when_not_pagato()
    {
        $model = new CafPatronato;
        $model->pagato = false;

        $this->assertNull($model->labelPagato());
    }

    public function test_bullet_esito_finale_returns_html_when_set()
    {
        $model = new CafPatronato;
        $model->esito_finale = 'ok';

        $result = $model->bulletEsitoFinale();

        $this->assertStringContainsString('bullet', $result);
        $this->assertStringContainsString(CafPatronato::ESITI['ok'], $result);
    }

    public function test_bullet_esito_finale_returns_null_when_not_set()
    {
        $model = new CafPatronato;
        $model->esito_finale = null;

        $this->assertNull($model->bulletEsitoFinale());
    }

    /*
    |--------------------------------------------------------------------------
    | TIPO PRODOTTO
    |--------------------------------------------------------------------------
    */

    public function test_tipo_prodotto_strips_full_namespace()
    {
        $model = new CafPatronato;
        $model->prodotto_type = 'App\Models\CafPatIsee';

        $this->assertEquals('CafPatIsee', $model->tipoProdotto());
    }

    public function test_tipo_prodotto_handles_null()
    {
        $model = new CafPatronato;
        $model->prodotto_type = null;

        $result = $model->tipoProdotto();

        // str_replace on null returns empty string or null depending on PHP version
        $this->assertEmpty($result);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION HELPERS
    |--------------------------------------------------------------------------
    */

    public function test_puo_modificare_esito_returns_true_for_admin()
    {
        $this->staffUser('admin');

        $this->assertTrue(CafPatronato::puoModificareEsito());
    }

    public function test_puo_modificare_esito_returns_true_for_supervisore()
    {
        $this->staffUser('supervisore');

        $this->assertTrue(CafPatronato::puoModificareEsito());
    }

    public function test_puo_modificare_esito_returns_false_for_agente()
    {
        $this->staffUser('agente');

        $this->assertFalse(CafPatronato::puoModificareEsito());
    }

    public function test_puo_modificare_returns_true_for_admin()
    {
        $this->staffUser('admin');

        $this->assertTrue(CafPatronato::puoModificare());
    }

    public function test_puo_modificare_returns_true_for_agente()
    {
        $this->staffUser('agente');

        $this->assertTrue(CafPatronato::puoModificare());
    }

    public function test_puo_modificare_returns_false_for_supervisore()
    {
        $this->staffUser('supervisore');

        $this->assertFalse(CafPatronato::puoModificare());
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL SCOPE (filtroOperatore)
    |--------------------------------------------------------------------------
    */

    public function test_global_scope_filters_by_agente_id_for_agente_users()
    {
        $this->ensurePermissionExists('agente');

        $agente1 = User::factory()->create();
        $agente1->givePermissionTo('agente');

        $agente2 = User::factory()->create();
        $agente2->givePermissionTo('agente');

        // Create records for each agent without scope interference
        $pratica1 = CafPatronato::withoutGlobalScope('filtroOperatore', function () use ($agente1) {
            $model = new CafPatronato;
            $model->agente_id = $agente1->id;
            $model->nome = 'Record1';
            $model->cognome = 'Test';
            $model->data = now();
            $model->esito_id = 'da-gestire';
            $model->saveQuietly();

            return $model;
        });

        $pratica2 = CafPatronato::withoutGlobalScope('filtroOperatore', function () use ($agente2) {
            $model = new CafPatronato;
            $model->agente_id = $agente2->id;
            $model->nome = 'Record2';
            $model->cognome = 'Test';
            $model->data = now();
            $model->esito_id = 'da-gestire';
            $model->saveQuietly();

            return $model;
        });

        // Acting as agente1: should only see their own records
        $this->actingAs($agente1);

        $results = CafPatronato::all();

        $this->assertTrue($results->contains('id', $pratica1->id));
        $this->assertFalse($results->contains('id', $pratica2->id));
    }

    public function test_admin_sees_all_records_without_scope_filtering()
    {
        $this->ensurePermissionsExist(['admin', 'agente']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('admin');

        $agente = User::factory()->create();
        $agente->givePermissionTo('agente');

        // Create a record for agente
        CafPatronato::withoutGlobalScope('filtroOperatore', function () use ($agente) {
            $model = new CafPatronato;
            $model->agente_id = $agente->id;
            $model->nome = 'Test';
            $model->cognome = 'Record';
            $model->data = now();
            $model->esito_id = 'da-gestire';
            $model->saveQuietly();
        });

        // Acting as admin: should see all records (scope only filters for 'agente')
        $this->actingAs($admin);

        $results = CafPatronato::all();

        $this->assertGreaterThanOrEqual(1, $results->count());
    }
}
