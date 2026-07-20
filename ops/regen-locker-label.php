<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = resource_path('views/Backend/LockerPoint/pdf/label.blade.php');
echo 'blade_path='.$path."\n";
echo 'exists='.(file_exists($path) ? 'yes' : 'no')."\n";
echo 'has_v2='.(str_contains(file_get_contents($path), 'adesiva A6 v2') ? 'yes' : 'no')."\n";
echo 'has_formato='.(str_contains(file_get_contents($path), 'Formato A6') ? 'yes' : 'no')."\n";
echo 'pdf_class_w='.\App\Http\Support\LockerTagPdf::WIDTH_MM."\n";

use App\Http\Support\LockerTagPdf;
use App\Models\LockerPackage;

$package = LockerPackage::where('code', $argv[1] ?? 'LP-5VB8PA')->firstOrFail();
$out = '/tmp/etichetta-'.$package->code.'-a6.pdf';
$bin = LockerTagPdf::make($package)->output();
file_put_contents($out, $bin);
echo 'pdf_out='.$out."\n";
echo 'pdf_bytes='.strlen($bin)."\n";

$html = view('Backend.LockerPoint.pdf.label', [
    'package' => $package,
    'paperWidthMm' => LockerTagPdf::WIDTH_MM,
    'paperHeightMm' => LockerTagPdf::HEIGHT_MM,
    'printedAt' => now()->format('d/m/Y H:i'),
])->render();
file_put_contents('/tmp/locker-label-debug.html', $html);
echo 'html_has_v2='.(str_contains($html, 'adesiva A6 v2') ? 'yes' : 'no')."\n";
echo 'html_len='.strlen($html)."\n";
