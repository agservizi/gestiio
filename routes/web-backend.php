<?php

use App\Models\MessaggioFormAssistenza;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role_or_permission:admin|agente|supervisore|operatore'])->group(function () {
    Route::get('2fa', [\App\Http\Controllers\Backend\Autenticazione2faController::class, 'show']);
});

Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa']], function () {
    Route::get('/', [\App\Http\Controllers\Backend\DashboardController::class, 'show']);
    Route::post('/dashboard/bulk-action', [\App\Http\Controllers\Backend\DashboardController::class, 'bulkAction']);

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
    Route::get('pda-contratto-energia/{id}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'pda']);
    Route::get('/contratto-energia/create/{servizio?}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'create']);

    Route::resource('contratto-energia', \App\Http\Controllers\Backend\ContrattoEnergiaController::class)->except(['create']);
    Route::get('/contratto-energia/{id}/duplica-anagrafica', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'duplicaAnagrafica']);
    Route::post('/contratto-energia/{id}/switch-categoria', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'switchCategoria']);
    Route::post('/contratto-energia/{id}/segnala-chat', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'segnalaChat']);
    Route::post('/contratto-energia/{id}/apri-chat', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'apriChat']);
    //Route::post('/contratto-energia/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'azioni']);




    //Caf patronato
    Route::get('/caf-patronato/{contrattoId}/allegato/{allegatoId}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'downloadAllegato']);
    Route::get('/caf-patronato-download/{contrattoId}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'downloadAllegatoCliente']);
    Route::get('/caf-patronato-allegati-orfani', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'allegatiOrfani']);

    Route::get('/caf-patronato/create/{servizio?}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'create']);
    Route::resource('/caf-patronato', \App\Http\Controllers\Backend\CafPatronatoController::class)->except(['create']);
    Route::post('/allegato-caf', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'uploadAllegato']);
    Route::delete('/allegato-caf', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'deleteAllegato']);

    //Visure
    Route::get('/visura/create/{servizio?}', [\App\Http\Controllers\Backend\VisuraController::class, 'create']);
    Route::resource('/visura', \App\Http\Controllers\Backend\VisuraController::class)->except(['create']);
    Route::post('/visura/ricerca-azienda', [\App\Http\Controllers\Backend\VisuraController::class, 'ricercaAzienda']);
    Route::post('/visura/ricerca-azienda-dettaglio', [\App\Http\Controllers\Backend\VisuraController::class, 'ricercaAziendaDettaglio']);
    Route::post('/allegato-visura', [\App\Http\Controllers\Backend\VisuraController::class, 'uploadAllegato']);
    Route::delete('/allegato-visura', [\App\Http\Controllers\Backend\VisuraController::class, 'deleteAllegato']);
    Route::post('/visura/{id}/openapi-richiedi', [\App\Http\Controllers\Backend\VisuraController::class, 'richiediOpenApi']);
    Route::post('/visura/{id}/openapi-sync', [\App\Http\Controllers\Backend\VisuraController::class, 'sincronizzaOpenApi']);
    Route::get('/visura/{id}/openapi-documento', [\App\Http\Controllers\Backend\VisuraController::class, 'scaricaDocumentoOpenApi']);


    //Spedizioni
    Route::get('spedizione-brt/bordero/{id?}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'bordero']);
    Route::get('/spedizione-brt/create/{zona?}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'create']);
    Route::resource('spedizione-brt', \App\Http\Controllers\Backend\SpedizioneBrtController::class)->except(['create']);
    Route::delete('spedizione-brt-annulla/{id}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'annulla']);
    Route::post('spedizione-brt/{id}/conferma', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'conferma']);
    Route::post('spedizione-brt/{id}/routing', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'routing']);
    Route::get('spedizione-brt/{id}/tracking', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'tracking']);
    Route::post('spedizione-brt/{id}/tracking-refresh', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'trackingRefresh']);
    Route::post('spedizione-brt/tracking/refresh-bulk', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'trackingRefreshBulk']);
    Route::get('spedizione-brt/{id}/etichetta/{index}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'etichetta']);
    Route::get('spedizione-brt/{id}/pdf/{tipopdf}', [\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'pdf']);
    Route::post('brt-orm', [\App\Http\Controllers\Backend\BrtOrmController::class, 'store']);
    Route::delete('brt-orm/{reservationId}', [\App\Http\Controllers\Backend\BrtOrmController::class, 'destroy']);


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
    Route::get('documento-preview/{id}', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'preview']);
    Route::post('documento-download-multiplo', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'downloadMultiplo']);
    Route::post('documento-sposta', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'moveFile']);
    Route::post('cartella-sposta', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'moveFolder']);
    Route::post('documento-rinomina', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'renameFile']);
    Route::post('documento/{id}/versione', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'uploadVersion']);
    Route::post('documento/{id}/versione/{versionId}/rollback', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'rollbackVersion']);
    Route::post('documento-scadenza', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'setExpiry']);
    Route::post('documenti/{id}/visibilita', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'setFolderVisibility']);
    Route::post('documento-share', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'createShareLink']);
    Route::get('documenti-audit-export', [\App\Http\Controllers\Backend\CartellaFilesController::class, 'exportAuditCsv']);

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

    //Chat interna
    Route::get('/chat-interna', [\App\Http\Controllers\Backend\ChatController::class, 'index']);
    Route::post('/chat-interna/thread', [\App\Http\Controllers\Backend\ChatController::class, 'storeThread']);
    Route::get('/chat-interna/poll', [\App\Http\Controllers\Backend\ChatController::class, 'poll']);
    Route::get('/chat-interna/poll-global', [\App\Http\Controllers\Backend\ChatController::class, 'pollGlobalNotifications']);
    Route::get('/chat-interna/search', [\App\Http\Controllers\Backend\ChatController::class, 'search']);
    Route::get('/chat-interna/push/vapid-public-key', [\App\Http\Controllers\Backend\ChatController::class, 'pushVapidPublicKey']);
    Route::post('/chat-interna/push/subscribe', [\App\Http\Controllers\Backend\ChatController::class, 'subscribePush']);
    Route::post('/chat-interna/push/unsubscribe', [\App\Http\Controllers\Backend\ChatController::class, 'unsubscribePush']);
    Route::get('/chat-interna/attachment/{attachment}', [\App\Http\Controllers\Backend\ChatController::class, 'attachment']);
    Route::get('/chat-interna/{thread}/messages', [\App\Http\Controllers\Backend\ChatController::class, 'messages']);
    Route::post('/chat-interna/{thread}/messages', [\App\Http\Controllers\Backend\ChatController::class, 'sendMessage']);
    Route::post('/chat-interna/{thread}/forward', [\App\Http\Controllers\Backend\ChatController::class, 'forwardMessages']);
    Route::post('/chat-interna/mention/resolve', [\App\Http\Controllers\Backend\ChatController::class, 'resolveMention']);
    Route::post('/chat-interna/{thread}/typing', [\App\Http\Controllers\Backend\ChatController::class, 'typing']);
    Route::post('/chat-interna/{thread}/close', [\App\Http\Controllers\Backend\ChatController::class, 'closeThread']);
    Route::post('/chat-interna/{thread}/mute', [\App\Http\Controllers\Backend\ChatController::class, 'toggleThreadMute']);
    Route::post('/chat-interna/message/{message}/reaction', [\App\Http\Controllers\Backend\ChatController::class, 'toggleReaction']);
    Route::post('/chat-interna/message/{message}/pin', [\App\Http\Controllers\Backend\ChatController::class, 'togglePin']);
    Route::post('/chat-interna/message/{message}/favorite', [\App\Http\Controllers\Backend\ChatController::class, 'toggleFavorite']);
    Route::patch('/chat-interna/message/{message}', [\App\Http\Controllers\Backend\ChatController::class, 'updateMessage']);
    Route::delete('/chat-interna/message/{message}', [\App\Http\Controllers\Backend\ChatController::class, 'deleteMessage']);
    Route::get('/chat-interna/templates', [\App\Http\Controllers\Backend\ChatController::class, 'quickTemplates']);
    Route::post('/chat-interna/templates', [\App\Http\Controllers\Backend\ChatController::class, 'saveQuickTemplate']);

    Route::get('select2', [\App\Http\Controllers\Backend\Select2::class, 'response']);

    Route::post('/contratto-stato/{id}', [\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'aggiornaStato']);
    Route::post('/contratto-energia-stato/{id}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'aggiornaStato']);
    Route::post('/caf-patronato-stato/{id}', [\App\Http\Controllers\Backend\CafPatronatoController::class, 'aggiornaStato']);
    Route::post('/visura-stato/{id}', [\App\Http\Controllers\Backend\VisuraController::class, 'aggiornaStato']);

    Route::get('profilo', [\App\Http\Controllers\Backend\ProfiloController::class, 'show']);
    Route::get('profilo-listino/{tipoContratto}', [\App\Http\Controllers\Backend\ProfiloController::class, 'showListino']);

    //Contatti Brt
    Route::resource('contatto-brt', \App\Http\Controllers\Backend\ContattoBrtController::class)->except(['show']);


});


Route::group(['middleware' => ['auth', 'role_or_permission:admin']], function () {


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

    $testTwilioController = 'App\\Http\\Controllers\\TestTwilio';
    if (class_exists($testTwilioController)) {
        Route::get('test-twilio', [$testTwilioController, 'show']);
        Route::post('test-twilio', [$testTwilioController, 'post']);
    }

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
    Route::resource('esito-caf-patronato', \App\Http\Controllers\Backend\EsitoCafPatronatoController::class)->except(['show']);
    Route::resource('esito-contratto-energia', \App\Http\Controllers\Backend\EsitoContrattoEnergiaController::class)->except(['show']);
    Route::resource('esito-visura', \App\Http\Controllers\Backend\EsitoVisuraController::class)->except(['show']);

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
