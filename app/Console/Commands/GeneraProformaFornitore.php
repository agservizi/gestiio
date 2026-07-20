<?php

namespace App\Console\Commands;

use App\Http\Services\Billing\FornitoreSettlementService;
use Illuminate\Console\Command;

class GeneraProformaFornitore extends Command
{
    protected $signature = 'billing:genera-proforma-fornitore
                            {--anno= : Anno (default: mese precedente)}
                            {--mese= : Mese 1-12 (default: mese precedente)}
                            {--source=all : caf|send|all}
                            {--force : Rigenera se già presente}';

    protected $description = 'Genera proforma mensili fornitore (CAF/Patronato e/o SEND) via InvoiceShelf';

    public function handle(FornitoreSettlementService $settlement): int
    {
        $ref = now()->subMonth();
        $anno = (int) ($this->option('anno') ?: $ref->year);
        $mese = (int) ($this->option('mese') ?: $ref->month);
        $source = strtolower((string) $this->option('source'));
        $force = (bool) $this->option('force');

        $ok = 0;
        $fail = 0;

        $targets = match ($source) {
            'caf' => ['caf'],
            'send' => ['send'],
            default => ['caf', 'send'],
        };

        foreach ($targets as $target) {
            try {
                $doc = $target === 'caf'
                    ? $settlement->generaProformaCaf($anno, $mese, $force)
                    : $settlement->generaProformaSend($anno, $mese, $force);
                $this->info(strtoupper($target).": generata #{$doc->id} totale={$doc->totale}");
                $ok++;
            } catch (\Throwable $e) {
                $this->warn(strtoupper($target).': '.$e->getMessage());
                $fail++;
            }
        }

        return $fail > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
