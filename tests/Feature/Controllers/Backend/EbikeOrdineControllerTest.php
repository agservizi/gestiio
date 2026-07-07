<?php

namespace Tests\Feature\Controllers\Backend;

use App\Enums\OrdineEbikeStatoEnum;
use App\Models\OrdineEbike;
use App\Models\ProdottoEbike;
use App\Models\User;
use App\Notifications\NotificaEbikePagamentoConfermato;
use App\Notifications\NotificaEbikeSpedito;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EbikeOrdineControllerTest extends TestCase
{
    protected function creaProdotto(int $giacenza = 5): ProdottoEbike
    {
        return ProdottoEbike::create([
            'nome' => 'Ebike Test',
            'sku' => 'EB-TEST-'.uniqid(),
            'prezzo' => 1000,
            'giacenza' => $giacenza,
            'attivo' => true,
        ]);
    }

    protected function creaAgenteConPermesso(): User
    {
        $this->ensurePermissionsExist(['agente', 'ebike-b2b']);
        $user = User::factory()->create();
        $user->givePermissionTo(['agente', 'ebike-b2b']);

        return $user;
    }

    public function test_agente_senza_permesso_ebike_b2b_non_puo_creare_ordini()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/ebike/ordini/create');

        $response->assertStatus(403);
    }

    public function test_agente_con_permesso_puo_creare_un_ordine_e_decrementa_la_giacenza()
    {
        Notification::fake();
        $agente = $this->creaAgenteConPermesso();
        $this->actingAs($agente);
        $prodotto = $this->creaProdotto(5);

        $response = $this->post('/backend/ebike/ordini', [
            'quantita' => [$prodotto->id => 2],
            'note' => 'Consegna in sede',
        ]);

        $ordine = OrdineEbike::first();

        $response->assertRedirect('/backend/ebike/ordini/'.$ordine->id);
        $this->assertNotNull($ordine);
        $this->assertSame(OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO, $ordine->stato);
        $this->assertSame('2000.00', (string) $ordine->totale);
        $this->assertSame(3, $prodotto->fresh()->giacenza);
    }

    public function test_non_puo_ordinare_piu_della_giacenza_disponibile()
    {
        $agente = $this->creaAgenteConPermesso();
        $this->actingAs($agente);
        $prodotto = $this->creaProdotto(1);

        $response = $this->post('/backend/ebike/ordini', [
            'quantita' => [$prodotto->id => 5],
        ]);

        $response->assertSessionHasErrors('quantita');
        $this->assertSame(0, OrdineEbike::count());
        $this->assertSame(1, $prodotto->fresh()->giacenza);
    }

    public function test_agente_puo_caricare_ricevuta_bonifico()
    {
        Storage::fake('public');
        Notification::fake();
        $agente = $this->creaAgenteConPermesso();
        $this->actingAs($agente);
        $prodotto = $this->creaProdotto(5);

        $this->post('/backend/ebike/ordini', ['quantita' => [$prodotto->id => 1]]);
        $ordine = OrdineEbike::first();

        $response = $this->post('/backend/ebike/ordini/'.$ordine->id.'/carica-pagamento', [
            'cro_bonifico' => 'CRO123456',
            'data_bonifico_dichiarata' => now()->toDateString(),
            'ricevuta_bonifico' => UploadedFile::fake()->create('ricevuta.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect('/backend/ebike/ordini/'.$ordine->id);
        $ordine->refresh();
        $this->assertSame(OrdineEbikeStatoEnum::PAGAMENTO_DA_VERIFICARE, $ordine->stato);
        $this->assertSame('CRO123456', $ordine->cro_bonifico);
        Storage::disk('public')->assertExists($ordine->ricevuta_bonifico);
    }

    public function test_admin_conferma_pagamento_e_imposta_scadenza_spedizione()
    {
        Notification::fake();
        $agente = $this->creaAgenteConPermesso();
        $ordine = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_DA_VERIFICARE,
        ]);

        $admin = $this->staffUser('admin');

        $response = $this->post('/backend/ebike/ordini/'.$ordine->id.'/conferma-pagamento');

        $response->assertRedirect('/backend/ebike/ordini/'.$ordine->id);
        $ordine->refresh();
        $this->assertSame(OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO, $ordine->stato);
        $this->assertSame($admin->id, $ordine->pagamento_confermato_da);
        $this->assertSame(
            now()->addDays(OrdineEbike::GIORNI_SLA_SPEDIZIONE)->toDateString(),
            $ordine->scadenza_spedizione->toDateString()
        );

        Notification::assertSentTo($agente, NotificaEbikePagamentoConfermato::class);
    }

    public function test_agente_non_puo_confermare_il_pagamento()
    {
        $agente = $this->creaAgenteConPermesso();
        $ordine = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_DA_VERIFICARE,
        ]);

        $this->actingAs($agente);

        $response = $this->post('/backend/ebike/ordini/'.$ordine->id.'/conferma-pagamento');

        $response->assertStatus(403);
    }

    public function test_admin_imposta_tracking_e_notifica_agente()
    {
        Notification::fake();
        $agente = $this->creaAgenteConPermesso();
        $ordine = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO,
            'scadenza_spedizione' => now()->addDays(10)->toDateString(),
        ]);

        $this->staffUser('admin');

        $response = $this->post('/backend/ebike/ordini/'.$ordine->id.'/imposta-tracking', [
            'corriere' => 'BRT',
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertRedirect('/backend/ebike/ordini/'.$ordine->id);
        $ordine->refresh();
        $this->assertSame(OrdineEbikeStatoEnum::SPEDITO, $ordine->stato);
        $this->assertSame('BRT', $ordine->corriere);
        Notification::assertSentTo($agente, NotificaEbikeSpedito::class);
    }

    public function test_agente_vede_solo_i_propri_ordini_admin_li_vede_tutti()
    {
        $agenteUno = $this->creaAgenteConPermesso();
        $agenteDue = $this->creaAgenteConPermesso();

        OrdineEbike::create(['agente_id' => $agenteUno->id, 'stato' => OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO]);
        OrdineEbike::create(['agente_id' => $agenteDue->id, 'stato' => OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO]);

        $this->actingAs($agenteUno);
        $response = $this->get('/backend/ebike/ordini');
        $response->assertStatus(200);
        $response->assertViewHas('records', function ($records) {
            return $records->total() === 1;
        });

        $this->staffUser('admin');
        $response = $this->get('/backend/ebike/ordini');
        $response->assertViewHas('records', function ($records) {
            return $records->total() === 2;
        });
    }

    public function test_annulla_ordine_ripristina_la_giacenza()
    {
        $agente = $this->creaAgenteConPermesso();
        $this->actingAs($agente);
        $prodotto = $this->creaProdotto(5);

        $this->post('/backend/ebike/ordini', ['quantita' => [$prodotto->id => 2]]);
        $ordine = OrdineEbike::first();
        $this->assertSame(3, $prodotto->fresh()->giacenza);

        $response = $this->post('/backend/ebike/ordini/'.$ordine->id.'/annulla', [
            'motivo' => 'Cambio idea',
        ]);

        $response->assertRedirect('/backend/ebike/ordini');
        $this->assertSame(OrdineEbikeStatoEnum::ANNULLATO, $ordine->fresh()->stato);
        $this->assertSame(5, $prodotto->fresh()->giacenza);
    }
}
