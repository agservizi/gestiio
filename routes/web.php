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
//Route::get('/', [\App\Http\Controllers\Frontend\VariePagineController::class, 'showHome']);
Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'show']);

Route::get('select2front', [\App\Http\Controllers\Backend\Select2::class, 'response']);


Route::get('/logout', [\App\Http\Controllers\LogOut::class, 'logOut']);
Route::post('/send-otp-email/{id}', [\App\Http\Controllers\LogOut::class, 'sendOtpEmail']);

Route::post('/register', [\App\Http\Controllers\RegistratiController::class, 'post']);
Route::get('/registrato', [\App\Http\Controllers\RegistratiController::class, 'show']);
Route::post('/verifica-partita-iva', [\App\Http\Controllers\RegistratiController::class, 'verificaPIvaEu']);

Route::get('/test', \App\Http\Controllers\TestController::class);

Route::view('/policies', 'auth.policies');

Route::get('/pagina/{pagina}', [\App\Http\Controllers\PagineController::class, 'show']);

Route::group(['middleware' => ['auth']], function () {

    Route::get('area-personale', [\App\Http\Controllers\Frontend\AreaUtenteController::class, 'show']);
    Route::get('ticket/{messaggioId}/allegato/{allegatoId}', [\App\Http\Controllers\Frontend\TicketController::class, 'downloadAllegato']);

    Route::resource('ticket', \App\Http\Controllers\Frontend\TicketController::class)->only(['index', 'create', 'show', 'store', 'update', 'edit']);
    Route::post('/allegato-ticket', [\App\Http\Controllers\Frontend\TicketController::class, 'uploadAllegato']);
    Route::delete('/allegato-ticket', [\App\Http\Controllers\Frontend\TicketController::class, 'deleteAllegato']);

    Route::get('select2', [\App\Http\Controllers\Frontend\Select2::class, 'response']);

    //Dati utente
    Route::get('/area-utente/{tab?}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'index']);
    Route::get('/dati-utente', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'show']);
    Route::patch('/dati-utente/{cosa}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'update']);


    Route::get('/metronic/{cosa}', [\App\Http\Controllers\Backend\AreaPersonaleController::class, 'metronic']);

});

if (env('APP_ENV') == 'local') {
    Route::get('login-id/{id}', [\App\Http\Controllers\LogOut::class, 'loginId']);
}





