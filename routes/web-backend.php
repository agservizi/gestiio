<?php

use App\Models\MessaggioFormAssistenza;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role_or_permission:admin|agente|supervisore|operatore'])->group(function () {
    Route::get('2fa', [\App\Http\Controllers\Backend\Autenticazione2faController::class, 'show']);
});

Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa']], function () {
    Route::get('/', [\App\Http\Controllers\Backend\DashboardController::class, 'show']);

    //Contratto
    Route::post('/allegato-contratto', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'deleteAllegato']);
    Route::get('contratto/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'downloadAllegato']);
    Route::resource('contratto', \App\Http\Controllers\Backend\ContrattoTelefoniaController::class);
    Route::post('/contratto/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'azioni']);
    Route::get('pda-contratto/{id}', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'pda']);

    //ContrattoEnergia
    Route::post('/allegato-contratto-energia', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto-energia', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'deleteAllegato']);
    Route::get('contratto-energia/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'downloadAllegato']);
    Route::get('/contratto-energia/create/{servizio?}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'create']);

    Route::resource('contratto-energia', \App\Http\Controllers\Backend\ContrattoEnergiaController::class)->except(['create']);
    //Route::post('/contratto-energia/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'azioni']);


    //Servizi finanziari
    Route::get('/servizio-finanziario/create/{servizio?}', [\App\Http\Controllers\Backend\ServizioFinanziarioController::class, 'create']);
    Route::resource('/servizio-finanziario', \App\Http\Controllers\Backend\ServizioFinanziarioController::class)->except(['create']);

    //Comparasemplice
    Route::resource('/comparasemplice', \App\Http\Controllers\Backend\ComparasempliceController::class);

    //Attivazioni
    Route::post('/allegato-attivazione-sim', [\App\Http\Controllers\Backend\AttivazioneSimController::class, 'uploadAllegato']);
    Route::delete('/allegato-attivazione-sim', [\App\Http\Controllers\Backend\AttivazioneSimController::class, 'deleteAllegato']);
    Route::get('attivazione-sim/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\AttivazioneSimController::class, 'downloadAllegato']);
    Route::get('/attivazione-sim/create/{servizio?}', [\App\Http\Controllers\Backend\AttivazioneSimController::class, 'create']);
    Route::resource('/attivazione-sim', \App\Http\Controllers\Backend\AttivazioneSimController::class)->except(['create']);

    //Sostituzioni sim
    Route::resource('sostituzioni-sim', \App\Http\Controllers\Backend\SostituzioneSimController::class)->except(['show', 'update', 'edit', 'create']);


    //Caf patronato
    Route::get('/caf-patronato/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'downloadAllegato']);
    Route::get('/caf-patronato-download/{contrattoId}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'downloadAllegatoCliente']);

    Route::get('/caf-patronato/create/{servizio?}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'create']);
    Route::resource('/caf-patronato', \App\Http\Controllers\Backend\CafPatronatoController::class)->except(['create']);
    Route::post('/allegato-caf', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'uploadAllegato']);
    Route::delete('/allegato-caf', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'deleteAllegato']);

    //Visure
    Route::get('/visura/create/{servizio?}', [\App\Http\Controllers\Backend\VisuraController::class, 'create']);
    Route::resource('/visura', \App\Http\Controllers\Backend\VisuraController::class)->except(['create']);
    Route::post('/allegato-visura', [\App\Http\Controllers\Backend\VisuraController::class, 'uploadAllegato']);
    Route::delete('/allegato-visura', [\App\Http\Controllers\Backend\VisuraController::class, 'deleteAllegato']);

    //Segnalazioni
    Route::resource('segnalazione', \App\Http\Controllers\Backend\SegnalazioneController::class);

    //Spedizioni
    Route::get('spedizione-brt/bordero/{id?}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'bordero']);
    Route::get('/spedizione-brt/create/{zona?}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'create']);
    Route::resource('spedizione-brt', \App\Http\Controllers\Backend\SpedizioneBrtController::class)->except(['create']);
    Route::delete('spedizione-brt-annulla/{id}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'annulla']);
    Route::get('spedizione-brt/{id}/etichetta/{index}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'etichetta']);
    Route::get('spedizione-brt/{id}/pdf/{tipopdf}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'pdf']);


//Allegati tutti servizi
    Route::get('/allegato-servizio/{contrattoId}', [\App\Http\Controllers\Backend\AllegatoServizioController::class, 'downloadAllegatoCliente']);
    Route::get('/allegato-servizio/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\AllegatoServizioController::class, 'downloadAllegato']);

    Route::post('/allegato-servizio', [\App\Http\Controllers\Backend\AllegatoServizioController::class, 'uploadAllegato']);
    Route::delete('/allegato-servizio', [\App\Http\Controllers\Backend\AllegatoServizioController::class, 'deleteAllegato']);


    //Documenti
    Route::get('documenti/{id?}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'index']);

    Route::resource('documenti/{cartellaId}/cartella', \App\Http\Controllers\Backend\CartellaFilesController::class)->except(['index', 'show']);

    Route::get('documento-upload/{cartellaId}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'show']);
    Route::post('documento-upload/{cartellaId}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'upload']);
    Route::delete('documento-cancella', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'cancellaFile']);
    Route::get('documento-download/{id}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'download']);

    Route::get('/modal/{modal}/{id?}', [\App\Http\Controllers\Backend\ModalController::class, 'show']);


    //Ajax
    Route::post('/ajax/{cosa}', [\App\Http\Controllers\Backend\AjaxController::class, 'post']);

    //Visura
    //Route::resource('/visura', \App\Http\Controllers\Backend\VisuraCameraleController::class)->only(['index', 'update', 'show']);
    Route::get('/visura-cerca-azienda', [\App\Http\Controllers\Backend\VisuraCameraleController::class, 'showCercaAzienda']);
    Route::post('/visura-cerca-azienda', [\App\Http\Controllers\Backend\VisuraCameraleController::class, 'postCercaAzienda']);
    Route::get('/visura-mostra-azienda', [\App\Http\Controllers\Backend\VisuraCameraleController::class, 'showAziende']);

    //Fatture proforma
    Route::get('fattura-proforma/{id}/pdf', [\App\Http\Controllers\Backend\FatturaProformaController::class, 'pdf']);
    Route::resource('fattura-proforma', \App\Http\Controllers\Backend\FatturaProformaController::class)->except(['edit', 'update']);


    //Portafoglio
    Route::resource('/portafoglio', \App\Http\Controllers\Backend\PortafoglioController::class);
    Route::post('/pagamento/{servizio}', [\App\Http\Controllers\Backend\PaymentController::class, 'pagamento']);
    Route::get('/pagamento/{servizio}/{result}', [\App\Http\Controllers\Backend\PaymentController::class, 'response']);
    Route::post('/pagamento', [\App\Http\Controllers\Backend\PaymentController::class, 'storePagamento']);
    Route::get('/pagamento-success', [\App\Http\Controllers\Backend\PaymentController::class, 'pagamentoSuccess']);


    //Tickets
    Route::resource('/ticket', \App\Http\Controllers\Backend\TicketsController::class, ['as' => 'ticket-admin']);

    Route::get('select2', [\App\Http\Controllers\Backend\Select2::class, 'response']);

    Route::post('/contratto-stato/{id}', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'aggiornaStato']);
    Route::post('/contratto-energia-stato/{id}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'aggiornaStato']);
    Route::post('/caf-patronato-stato/{id}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'aggiornaStato']);
    Route::post('/attivazione-sim-stato/{id}', [\App\Http\Controllers\Backend\AttivazioneSimController::class, 'aggiornaStato']);
    Route::post('/visura-stato/{id}', [\App\Http\Controllers\Backend\VisuraController::class, 'aggiornaStato']);
    Route::post('/segnalazione-stato/{id}', [\App\Http\Controllers\Backend\SegnalazioneController::class, 'aggiornaStato']);

    Route::get('profilo', [\App\Http\Controllers\Backend\ProfiloController::class, 'show']);
    Route::get('profilo-listino/{tipoContratto}', [\App\Http\Controllers\Backend\ProfiloController::class, 'showListino']);

    //Contatti Brt
    Route::resource('contatto-brt', \App\Http\Controllers\Backend\ContattoBrtController::class)->except(['show']);


});


Route::group(['middleware' => ['auth', 'role_or_permission:admin']], function () {


    Route::post('/servizio-finanziario-stato/{id}', [\App\Http\Controllers\Backend\ServizioFinanziarioController::class, 'aggiornaStato']);
    Route::post('/comparasemplice-stato/{id}', [\App\Http\Controllers\Backend\ComparasempliceController::class, 'aggiornaStato']);


    //Cliente
    Route::get('cliente/{id}/tab/{tab}', [\App\Http\Controllers\Backend\ClienteController::class, 'tab']);
    Route::resource('cliente', \App\Http\Controllers\Backend\ClienteController::class);
    Route::post('/cliente/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ClienteController::class, 'azioni']);


    Route::get('esporta/{cosa}', [\App\Http\Controllers\Backend\EsportaController::class, 'esporta']);

    //Impostazioni
    Route::get('/settings', [\App\Http\Controllers\Backend\SettingController::class, 'index'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\Backend\SettingController::class, 'store'])->name('settings.store');


    //Assistenza
    Route::resource('cliente-assistenza', \App\Http\Controllers\Backend\ClienteAssistenzaController::class);
    Route::resource('prodotto-assistenza', \App\Http\Controllers\Backend\ProdottoAssistenzaController::class)->except(['show']);
    Route::resource('richiesta-assistenza', \App\Http\Controllers\Backend\RichiestaAssistenzaController::class);
    Route::get('richiesta-assistenza/{id}/pdf', [\App\Http\Controllers\Backend\RichiestaAssistenzaController::class, 'pdf']);
    //Registri
    Route::get('registro/{cosa}', [\App\Http\Controllers\Backend\RegistriController::class, 'index']);

    //plafond
    Route::get('/carica-plafond', [\App\Http\Controllers\Backend\RicaricaPlafonController::class, 'show']);
    Route::post('/carica-plafond', [\App\Http\Controllers\Backend\RicaricaPlafonController::class, 'store']);

    //Agente
    Route::get('/agente-tab/{id}/tab/{tab}', [\App\Http\Controllers\Backend\AgenteController::class, 'tab']);
    Route::resource('/agente', \App\Http\Controllers\Backend\AgenteController::class);
    Route::post('/agente/{id}/azione/{azione}', [\App\Http\Controllers\Backend\AgenteController::class, 'azioni']);

    Route::get('agente/{agente}/tipo-contratto/{tipocontratto}', [\App\Http\Controllers\Backend\FasciaTipoContrattoController::class, 'edit']);
    Route::patch('agente/{agente}/tipo-contratto/{tipocontratto}', [\App\Http\Controllers\Backend\FasciaTipoContrattoController::class, 'update']);

    //Route::resource('/operatore', \App\Http\Controllers\Backend\OperatoreController::class)->except(['show']);

    Route::resource('/notifica', \App\Http\Controllers\Backend\NotificaController::class)->except(['show']);


    Route::get('produzione-operatore', [\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'index']);
    Route::get('produzione-operatore/{id}/crea-proforma', [\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'creaProforma']);

    Route::get('test-twilio', [\App\Http\Controllers\TestTwilio::class, 'show']);
    Route::post('test-twilio', [\App\Http\Controllers\TestTwilio::class, 'post']);

    Route::resource('/tipo-contratto', \App\Http\Controllers\Backend\TipoContrattoController::class)->except(['show']);
    Route::resource('/tipo-caf-patronato', \App\Http\Controllers\Backend\TipoCafPatronatoController::class)->except(['show']);
    Route::resource('/tipo-esito', \App\Http\Controllers\Backend\EsitoTelefoniaController::class)->except(['show']);
    Route::resource('/tipo-visura', \App\Http\Controllers\Backend\TipoVisuraController::class)->except(['show']);
    Route::resource('/offerta-sim', \App\Http\Controllers\Backend\OffertaSimController::class)->except(['show']);

    //Sms
    Route::get('/sms', [\App\Http\Controllers\Backend\SmsController::class, 'index']);
    Route::get('/sms/{id}', [\App\Http\Controllers\Backend\SmsController::class, 'show']);
    Route::get('/invia-sms', [\App\Http\Controllers\Backend\SmsController::class, 'create']);
    Route::post('/invia-sms', [\App\Http\Controllers\Backend\SmsController::class, 'store']);


    //Gestori
    Route::resource('/gestore', \App\Http\Controllers\Backend\GestoreController::class)->except(['show']);
    Route::resource('/gestore-attivazione', \App\Http\Controllers\Backend\GestoreAttivazioniController::class)->except(['show']);
    Route::resource('/gestore-contratto-energia', \App\Http\Controllers\Backend\GestoreContrattoEnergiaController::class)->except(['show']);


    //Esiti
    Route::resource('esito-servizio', \App\Http\Controllers\Backend\EsitoServizioFinanziarioController::class)->except(['show']);
    Route::resource('esito-caf-patronato', \App\Http\Controllers\Backend\EsitoCafPatronatoController::class)->except(['show']);
    Route::resource('esito-contratto-energia', \App\Http\Controllers\Backend\EsitoContrattoEnergiaController::class)->except(['show']);
    Route::resource('esito-attivazione-sim', \App\Http\Controllers\Backend\EsitoAttivazioneSimController::class)->except(['show']);
    Route::resource('esito-visura', \App\Http\Controllers\Backend\EsitoVisuraController::class)->except(['show']);
    Route::resource('esito-segnalazione', \App\Http\Controllers\Backend\EsitoSegnalazioneController::class)->except(['show']);
    Route::resource('esito-comparasemplice', \App\Http\Controllers\Backend\EsitoComparasempliceController::class)->except(['show']);

    //Listini
    Route::resource('listino', \App\Http\Controllers\Backend\ListinoController::class);
    Route::resource('brt-listino', \App\Http\Controllers\Backend\ListinoBrtController::class);
    Route::resource('brt-listino-europa', \App\Http\Controllers\Backend\ListinoBrtEuropaController::class);

    //Chiamate api
    Route::get('chiamata-api', [\App\Http\Controllers\Backend\ChiamataApiController::class, 'index']);
    Route::get('chiamata-api/{id}', [\App\Http\Controllers\Backend\ChiamataApiController::class, 'show']);

    //Causali ticket
    Route::resource('causale-ticket', \App\Http\Controllers\Backend\CausaleTicketController::class)->except(['show']);

    Route::get('prezzi-spedizioni', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'showPrezziAgenti']);
    Route::post('prezzi-spedizioni', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'updateRicaricoAgenti']);


});
