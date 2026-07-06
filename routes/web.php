<?php

use App\Http\Controllers\Backend\AreaPersonaleController;
use App\Http\Controllers\Backend\CartellaFilesController;
use App\Http\Controllers\Backend\Select2;
use App\Http\Controllers\Backend\VisuraCameraleController;
use App\Http\Controllers\Frontend\AreaUtenteController;
use App\Http\Controllers\Frontend\ContrattoController;
use App\Http\Controllers\Frontend\ContrattoEnergiaDocumentiController;
use App\Http\Controllers\Frontend\MarketingController;
use App\Http\Controllers\Frontend\TicketController;
use App\Http\Controllers\LogOut;
use App\Http\Controllers\PagineController;
use App\Http\Controllers\RegistratiController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Webhook\N8nAiWebhookController;
use App\Http\Controllers\Webhook\InpostWebhookController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/collabora-con-ag-servizi', [MarketingController::class, 'collabora'])->name('marketing.collabora');

Route::get('select2front', [Select2::class, 'response']);

Route::get('/logout', [LogOut::class, 'logOut']);
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle']);
Route::post('/webhook/inpost/tracking', [InpostWebhookController::class, 'handle'])->middleware('throttle:120,1');
Route::post('/webhook/n8n/ai', [N8nAiWebhookController::class, 'handle'])->middleware('throttle:60,1');
Route::post('/send-otp-email/{id}', [LogOut::class, 'sendOtpEmail'])->middleware('throttle:3,5');

Route::post('/register', [RegistratiController::class, 'post']);
Route::get('/registrato', [RegistratiController::class, 'show']);
Route::post('/verifica-partita-iva', [RegistratiController::class, 'verificaPIvaEu']);

Route::get('/test', TestController::class);

Route::view('/policies', 'auth.policies');
Route::match(['get', 'post'], '/documenti/condivisi/{token}', [CartellaFilesController::class, 'sharedDownload']);

Route::get('/pagina/{pagina}', [PagineController::class, 'show']);

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/contratto-energia/documenti/template', [ContrattoEnergiaDocumentiController::class, 'downloadTemplate'])
        ->name('frontend.contratto-energia.magic.template');
    Route::get('/contratto-energia/documenti/{token}', [ContrattoEnergiaDocumentiController::class, 'show'])
        ->name('frontend.contratto-energia.magic.show');
    Route::post('/contratto-energia/documenti/{token}', [ContrattoEnergiaDocumentiController::class, 'store'])
        ->name('frontend.contratto-energia.magic.store');
});

Route::group(['middleware' => ['auth']], function () {

    Route::get('area-personale', [AreaUtenteController::class, 'show']);
    Route::get('area-personale/contratti', [ContrattoController::class, 'index']);
    Route::get('ticket/{messaggioId}/allegato/{allegatoId}', [TicketController::class, 'downloadAllegato']);

    Route::resource('ticket', TicketController::class)->only(['index', 'create', 'show', 'store', 'update', 'edit']);
    Route::post('/allegato-ticket', [TicketController::class, 'uploadAllegato'])->middleware('throttle:30,1');
    Route::delete('/allegato-ticket', [TicketController::class, 'deleteAllegato'])->middleware('throttle:60,1');

    Route::get('select2', [App\Http\Controllers\Frontend\Select2::class, 'response']);

    // Dati utente
    Route::get('/area-utente/{tab?}', [AreaPersonaleController::class, 'index']);
    Route::get('/dati-utente', [AreaPersonaleController::class, 'show']);
    Route::get('/dati-utente/export', [AreaPersonaleController::class, 'exportDatiPersonali']);
    Route::patch('/dati-utente/{cosa}', [AreaPersonaleController::class, 'update']);

    Route::get('/metronic/{cosa}', [AreaPersonaleController::class, 'metronic']);

});

// Alias compatibilità: alcune chiamate legacy puntano senza prefisso /backend.
Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa']], function () {
    Route::get('/visura-cerca-azienda', [VisuraCameraleController::class, 'showCercaAzienda']);
    Route::post('/visura-cerca-azienda', [VisuraCameraleController::class, 'postCercaAzienda']);
});

// Stop impersonation: restore original user stored in session('impersona')
Route::get('/stop-impersona', function () {
    if (! session()->has('impersona')) {
        return redirect('/');
    }
    $orig = session('impersona');
    session()->forget('impersona');
    Auth::loginUsingId($orig, false);

    return redirect('/backend');
})->middleware('auth');

if (env('APP_ENV') == 'local') {
    Route::get('login-id/{id}', [LogOut::class, 'loginId']);
}
