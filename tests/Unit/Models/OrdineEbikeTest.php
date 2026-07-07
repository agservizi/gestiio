<?php

namespace Tests\Unit\Models;

use App\Enums\OrdineEbikeStatoEnum;
use App\Models\OrdineEbike;
use App\Models\ProdottoEbike;
use App\Models\RigaOrdineEbike;
use App\Models\User;
use Tests\TestCase;

class OrdineEbikeTest extends TestCase
{
    public function test_ricalcola_totale_sums_righe_subtotali()
    {
        $agente = User::factory()->create();
        $prodotto = ProdottoEbike::create([
            'nome' => 'Ebike City',
            'sku' => 'EB-CITY-1',
            'prezzo' => 500,
            'giacenza' => 10,
            'attivo' => true,
        ]);

        $ordine = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO,
        ]);

        RigaOrdineEbike::create([
            'ordine_id' => $ordine->id,
            'prodotto_id' => $prodotto->id,
            'nome_prodotto' => $prodotto->nome,
            'quantita' => 3,
            'prezzo_unitario' => 500,
        ]);

        $ordine->ricalcolaTotale();

        $this->assertSame('1500.00', (string) $ordine->totale);
    }

    public function test_scadenza_superata_is_true_only_when_confirmed_and_overdue()
    {
        $agente = User::factory()->create();

        $ordine = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO,
            'scadenza_spedizione' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($ordine->scadenzaSuperata());

        $ordine->stato = OrdineEbikeStatoEnum::SPEDITO;
        $this->assertFalse($ordine->scadenzaSuperata());
    }

    public function test_agente_global_scope_hides_other_agenti_orders()
    {
        $this->ensurePermissionExists('agente');
        $agenteUno = User::factory()->create();
        $agenteUno->givePermissionTo('agente');
        $agenteDue = User::factory()->create();
        $agenteDue->givePermissionTo('agente');

        OrdineEbike::create(['agente_id' => $agenteUno->id, 'stato' => OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO]);
        OrdineEbike::create(['agente_id' => $agenteDue->id, 'stato' => OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO]);

        $this->actingAs($agenteUno);

        $this->assertCount(1, OrdineEbike::all());
    }

    public function test_enum_labels_are_defined_for_every_case()
    {
        foreach (OrdineEbikeStatoEnum::cases() as $case) {
            $this->assertNotEmpty($case->testo());
            $this->assertNotEmpty($case->badge());
        }
    }
}
