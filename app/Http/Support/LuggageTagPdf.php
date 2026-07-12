<?php

namespace App\Http\Support;

use App\Models\LuggageDeposit;
use Barryvdh\DomPDF\Facade\Pdf;

class LuggageTagPdf
{
    /** Larghezza carta termica standard (mm). */
    public const WIDTH_MM = 80;

    /** Altezza tag lungo stile aeroporto per rotolo termico (mm). */
    public const HEIGHT_MM = 330;

    public static function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    public static function make(LuggageDeposit $deposit, array $tags): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('Backend.LuggageDeposit.pdf.tags', [
            'deposit' => $deposit,
            'tags' => $tags,
            'paperWidthMm' => self::WIDTH_MM,
            'paperHeightMm' => self::HEIGHT_MM,
            'printedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper([
            0,
            0,
            self::mmToPt(self::WIDTH_MM),
            self::mmToPt(self::HEIGHT_MM),
        ]);

        return $pdf;
    }
}
