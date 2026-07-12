<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $html = view('Backend.LuggageDeposit.tabella', [
        'records' => App\Models\LuggageDeposit::paginate(2),
        'controller' => App\Http\Controllers\Backend\LuggageDepositController::class,
    ])->render();
    echo 'render_ok:' . strlen($html) . "\n";
} catch (Throwable $e) {
    echo 'render_fail:' . $e->getMessage() . "\n";
    exit(1);
}
