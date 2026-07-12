<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$deposit = App\Models\LuggageDeposit::where('code', 'LB-UMKGCK')->first()
    ?? App\Models\LuggageDeposit::latest()->first();

if (! $deposit) {
    echo "no_deposit\n";
    exit(1);
}

$tags = app(App\Http\Services\LuggageDepositService::class)->resolveBagTags($deposit);
$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('Backend.LuggageDeposit.pdf.agreement', [
    'deposit' => $deposit,
    'tags' => $tags,
]);
$pdf->setPaper('a4', 'portrait');
$path = '/var/www/html/storage/app/test-agreement.pdf';
$pdf->save($path);

$text = shell_exec('pdftotext ' . escapeshellarg($path) . ' - 2>/dev/null | grep -c "PAGE" || true');
$pages = 0;
if (preg_match_all('/\/Type[\s]*\/Page[^s]/', file_get_contents($path), $m)) {
    $pages = count($m[0]);
}
// fallback: count page objects
if ($pages === 0 && function_exists('exec')) {
    exec('pdfinfo ' . escapeshellarg($path) . ' 2>/dev/null', $out);
    foreach ($out as $line) {
        if (str_starts_with($line, 'Pages:')) {
            $pages = (int) trim(substr($line, 6));
        }
    }
}
echo 'code=' . $deposit->code . ' pages=' . $pages . "\n";
