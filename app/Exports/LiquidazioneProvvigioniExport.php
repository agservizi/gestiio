<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LiquidazioneProvvigioniExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected Collection $righe,
        protected int $anno,
        protected int $mese
    ) {
    }

    public function collection()
    {
        return $this->righe->map(fn ($r) => [
            $r->nominativo,
            $r->email,
            number_format($r->importo_telefonia, 2, ',', ''),
            number_format($r->importo_energia, 2, ',', ''),
            number_format($r->importo_gare ?? 0, 2, ',', ''),
            number_format($r->importo_totale, 2, ',', ''),
            $r->contratti_da_liquidare,
            $r->contratti_pagati,
            $r->proforma_numero,
            $r->proforma_label,
        ]);
    }

    public function headings(): array
    {
        return [
            'Affiliato',
            'Email',
            'Telefonia €',
            'Energia €',
            'Gare €',
            'Totale €',
            'Contratti da liquidare',
            'Contratti pagati',
            'Proforma N.',
            'Stato proforma',
        ];
    }

    public function title(): string
    {
        return sprintf('%02d-%04d', $this->mese, $this->anno);
    }
}
