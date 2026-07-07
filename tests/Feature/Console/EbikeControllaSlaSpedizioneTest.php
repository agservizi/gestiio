<?php

namespace Tests\Feature\Console;

use App\Enums\OrdineEbikeStatoEnum;
use App\Models\OrdineEbike;
use App\Models\User;
use App\Notifications\NotificaEbikeSlaSuperato;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EbikeControllaSlaSpedizioneTest extends TestCase
{
    public function test_segnala_ordini_oltre_sla_e_non_li_rinotifica_due_volte()
    {
        Notification::fake();
        $agente = User::factory()->create();

        $scaduto = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO,
            'scadenza_spedizione' => now()->subDay()->toDateString(),
        ]);

        $nonScaduto = OrdineEbike::create([
            'agente_id' => $agente->id,
            'stato' => OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO,
            'scadenza_spedizione' => now()->addDays(2)->toDateString(),
        ]);

        $this->artisan('ebike:controlla-sla-spedizione')->assertExitCode(0);

        $this->assertTrue($scaduto->fresh()->sla_alert_inviato);
        $this->assertFalse($nonScaduto->fresh()->sla_alert_inviato);

        $userAdmin = User::find(2);
        if ($userAdmin) {
            Notification::assertSentTo($userAdmin, NotificaEbikeSlaSuperato::class);
        }

        // Rilanciando il comando non deve rinotificare lo stesso ordine
        Notification::fake();
        $this->artisan('ebike:controlla-sla-spedizione')->assertExitCode(0);
        Notification::assertNothingSent();
    }

    public function test_nessun_ordine_da_segnalare_termina_correttamente()
    {
        $this->artisan('ebike:controlla-sla-spedizione')->assertExitCode(0);
        $this->assertTrue(true);
    }
}
