<?php

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
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore'])) {
            return redirect('/backend');
        }
        return redirect('/area-personale');
    }

    return redirect('/login');
});

Route::get('select2front', [\App\Http\Controllers\Backend\Select2::class, 'response']);


Route::get('/logout', [\App\Http\Controllers\LogOut::class, 'logOut']);
Route::post('/send-otp-email/{id}', [\App\Http\Controllers\LogOut::class, 'sendOtpEmail']);

Route::post('/register', [\App\Http\Controllers\RegistratiController::class, 'post']);
Route::get('/registrato', [\App\Http\Controllers\RegistratiController::class, 'show']);
Route::post('/verifica-partita-iva', [\App\Http\Controllers\RegistratiController::class, 'verificaPIvaEu']);

Route::get('/test', \App\Http\Controllers\TestController::class);

Route::view('/policies', 'auth.policies');
Route::match(['get', 'post'], '/documenti/condivisi/{token}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'sharedDownload']);

Route::get('/pagina/{pagina}', [\App\Http\Controllers\PagineController::class, 'show']);

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/contratto-energia/documenti/{token}', [\App\Http\Controllers\Frontend\ContrattoEnergiaDocumentiController::class, 'show'])
        ->name('frontend.contratto-energia.magic.show');
    Route::post('/contratto-energia/documenti/{token}', [\App\Http\Controllers\Frontend\ContrattoEnergiaDocumentiController::class, 'store'])
        ->name('frontend.contratto-energia.magic.store');
});

Route::group(['middleware' => ['auth']], function () {

    Route::get('area-personale', [\App\Http\Controllers\Frontend\AreaUtenteController::class, 'show']);
    Route::get('area-personale/contratti', [\App\Http\Controllers\Frontend\ContrattoController::class, 'index']);
    Route::get('ticket/{messaggioId}/allegato/{allegatoId}', [\App\Http\Controllers\Frontend\TicketController::class, 'downloadAllegato']);

    Route::resource('ticket', \App\Http\Controllers\Frontend\TicketController::class)->only(['index', 'create', 'show', 'store', 'update', 'edit']);
    Route::post('/allegato-ticket', [\App\Http\Controllers\Frontend\TicketController::class, 'uploadAllegato'])->middleware('throttle:30,1');
    Route::delete('/allegato-ticket', [\App\Http\Controllers\Frontend\TicketController::class, 'deleteAllegato'])->middleware('throttle:60,1');

    Route::get('select2', [\App\Http\Controllers\Frontend\Select2::class, 'response']);

    //Dati utente
    Route::get('/area-utente/{tab?}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'index']);
    Route::get('/dati-utente', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'show']);
    Route::get('/dati-utente/export', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'exportDatiPersonali']);
    Route::patch('/dati-utente/{cosa}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'update']);


    Route::get('/metronic/{cosa}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'metronic']);

});

// Alias compatibilità: alcune chiamate legacy puntano senza prefisso /backend.
Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa']], function () {
    Route::get('/visura-cerca-azienda', [\App\Http\Controllers\Backend\VisuraCameraleController::class, 'showCercaAzienda']);
    Route::post('/visura-cerca-azienda', [\App\Http\Controllers\Backend\VisuraCameraleController::class, 'postCercaAzienda']);
});

// Stop impersonation: restore original user stored in session('impersona')
Route::get('/stop-impersona', function () {
    if (!session()->has('impersona')) {
        return redirect('/');
    }
    $orig = session('impersona');
    session()->forget('impersona');
    \Illuminate\Support\Facades\Auth::loginUsingId($orig, false);
    return redirect('/backend');
})->middleware('auth');

if (env('APP_ENV') == 'local') {
    Route::get('login-id/{id}', [\App\Http\Controllers\LogOut::class, 'loginId']);
}
