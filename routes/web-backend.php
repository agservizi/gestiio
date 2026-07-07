<?php

use App\Http\Controllers\Backend\AgenteController;
use App\Http\Controllers\Backend\AiAutomationController;
use App\Http\Controllers\Backend\AjaxController;
use App\Http\Controllers\Backend\AllegatoServizioController;
use App\Http\Controllers\Backend\Autenticazione2faController;
use App\Http\Controllers\Backend\BrtOrmController;
use App\Http\Controllers\Backend\CafPatronatoController;
use App\Http\Controllers\Backend\CartellaFilesController;
use App\Http\Controllers\Backend\CausaleTicketController;
use App\Http\Controllers\Backend\ChatController;
use App\Http\Controllers\Backend\ChiamataApiController;
use App\Http\Controllers\Backend\ClienteAssistenzaController;
use App\Http\Controllers\Backend\ClienteController;
use App\Http\Controllers\Backend\ContattoBrtController;
use App\Http\Controllers\Backend\ContrattoEnergiaController;
use App\Http\Controllers\Backend\ContrattoTelefoniaController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\EbikeOrdineController;
use App\Http\Controllers\Backend\EbikeProdottoController;
use App\Http\Controllers\Backend\EsitoCafPatronatoController;
use App\Http\Controllers\Backend\EsitoContrattoEnergiaController;
use App\Http\Controllers\Backend\EsitoTelefoniaController;
use App\Http\Controllers\Backend\EsitoVisuraController;
use App\Http\Controllers\Backend\EsportaController;
use App\Http\Controllers\Backend\FasciaTipoContrattoController;
use App\Http\Controllers\Backend\FatturaProformaController;
use App\Http\Controllers\Backend\GestoreAttivazioniController;
use App\Http\Controllers\Backend\GestoreContrattoEnergiaController;
use App\Http\Controllers\Backend\GestoreController;
use App\Http\Controllers\Backend\InpostConsoleController;
use App\Http\Controllers\Backend\InpostPickupController;
use App\Http\Controllers\Backend\InpostReturnController;
use App\Http\Controllers\Backend\ListinoBrtController;
use App\Http\Controllers\Backend\ListinoBrtEuropaController;
use App\Http\Controllers\Backend\ListinoController;
use App\Http\Controllers\Backend\ListinoInpostController;
use App\Http\Controllers\Backend\ModalController;
use App\Http\Controllers\Backend\NotificaController;
use App\Http\Controllers\Backend\OffertaSimController;
use App\Http\Controllers\Backend\PaymentController;
use App\Http\Controllers\Backend\PortafoglioController;
use App\Http\Controllers\Backend\ProdottoAssistenzaController;
use App\Http\Controllers\Backend\ProduzioneOperatoreController;
use App\Http\Controllers\Backend\ProfiloController;
use App\Http\Controllers\Backend\RegistriController;
use App\Http\Controllers\Backend\RicaricaCartaIbanController;
use App\Http\Controllers\Backend\RicaricaPlafonController;
use App\Http\Controllers\Backend\RichiestaAssistenzaController;
use App\Http\Controllers\Backend\Select2;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\SpedizioneBrtController;
use App\Http\Controllers\Backend\SpedizioneInpostController;
use App\Http\Controllers\Backend\TicketsController;
use App\Http\Controllers\Backend\TipoCafPatronatoController;
use App\Http\Controllers\Backend\TipoContrattoController;
use App\Http\Controllers\Backend\TipoVisuraController;
use App\Http\Controllers\Backend\VisuraCameraleController;
use App\Http\Controllers\Backend\VisuraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role_or_permission:admin|agente|supervisore|operatore'])->group(function () {
    Route::get('2fa', [Autenticazione2faController::class, 'show']);
});

Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa']], function () {
    Route::get('/', [DashboardController::class, 'show']);
    Route::get('/lavoro', [DashboardController::class, 'show']);
    Route::post('/dashboard/bulk-action', [DashboardController::class, 'bulkAction']);
    Route::get('/ai-control-tower', [AiAutomationController::class, 'index']);
    Route::post('/ai-control-tower/dashboard-review', [AiAutomationController::class, 'triggerDashboard']);
    Route::post('/ai-control-tower/ask', [AiAutomationController::class, 'ask']);
    Route::post('/gestiio-ai/chat', [AiAutomationController::class, 'chat']);
    Route::post('/gestiio-ai/ricarica-plafond/intent', [PaymentController::class, 'prepareChatRicarica']);
    Route::post('/gestiio-ai/ricarica-plafond/pay', [PaymentController::class, 'storePagamentoChat']);
    Route::post('/ai-suggestion/{suggestion}/feedback', [AiAutomationController::class, 'feedback']);

    // Contratto
    Route::post('/allegato-contratto', [ContrattoTelefoniaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto', [ContrattoTelefoniaController::class, 'deleteAllegato']);
    Route::get('contratto/{contrattoId}/allegato/{allegatoId}', [ContrattoTelefoniaController::class, 'downloadAllegato']);
    Route::resource('contratto', ContrattoTelefoniaController::class);
    Route::post('/contratto/{id}/azione/{azione}', [ContrattoTelefoniaController::class, 'azioni']);
    Route::post('/contratto/verifica-cf-rischio', [ContrattoTelefoniaController::class, 'verificaCodiceFiscaleRischio']);
    Route::get('pda-contratto/{id}', [ContrattoTelefoniaController::class, 'pda']);

    // ContrattoEnergia
    Route::post('/allegato-contratto-energia', [ContrattoEnergiaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto-energia', [ContrattoEnergiaController::class, 'deleteAllegato']);
    Route::get('contratto-energia/{contrattoId}/allegato/{allegatoId}', [ContrattoEnergiaController::class, 'downloadAllegato']);
    Route::get('pda-contratto-energia/{id}', [ContrattoEnergiaController::class, 'pda']);
    Route::get('/contratto-energia/create/{servizio?}', [ContrattoEnergiaController::class, 'create']);

    Route::resource('contratto-energia', ContrattoEnergiaController::class)->except(['create']);
    Route::post('/contratto-energia/verifica-cf-rischio', [ContrattoEnergiaController::class, 'verificaCodiceFiscaleRischio']);
    Route::get('/contratto-energia/{id}/duplica-anagrafica', [ContrattoEnergiaController::class, 'duplicaAnagrafica']);
    Route::post('/contratto-energia/{id}/switch-categoria', [ContrattoEnergiaController::class, 'switchCategoria']);
    Route::post('/contratto-energia/{id}/segnala-chat', [ContrattoEnergiaController::class, 'segnalaChat']);
    Route::post('/contratto-energia/{id}/apri-chat', [ContrattoEnergiaController::class, 'apriChat']);
    // Route::post('/contratto-energia/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'azioni']);

    // Caf patronato
    Route::get('/caf-patronato/{contrattoId}/allegato/{allegatoId}', [CafPatronatoController::class, 'downloadAllegato']);
    Route::get('/caf-patronato-download/{contrattoId}', [CafPatronatoController::class, 'downloadAllegatoCliente']);
    Route::get('/caf-patronato-allegati-orfani', [CafPatronatoController::class, 'allegatiOrfani']);

    Route::get('/caf-patronato/create/{servizio?}', [CafPatronatoController::class, 'create']);
    Route::resource('/caf-patronato', CafPatronatoController::class)->except(['create']);
    Route::post('/allegato-caf', [CafPatronatoController::class, 'uploadAllegato']);
    Route::delete('/allegato-caf', [CafPatronatoController::class, 'deleteAllegato']);

    // Visure
    Route::get('/visura/create/{servizio?}', [VisuraController::class, 'create']);
    Route::resource('/visura', VisuraController::class)->except(['create']);
    Route::post('/visura/ricerca-azienda', [VisuraController::class, 'ricercaAzienda']);
    Route::post('/visura/ricerca-azienda-dettaglio', [VisuraController::class, 'ricercaAziendaDettaglio']);
    Route::post('/allegato-visura', [VisuraController::class, 'uploadAllegato']);
    Route::delete('/allegato-visura', [VisuraController::class, 'deleteAllegato']);
    Route::post('/visura/{id}/openapi-richiedi', [VisuraController::class, 'richiediOpenApi']);
    Route::post('/visura/{id}/openapi-sync', [VisuraController::class, 'sincronizzaOpenApi']);
    Route::get('/visura/{id}/openapi-documento', [VisuraController::class, 'scaricaDocumentoOpenApi']);

    // Spedizioni
    Route::get('spedizione-brt/bordero/{id?}', [SpedizioneBrtController::class, 'bordero']);
    Route::get('/spedizione-brt/create/{zona?}', [SpedizioneBrtController::class, 'create']);
    Route::resource('spedizione-brt', SpedizioneBrtController::class)->except(['create']);
    Route::delete('spedizione-brt-annulla/{id}', [SpedizioneBrtController::class, 'annulla']);
    Route::post('spedizione-brt/{id}/conferma', [SpedizioneBrtController::class, 'conferma']);
    Route::post('spedizione-brt/{id}/routing', [SpedizioneBrtController::class, 'routing']);
    Route::get('spedizione-brt/{id}/tracking', [SpedizioneBrtController::class, 'tracking']);
    Route::post('spedizione-brt/{id}/tracking-refresh', [SpedizioneBrtController::class, 'trackingRefresh']);
    Route::post('spedizione-brt/tracking/refresh-bulk', [SpedizioneBrtController::class, 'trackingRefreshBulk']);
    Route::get('spedizione-brt/{id}/etichetta/{index}', [SpedizioneBrtController::class, 'etichetta']);
    Route::get('spedizione-brt/{id}/pdf/{tipopdf}', [SpedizioneBrtController::class, 'pdf']);
    Route::resource('spedizione-inpost', SpedizioneInpostController::class);
    Route::post('spedizione-inpost/{id}/tracking-refresh', [SpedizioneInpostController::class, 'trackingRefresh']);
    Route::post('spedizione-inpost/tracking/refresh-bulk', [SpedizioneInpostController::class, 'trackingRefreshBulk']);
    Route::get('spedizione-inpost/{id}/etichetta', [SpedizioneInpostController::class, 'etichetta']);
    Route::get('spedizione-inpost/{id}/sync', [SpedizioneInpostController::class, 'sync']);
    Route::resource('inpost-return', InpostReturnController::class);
    Route::get('inpost-return/{id}/etichetta', [InpostReturnController::class, 'etichetta']);
    Route::post('inpost-pickup/cutoff-time', [InpostPickupController::class, 'cutoffTime']);
    Route::get('inpost-pickup/{id}/sync', [InpostPickupController::class, 'sync']);
    Route::post('inpost-pickup/{id}/cancel', [InpostPickupController::class, 'cancel']);
    Route::resource('inpost-pickup', InpostPickupController::class);
    Route::get('inpost-return/{id}/sync', [InpostReturnController::class, 'sync']);
    Route::post('brt-orm', [BrtOrmController::class, 'store']);
    Route::delete('brt-orm/{reservationId}', [BrtOrmController::class, 'destroy']);

    // Allegati tutti servizi
    Route::get('/allegato-servizio/{contrattoId}', [AllegatoServizioController::class, 'downloadAllegatoCliente']);
    Route::get('/allegato-servizio/{contrattoId}/allegato/{allegatoId}', [AllegatoServizioController::class, 'downloadAllegato']);

    Route::post('/allegato-servizio', [AllegatoServizioController::class, 'uploadAllegato']);
    Route::delete('/allegato-servizio', [AllegatoServizioController::class, 'deleteAllegato']);

    // Documenti
    Route::get('documenti/{id?}', [CartellaFilesController::class, 'index']);

    Route::resource('documenti/{cartellaId}/cartella', CartellaFilesController::class)->except(['index', 'show']);

    Route::get('documento-upload/{cartellaId}', [CartellaFilesController::class, 'show']);
    Route::post('documento-upload/{cartellaId}', [CartellaFilesController::class, 'upload']);
    Route::delete('documento-cancella', [CartellaFilesController::class, 'cancellaFile']);
    Route::get('documento-download/{id}', [CartellaFilesController::class, 'download']);
    Route::get('documento-preview/{id}', [CartellaFilesController::class, 'preview']);
    Route::post('documento-download-multiplo', [CartellaFilesController::class, 'downloadMultiplo']);
    Route::post('documento-sposta', [CartellaFilesController::class, 'moveFile']);
    Route::post('cartella-sposta', [CartellaFilesController::class, 'moveFolder']);
    Route::post('documento-rinomina', [CartellaFilesController::class, 'renameFile']);
    Route::post('documento/{id}/versione', [CartellaFilesController::class, 'uploadVersion']);
    Route::post('documento/{id}/versione/{versionId}/rollback', [CartellaFilesController::class, 'rollbackVersion']);
    Route::post('documento-scadenza', [CartellaFilesController::class, 'setExpiry']);
    Route::post('documenti/{id}/visibilita', [CartellaFilesController::class, 'setFolderVisibility']);
    Route::post('documento-share', [CartellaFilesController::class, 'createShareLink']);
    Route::get('documenti-audit-export', [CartellaFilesController::class, 'exportAuditCsv']);

    Route::get('/modal/{modal}/{id?}', [ModalController::class, 'show']);

    // Ajax
    Route::post('/ajax/{cosa}', [AjaxController::class, 'post']);

    // Visura
    // Route::resource('/visura', \App\Http\Controllers\Backend\VisuraCameraleController::class)->only(['index', 'update', 'show']);
    Route::get('/visura-cerca-azienda', [VisuraCameraleController::class, 'showCercaAzienda']);
    Route::post('/visura-cerca-azienda', [VisuraCameraleController::class, 'postCercaAzienda']);
    Route::get('/visura-mostra-azienda', [VisuraCameraleController::class, 'showAziende']);

    // Fatture proforma
    Route::get('fattura-proforma/{id}/pdf', [FatturaProformaController::class, 'pdf']);
    Route::resource('fattura-proforma', FatturaProformaController::class)->except(['edit', 'update']);

    // Portafoglio
    Route::post('/portafoglio/richiedi-spostamento', [PortafoglioController::class, 'richiediSpostamento']);
    Route::post('/portafoglio/sposta', [PortafoglioController::class, 'sposta']);
    Route::post('/portafoglio/storna', [PortafoglioController::class, 'storna']);
    Route::post('/portafoglio/richieste-spostamento/{id}/applica', [PortafoglioController::class, 'applicaRichiestaSpostamento']);
    Route::resource('/portafoglio', PortafoglioController::class);

    // Ebike B2B
    Route::resource('/ebike/prodotti', EbikeProdottoController::class)->except(['show']);
    Route::post('/ebike/ordini/{id}/carica-pagamento', [EbikeOrdineController::class, 'caricaPagamento']);
    Route::post('/ebike/ordini/{id}/conferma-pagamento', [EbikeOrdineController::class, 'confermaPagamento']);
    Route::post('/ebike/ordini/{id}/imposta-tracking', [EbikeOrdineController::class, 'impostaTracking']);
    Route::post('/ebike/ordini/{id}/segna-consegnato', [EbikeOrdineController::class, 'segnaConsegnato']);
    Route::post('/ebike/ordini/{id}/annulla', [EbikeOrdineController::class, 'annulla']);
    Route::resource('/ebike/ordini', EbikeOrdineController::class)->only(['index', 'create', 'store', 'show']);

    Route::post('/pagamento/{servizio}', [PaymentController::class, 'pagamento']);
    Route::get('/pagamento/{servizio}/{result}', [PaymentController::class, 'response']);
    Route::post('/pagamento', [PaymentController::class, 'storePagamento']);
    Route::post('/pagamento-bonifico-stripe', [PaymentController::class, 'storeBonificoStripe']);
    Route::get('/pagamento-success', [PaymentController::class, 'pagamentoSuccess']);

    // Tickets
    Route::resource('/ticket', TicketsController::class, ['as' => 'ticket-admin']);

    // Chat interna
    Route::get('/chat-interna', [ChatController::class, 'index']);
    Route::post('/chat-interna/thread', [ChatController::class, 'storeThread']);
    Route::get('/chat-interna/poll', [ChatController::class, 'poll']);
    Route::get('/chat-interna/poll-global', [ChatController::class, 'pollGlobalNotifications']);
    Route::get('/chat-interna/search', [ChatController::class, 'search']);
    Route::get('/chat-interna/push/vapid-public-key', [ChatController::class, 'pushVapidPublicKey']);
    Route::post('/chat-interna/push/subscribe', [ChatController::class, 'subscribePush']);
    Route::post('/chat-interna/push/unsubscribe', [ChatController::class, 'unsubscribePush']);
    Route::get('/chat-interna/attachment/{attachment}', [ChatController::class, 'attachment']);
    Route::get('/chat-interna/{thread}/messages', [ChatController::class, 'messages']);
    Route::post('/chat-interna/{thread}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chat-interna/{thread}/forward', [ChatController::class, 'forwardMessages']);
    Route::post('/chat-interna/mention/resolve', [ChatController::class, 'resolveMention']);
    Route::post('/chat-interna/{thread}/typing', [ChatController::class, 'typing']);
    Route::post('/chat-interna/{thread}/close', [ChatController::class, 'closeThread']);
    Route::post('/chat-interna/{thread}/mute', [ChatController::class, 'toggleThreadMute']);
    Route::post('/chat-interna/message/{message}/reaction', [ChatController::class, 'toggleReaction']);
    Route::post('/chat-interna/message/{message}/pin', [ChatController::class, 'togglePin']);
    Route::post('/chat-interna/message/{message}/favorite', [ChatController::class, 'toggleFavorite']);
    Route::patch('/chat-interna/message/{message}', [ChatController::class, 'updateMessage']);
    Route::delete('/chat-interna/message/{message}', [ChatController::class, 'deleteMessage']);
    Route::get('/chat-interna/templates', [ChatController::class, 'quickTemplates']);
    Route::post('/chat-interna/templates', [ChatController::class, 'saveQuickTemplate']);

    Route::get('select2', [Select2::class, 'response']);

    Route::post('/contratto-stato/{id}', [ContrattoTelefoniaController::class, 'aggiornaStato']);
    Route::post('/contratto-energia-stato/{id}', [ContrattoEnergiaController::class, 'aggiornaStato']);
    Route::post('/caf-patronato-stato/{id}', [CafPatronatoController::class, 'aggiornaStato']);
    Route::post('/visura-stato/{id}', [VisuraController::class, 'aggiornaStato']);

    Route::get('profilo', [ProfiloController::class, 'show']);
    Route::get('profilo-listino/{tipoContratto}', [ProfiloController::class, 'showListino']);

    // Contatti Brt
    Route::resource('contatto-brt', ContattoBrtController::class)->except(['show']);

});

Route::group(['middleware' => ['auth', '2fa', 'role_or_permission:admin']], function () {

    // Cliente
    Route::get('cliente/{id}/tab/{tab}', [ClienteController::class, 'tab']);
    Route::resource('cliente', ClienteController::class);
    Route::post('/cliente/{id}/azione/{azione}', [ClienteController::class, 'azioni']);

    Route::get('esporta/{cosa}', [EsportaController::class, 'esporta']);

    // Impostazioni
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::get('/controlli-contratti', [SettingController::class, 'index'])->name('controlli-contratti');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::get('/settings/export', [SettingController::class, 'export'])->name('settings.export');
    Route::post('/settings/import', [SettingController::class, 'import'])->name('settings.import');
    Route::post('/settings/rollback/{auditLog}', [SettingController::class, 'rollback'])->name('settings.rollback');
    Route::post('/settings/test-codice-fiscale', [SettingController::class, 'testCodiceFiscale'])->name('settings.test-codice-fiscale');

    // Assistenza
    Route::resource('cliente-assistenza', ClienteAssistenzaController::class);
    Route::resource('prodotto-assistenza', ProdottoAssistenzaController::class)->except(['show']);
    Route::resource('richiesta-assistenza', RichiestaAssistenzaController::class);
    Route::get('richiesta-assistenza/{id}/pdf', [RichiestaAssistenzaController::class, 'pdf']);
    Route::post('richiesta-assistenza/{id}/reinvio-credenziali', [RichiestaAssistenzaController::class, 'reinviaCredenziali']);
    // Registri
    Route::get('registro/{cosa}', [RegistriController::class, 'index'])
        ->whereIn('cosa', ['login', 'email', 'backup-db', 'elenco_licenze', 'info-sito', 'errori', 'upload', 'modifiche'])
        ->name('registro.index');
    Route::post('registro/backup-db/esegui', [RegistriController::class, 'eseguiBackup'])->name('registro.backup.esegui');
    Route::get('registro/backup-db/scarica', [RegistriController::class, 'scaricaBackup'])->name('registro.backup.scarica');

    // plafond
    Route::get('/carica-plafond', [RicaricaPlafonController::class, 'show']);
    Route::post('/carica-plafond', [RicaricaPlafonController::class, 'store']);

    // Agente
    Route::get('/agente-tab/{id}/tab/{tab}', [AgenteController::class, 'tab']);
    Route::resource('/agente', AgenteController::class);
    Route::post('/agente/{id}/azione/{azione}', [AgenteController::class, 'azioni']);

    Route::get('agente/{agente}/tipo-contratto/{tipocontratto}', [FasciaTipoContrattoController::class, 'edit']);
    Route::patch('agente/{agente}/tipo-contratto/{tipocontratto}', [FasciaTipoContrattoController::class, 'update']);

    // Route::resource('/operatore', \App\Http\Controllers\Backend\OperatoreController::class)->except(['show']);

    Route::resource('/notifica', NotificaController::class)->except(['show']);

    Route::get('produzione-operatore', [ProduzioneOperatoreController::class, 'index']);
    Route::get('produzione-operatore/{id}/crea-proforma', [ProduzioneOperatoreController::class, 'creaProforma']);

    Route::resource('/tipo-contratto', TipoContrattoController::class)->except(['show']);
    Route::resource('/tipo-caf-patronato', TipoCafPatronatoController::class)->except(['show']);
    Route::resource('/tipo-esito', EsitoTelefoniaController::class)->except(['show']);
    Route::resource('/tipo-visura', TipoVisuraController::class)->except(['show']);
    Route::resource('/offerta-sim', OffertaSimController::class)->except(['show']);

    // Gestori
    Route::resource('/gestore', GestoreController::class)->except(['show']);
    Route::resource('/gestore-attivazione', GestoreAttivazioniController::class)->except(['show']);
    Route::resource('/gestore-contratto-energia', GestoreContrattoEnergiaController::class)->except(['show']);

    // Esiti
    Route::resource('esito-caf-patronato', EsitoCafPatronatoController::class)->except(['show']);
    Route::resource('esito-contratto-energia', EsitoContrattoEnergiaController::class)->except(['show']);
    Route::resource('esito-visura', EsitoVisuraController::class)->except(['show']);

    // Listini
    Route::resource('listino', ListinoController::class);
    Route::resource('brt-listino', ListinoBrtController::class);
    Route::resource('brt-listino-europa', ListinoBrtEuropaController::class);
    Route::resource('inpost-listino', ListinoInpostController::class);
    Route::get('inpost-account', [InpostConsoleController::class, 'account']);
    Route::get('inpost-deposits', [InpostConsoleController::class, 'deposits']);
    Route::post('inpost-deposits', [InpostConsoleController::class, 'storeDeposit']);

    // Chiamate api
    Route::get('chiamata-api', [ChiamataApiController::class, 'index']);
    Route::get('chiamata-api/{id}', [ChiamataApiController::class, 'show']);

    // Causali ticket
    Route::resource('causale-ticket', CausaleTicketController::class)->except(['show']);

    Route::get('prezzi-spedizioni', [SpedizioneBrtController::class, 'showPrezziAgenti']);
    Route::post('prezzi-spedizioni', [SpedizioneBrtController::class, 'updateRicaricoAgenti']);

    // Ricariche carte prepagate - IBAN clienti
    Route::resource('ricarica-carta-iban', RicaricaCartaIbanController::class)->except(['show']);

});
