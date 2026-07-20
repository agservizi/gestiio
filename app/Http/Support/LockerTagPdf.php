<?php

namespace App\Http\Support;

use App\Models\LockerPackage;
use Barryvdh\DomPDF\Facade\Pdf;

class LockerTagPdf
{
    /** ISO A6 — etichetta adesiva stampabile */
    public const WIDTH_MM = 105;

    public const HEIGHT_MM = 148;

    public static function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    public static function make(LockerPackage $package): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('Backend.LockerPoint.pdf.label', [
            'package' => $package,
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
