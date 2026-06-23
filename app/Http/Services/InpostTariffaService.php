<?php

namespace App\Http\Services;

use App\Models\ListinoInpost;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use function App\applicaPercentuale;

class InpostTariffaService
{
    protected ?float $prezzo = null;

    protected ?string $testoTariffa = null;

    protected string|bool $error = false;

    public function __construct(protected string $packageType, protected string $deliveryType) {}

    public function calcola(): void
    {
        $tariffa = ListinoInpost::trovaTariffa($this->packageType);
        if (! $tariffa) {
            $this->error = 'Tariffa InPost non configurata per il package selezionato';

            return;
        }

        $prezzo = $tariffa->calcolaPrezzo($this->deliveryType);
        if ($prezzo === null) {
            $this->error = 'Prezzo InPost non configurato per la modalita di consegna selezionata';

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->hasPermissionTo('agente')) {
            $agente = $user->agente()->select(['id', 'variazione_prezzi_spedizioni'])->first();
            if ($agente?->variazione_prezzi_spedizioni) {
                $prezzo = applicaPercentuale($prezzo, $agente->variazione_prezzi_spedizioni);
            }
        }

        $this->prezzo = (float) $prezzo;
        $this->testoTariffa = $tariffa->testoTariffa($this->deliveryType);
    }

    public function getPrezzo(): ?float
    {
        return $this->prezzo;
    }

    public function getTestotariffa(): ?string
    {
        return $this->testoTariffa;
    }

    public function isError(): string|bool
    {
        return $this->error;
    }
}
