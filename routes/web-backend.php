<?php

use App\Http\Controllers\Backend\AgenteController;
use App\Http\Controllers\Backend\AjaxController;
use App\Http\Controllers\Backend\AllegatoMobileScanController;
use App\Http\Controllers\Backend\AllegatoServizioController;
use App\Http\Controllers\Backend\AttivaServizioController;
use App\Http\Controllers\Backend\Autenticazione2faController;
use App\Http\Controllers\Backend\BillingDocumentController;
use App\Http\Controllers\Backend\BillingFornitoreController;
use App\Http\Controllers\Backend\BrtOrmController;
use App\Http\Controllers\Backend\CafPatronatoController;
use App\Http\Controllers\Backend\CanoneServiziController;
use App\Http\Controllers\Backend\ContentReportController;
use App\Http\Controllers\Backend\CartellaFilesController;
use App\Http\Controllers\Backend\CausaleTicketController;
use App\Http\Controllers\Backend\CentroNotificheController;
use App\Http\Controllers\Backend\ChatController;
use App\Http\Controllers\Backend\ChiamataApiController;
use App\Http\Controllers\Backend\ClienteAssistenzaController;
use App\Http\Controllers\Backend\ClienteController;
use App\Http\Controllers\Backend\ContattoBrtController;
use App\Http\Controllers\Backend\ContractSigningController;
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
use App\Http\Controllers\Backend\LiquidazioneProvvigioniController;
use App\Http\Controllers\Backend\ProvvigioneClawbackController;
use App\Http\Controllers\Backend\GestoreAttivazioniController;
use App\Http\Controllers\Backend\GestoreContrattoEnergiaController;
use App\Http\Controllers\Backend\GestoreController;
use App\Http\Controllers\Backend\InpostConsoleController;
use App\Http\Controllers\Backend\InpostPickupController;
use App\Http\Controllers\Backend\InpostReturnController;
use App\Http\Controllers\Backend\ListinoBrtController;
use App\Http\Controllers\Backend\ListinoBrtEuropaController;
use App\Http\Controllers\Backend\GaraController;
use App\Http\Controllers\Backend\ListinoController;
use App\Http\Controllers\Backend\ListinoInpostController;
use App\Http\Controllers\Backend\LockerPackageController;
use App\Http\Controllers\Backend\LuggageDepositController;
use App\Http\Controllers\Backend\ModalController;
use App\Http\Controllers\Backend\NotificaController;
use App\Http\Controllers\Backend\OffertaSimController;
use App\Http\Controllers\Backend\EnergiaOffertaController;
use App\Http\Controllers\Backend\TelefoniaOffertaController;
use App\Http\Controllers\Backend\SimulaBollettaController;
use App\Http\Controllers\Backend\PasskeyController;
use App\Http\Controllers\Backend\PreferenzeTemaController;
use App\Http\Controllers\Backend\PaymentController;
use App\Http\Controllers\Backend\PdfToolsController;
use App\Http\Controllers\Backend\BooriteSsoController;
use App\Http\Controllers\Backend\PdfToolsWorkbenchController;
use App\Http\Controllers\Backend\PortafoglioController;
use App\Http\Controllers\Backend\ProdottoAssistenzaController;
use App\Http\Controllers\Backend\ProduzioneOperatoreController;
use App\Http\Controllers\Backend\ProfiloController;
use App\Http\Controllers\Backend\QuickContractController;
use App\Http\Controllers\Backend\RegistriController;
use App\Http\Controllers\Backend\BreakingNewsController;
use App\Http\Controllers\Backend\RicaricaBonificoPortafoglioController;
use App\Http\Controllers\Backend\RicaricaCartaIbanController;
use App\Http\Controllers\Backend\RicaricaPlafonController;
use App\Http\Controllers\Backend\RichiestaAssistenzaController;
use App\Http\Controllers\Backend\SeafileDocumentiController;
use App\Http\Controllers\Backend\AddressAutocompleteController;
use App\Http\Controllers\Backend\Select2;
use App\Http\Controllers\Backend\SendRequestController;
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

Route::group(['middleware' => ['auth', 'role_or_permission:admin|agente|supervisore|operatore', '2fa', 'demo.readonly']], function () {
    Route::get('/', [DashboardController::class, 'show']);
    Route::get('/lavoro', [DashboardController::class, 'show']);
    Route::get('/attiva-servizio', [AttivaServizioController::class, 'show'])->name('attiva-servizio');
    Route::post('/attiva-servizio', [AttivaServizioController::class, 'richiedi'])->name('attiva-servizio.richiedi');
    Route::get('produzione-operatore', [ProduzioneOperatoreController::class, 'index']);
    Route::get('produzione-operatore/{id}', [ProduzioneOperatoreController::class, 'show']);
    Route::get('provvigioni', [\App\Http\Controllers\Backend\ProvvigioniAffiliatoController::class, 'index'])
        ->name('backend.provvigioni.index');
    Route::get('mie-pratiche', [\App\Http\Controllers\Backend\MiePraticheController::class, 'index'])
        ->name('backend.mie-pratiche.index');
    Route::post('mie-pratiche/lookup', [\App\Http\Controllers\Backend\MiePraticheController::class, 'lookup'])
        ->name('backend.mie-pratiche.lookup');
    Route::get('hub-promozione', [\App\Http\Controllers\Backend\HubPromozioneController::class, 'index'])
        ->name('backend.hub-promozione.index');
    Route::get('referral-affiliato', [\App\Http\Controllers\Backend\ReferralAffiliatoController::class, 'index'])
        ->name('backend.referral.index');
    Route::get('admin/referral-bonus', [\App\Http\Controllers\Backend\AdminReferralController::class, 'index'])
        ->name('backend.admin.referral.index');
    Route::post('admin/referral-bonus/{id}/credit', [\App\Http\Controllers\Backend\AdminReferralController::class, 'credit'])
        ->name('backend.admin.referral.credit')->whereNumber('id');
    Route::post('admin/referral-bonus/{id}/cancel', [\App\Http\Controllers\Backend\AdminReferralController::class, 'cancel'])
        ->name('backend.admin.referral.cancel')->whereNumber('id');
    Route::post('preventivo-allaccio/stima', [\App\Http\Controllers\Backend\PreventivoAllaccioController::class, 'stima'])
        ->name('backend.preventivo-allaccio.stima');
    Route::get('preventivo-allaccio/pdf', [\App\Http\Controllers\Backend\PreventivoAllaccioController::class, 'pdf'])
        ->name('backend.preventivo-allaccio.pdf');
    Route::post('/dashboard/bulk-action', [DashboardController::class, 'bulkAction']);
    Route::post('/dashboard/mercati-energia/ack', [DashboardController::class, 'ackMercatiEnergiaSwal'])
        ->name('backend.dashboard.mercati-energia.ack');
    Route::get('/dashboard/workspace-search', [DashboardController::class, 'workspaceSearch'])
        ->name('dashboard.workspace-search');
    Route::middleware(['role_or_permission:admin|agente'])->group(function () {
        Route::get('/pdf-tools', [PdfToolsWorkbenchController::class, 'index'])->name('backend.pdf-tools');
        Route::post('/pdf-tools/run/{tool}', [PdfToolsWorkbenchController::class, 'run'])
            ->where('tool', 'merge|split|compress|rotate|remove-pages|ocr|extract-text')
            ->middleware('throttle:30,1')
            ->name('backend.pdf-tools.run');
        Route::get('/pdf-tools/enter', [PdfToolsController::class, 'enter'])->name('backend.pdf-tools.enter');
        Route::get('/pdf-tools/sso-token', [PdfToolsController::class, 'ssoToken'])->name('backend.pdf-tools.sso-token');
        Route::get('/pdf-tools/desktop-credentials', [PdfToolsController::class, 'desktopCredentials'])->name('backend.pdf-tools.desktop-credentials');

        // Formazione → Boorite Learning SSO (JWT)
        Route::get('/formazione', BooriteSsoController::class)
            ->middleware('throttle:30,1')
            ->name('backend.training.sso');

        // Documenti → Seafile (admin RW / agente RO via SSO)
        Route::get('/documenti/sso', [SeafileDocumentiController::class, 'sso'])->name('backend.documenti.sso');
        Route::get('/documenti/{id?}', [SeafileDocumentiController::class, 'index'])
            ->where('id', '[0-9]*')
            ->name('backend.documenti');
    });

    Route::middleware(['can:viewAny,App\Models\SendRequest'])->prefix('send')->group(function () {
        Route::get('/', [SendRequestController::class, 'dashboard']);
        Route::get('richieste', [SendRequestController::class, 'index']);
        Route::get('nuova', [SendRequestController::class, 'create']);
        Route::post('nuova', [SendRequestController::class, 'store']);
        Route::get('coda', [SendRequestController::class, 'queue']);
        Route::get('integrazioni', [SendRequestController::class, 'integrations']);
        Route::get('report', [SendRequestController::class, 'report']);
        Route::get('report.csv', [SendRequestController::class, 'exportCsv']);
        Route::get('impostazioni', [SendRequestController::class, 'settings']);
        Route::post('impostazioni', [SendRequestController::class, 'updateSettings']);
        Route::get('eventi-inviati', [SendRequestController::class, 'webhookEvents']);
        Route::post('eventi-inviati/{delivery}/retry', [SendRequestController::class, 'retryWebhook']);
        Route::post('allegato', [SendRequestController::class, 'uploadAllegato']);
        Route::delete('allegato', [SendRequestController::class, 'deleteAllegato']);
        Route::get('{send}/audit.csv', [SendRequestController::class, 'exportAudit']);
        Route::get('{send}', [SendRequestController::class, 'show']);
        Route::get('{send}/modifica', [SendRequestController::class, 'edit']);
        Route::post('{send}/modifica', [SendRequestController::class, 'update']);
        Route::post('{send}/invia', [SendRequestController::class, 'submit']);
        Route::post('{send}/prendi-in-carico', [SendRequestController::class, 'takeCharge']);
        Route::post('{send}/assegna-a-me', [SendRequestController::class, 'claim']);
        Route::post('{send}/lavorazione', [SendRequestController::class, 'startProcessing']);
        Route::post('{send}/integrazione', [SendRequestController::class, 'requestIntegration']);
        Route::post('{send}/completa', [SendRequestController::class, 'complete']);
        Route::post('{send}/rifiuta', [SendRequestController::class, 'reject']);
        Route::post('{send}/annulla', [SendRequestController::class, 'cancel']);
        Route::post('{send}/consegna', [SendRequestController::class, 'deliver']);
        Route::post('{send}/elimina', [SendRequestController::class, 'destroy']);
        Route::post('{send}/riapri', [SendRequestController::class, 'reopen']);
        Route::post('{send}/riassegna', [SendRequestController::class, 'reassign']);
        Route::get('{send}/ricevuta-consegna.pdf', [SendRequestController::class, 'deliveryReceiptPdf']);
        Route::post('{send}/nota', [SendRequestController::class, 'addNote']);
        Route::post('{send}/documento', [SendRequestController::class, 'uploadDocument']);
        Route::post('{send}/allegato-cliente', [SendRequestController::class, 'uploadClientDocument']);
        Route::get('{send}/allegato-cliente', [SendRequestController::class, 'downloadClientDocument']);
        Route::get('{send}/documento/{document}', [SendRequestController::class, 'downloadDocument']);
        Route::post('{send}/documento/{document}/elimina', [SendRequestController::class, 'destroyDocument']);
    });

    // Alias per action([CartellaFilesController::class,'index']) legacy (dashboard/view) → redirect Seafile
    Route::get('/documenti-index/{id?}', [CartellaFilesController::class, 'index'])
        ->where('id', '[0-9]*')
        ->name('documenti.legacy-index');

    // Carica da telefono (Stirling mobile-scanner) — bridge autenticato per dropzoneUx
    Route::post('/allegati-mobile-scan/session', [AllegatoMobileScanController::class, 'createSession'])
        ->name('allegati-mobile-scan.session');
    Route::get('/allegati-mobile-scan/session/{sessionId}', [AllegatoMobileScanController::class, 'status'])
        ->where('sessionId', '[a-fA-F0-9-]{36}')
        ->name('allegati-mobile-scan.status');
    Route::get('/allegati-mobile-scan/session/{sessionId}/file/{fileId}', [AllegatoMobileScanController::class, 'download'])
        ->where('sessionId', '[a-fA-F0-9-]{36}')
        ->name('allegati-mobile-scan.download');
    Route::delete('/allegati-mobile-scan/session/{sessionId}', [AllegatoMobileScanController::class, 'destroy'])
        ->where('sessionId', '[a-fA-F0-9-]{36}')
        ->name('allegati-mobile-scan.destroy');

    Route::prefix('quick-contract/{module}')
        ->where(['module' => 'telefonia|energia'])
        ->group(function () {
            Route::get('nuovo', [QuickContractController::class, 'choice'])->name('quick-contract.choice');
            Route::get('destinazione', [QuickContractController::class, 'destinationForm'])->name('quick-contract.destination');
            Route::post('destinazione', [QuickContractController::class, 'storeDestination'])->name('quick-contract.destination.store');
            Route::get('upload', [QuickContractController::class, 'uploadForm'])->name('quick-contract.upload-form');
            Route::post('upload', [QuickContractController::class, 'upload'])->name('quick-contract.upload');
            Route::get('{extraction}/status', [QuickContractController::class, 'status'])->name('quick-contract.status');
            Route::post('{extraction}/operazione', [QuickContractController::class, 'storeOperazione'])->name('quick-contract.operazione');
            Route::post('{extraction}/customer-patch', [QuickContractController::class, 'storeCustomerPatch'])->name('quick-contract.customer-patch');
            Route::post('{extraction}/swal-ack', [QuickContractController::class, 'ackOperazioneSwal'])->name('quick-contract.swal-ack');
            Route::get('{extraction}/retry', [QuickContractController::class, 'retry'])->name('quick-contract.retry');
            Route::get('{extraction}/preview', [QuickContractController::class, 'preview'])->name('quick-contract.preview');
            Route::get('{extraction}/completa', [QuickContractController::class, 'completa'])->name('quick-contract.completa');
            Route::get('{extraction}/confronta', [QuickContractController::class, 'confrontaForm'])->name('quick-contract.confronta');
            Route::post('{extraction}/confronta', [QuickContractController::class, 'storeConfronta'])->name('quick-contract.confronta.store');
            Route::get('{extraction}/cliente-check', [QuickContractController::class, 'clienteCheck'])->name('quick-contract.cliente-check');
            Route::post('{extraction}/cliente-check', [QuickContractController::class, 'clienteCheckRefresh'])->name('quick-contract.cliente-check.refresh');
            Route::post('{extraction}/confirm', [QuickContractController::class, 'confirm'])->name('quick-contract.confirm');
            Route::get('{extraction}', [QuickContractController::class, 'review'])->name('quick-contract.review');
        });

    // Contratto
    Route::post('/allegato-contratto', [ContrattoTelefoniaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto', [ContrattoTelefoniaController::class, 'deleteAllegato']);
    Route::get('contratto/{contrattoId}/allegato/{allegatoId}', [ContrattoTelefoniaController::class, 'downloadAllegato']);
    Route::resource('contratto', ContrattoTelefoniaController::class);
    Route::post('/contratto/{id}/riapri', [ContrattoTelefoniaController::class, 'riapri'])->name('contratto.riapri');
    Route::post('/contratto/{id}/conferma-bozza', [ContrattoTelefoniaController::class, 'confermaBozza'])->name('contratto.conferma-bozza');
    Route::post('/contratto/{id}/prendi-in-carico-segnalazione', [ContrattoTelefoniaController::class, 'prendiInCaricoSegnalazione'])
        ->name('contratto.prendi-in-carico-segnalazione');
    Route::post('/contratto/{id}/azione/{azione}', [ContrattoTelefoniaController::class, 'azioni']);
    Route::post('/contratto/verifica-cf-rischio', [ContrattoTelefoniaController::class, 'verificaCodiceFiscaleRischio']);
    Route::post('/contratto/verifica-copertura', [ContrattoTelefoniaController::class, 'verificaCopertura'])
        ->middleware('throttle:30,1');
    Route::post('/contratto/richiedi-copertura', [ContrattoTelefoniaController::class, 'richiediCopertura'])
        ->middleware('throttle:10,1');
    Route::get('/autocomplete/vie', [AddressAutocompleteController::class, 'streets'])
        ->middleware('throttle:60,1')
        ->name('autocomplete.vie');
    Route::get('/autocomplete/civici', [AddressAutocompleteController::class, 'houseNumbers'])
        ->middleware('throttle:60,1')
        ->name('autocomplete.civici');
    Route::post('/contratto/{id}/segnala-rischio', [ContrattoTelefoniaController::class, 'segnalaRischio']);
    Route::get('pda-contratto/{id}', [ContrattoTelefoniaController::class, 'pda']);

    // ContrattoEnergia
    Route::post('/allegato-contratto-energia', [ContrattoEnergiaController::class, 'uploadAllegato']);
    Route::delete('/allegato-contratto-energia', [ContrattoEnergiaController::class, 'deleteAllegato']);
    Route::get('contratto-energia/{contrattoId}/allegato/{allegatoId}', [ContrattoEnergiaController::class, 'downloadAllegato']);
    Route::post('/contratto-energia/sentra-nuova-attivazione/generate', [ContrattoEnergiaController::class, 'sentraNuovaAttivazioneGenerate'])
        ->name('contratto-energia.sentra-nuova.generate');
    Route::get('/contratto-energia/sentra-nuova-attivazione/download/{file}', [ContrattoEnergiaController::class, 'sentraNuovaAttivazioneDownload'])
        ->name('contratto-energia.sentra-nuova.download');
    Route::get('/contratto-energia/sentra-nuova-attivazione/cte', [ContrattoEnergiaController::class, 'sentraNuovaAttivazioneCte'])
        ->name('contratto-energia.sentra-nuova.cte');
    Route::get('pda-contratto-energia/{id}', [ContrattoEnergiaController::class, 'pda']);
    Route::get('/contratto-energia/create/{servizio?}', [ContrattoEnergiaController::class, 'create']);

    Route::resource('contratto-energia', ContrattoEnergiaController::class)->except(['create']);
    Route::post('/contratto-energia/verifica-cf-rischio', [ContrattoEnergiaController::class, 'verificaCodiceFiscaleRischio']);
    Route::post('/contratto-energia/{id}/riapri', [ContrattoEnergiaController::class, 'riapri'])->name('contratto-energia.riapri');
    Route::post('/contratto-energia/{id}/conferma-bozza', [ContrattoEnergiaController::class, 'confermaBozza'])->name('contratto-energia.conferma-bozza');
    Route::post('/contratto-energia/{id}/scadenza', [ContrattoEnergiaController::class, 'aggiornaScadenza'])->name('contratto-energia.scadenza');
    Route::post('/contratto-energia/{id}/prendi-in-carico-segnalazione', [ContrattoEnergiaController::class, 'prendiInCaricoSegnalazione'])
        ->name('contratto-energia.prendi-in-carico-segnalazione');
    Route::post('/contratto-energia/{id}/segnala-rischio', [ContrattoEnergiaController::class, 'segnalaRischio']);
    Route::get('/contratto-energia/{id}/duplica-anagrafica', [ContrattoEnergiaController::class, 'duplicaAnagrafica']);
    Route::post('/contratto-energia/{id}/switch-categoria', [ContrattoEnergiaController::class, 'switchCategoria']);
    Route::post('/contratto-energia/{id}/segnala-chat', [ContrattoEnergiaController::class, 'segnalaChat']);
    Route::post('/contratto-energia/{id}/apri-chat', [ContrattoEnergiaController::class, 'apriChat']);
    // Route::post('/contratto-energia/{id}/azione/{azione}', [\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'azioni']);

    // Firma digitale: relay link/OTP admin ↔ affiliato (SweetAlert)
    Route::prefix('contract-signing')->group(function () {
        Route::get('/pending', [ContractSigningController::class, 'pending']);
        Route::get('/latest', [ContractSigningController::class, 'latest']);
        Route::get('/reopen', [ContractSigningController::class, 'reopen']);
        Route::get('/{relay}', [ContractSigningController::class, 'show'])->whereNumber('relay');
        Route::post('/request-link', [ContractSigningController::class, 'requestLink']);
        Route::post('/request-otp', [ContractSigningController::class, 'requestOtp']);
        Route::post('/{relay}/answer', [ContractSigningController::class, 'answer'])->whereNumber('relay');
        Route::post('/{relay}/complete', [ContractSigningController::class, 'complete'])->whereNumber('relay');
    });

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

    // Documenti legacy (share/download/versioni su disk locale — UI sostituita da Seafile)
    // Route::get('documenti/{id?}', ...) → SeafileDocumentiController sopra

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
    Route::post('fattura-proforma/{id}/emetti', [FatturaProformaController::class, 'emetti']);
    Route::post('fattura-proforma/{id}/invia-email', [FatturaProformaController::class, 'inviaEmail']);
    Route::post('fattura-proforma/{id}/segna-pagata', [FatturaProformaController::class, 'segnaPagata']);
    Route::post('fattura-proforma/{id}/rigenera', [FatturaProformaController::class, 'rigenera']);
    Route::patch('fattura-proforma/{id}/intestazione', [FatturaProformaController::class, 'updateIntestazione']);
    Route::resource('fattura-proforma', FatturaProformaController::class)->except(['create', 'store', 'edit', 'update']);

    // Liquidazione provvigioni mese (admin)
    Route::get('liquidazione-provvigioni', [LiquidazioneProvvigioniController::class, 'index']);
    Route::get('liquidazione-provvigioni/report', [LiquidazioneProvvigioniController::class, 'report']);
    Route::get('liquidazione-provvigioni/export', [LiquidazioneProvvigioniController::class, 'exportExcel']);
    Route::post('liquidazione-provvigioni/genera-tutte', [LiquidazioneProvvigioniController::class, 'generaTutte']);
    Route::post('liquidazione-provvigioni/genera/{produzioneId}', [LiquidazioneProvvigioniController::class, 'generaSingola']);
    Route::post('liquidazione-provvigioni/segna-pagate', [LiquidazioneProvvigioniController::class, 'segnaPagateBatch']);

    // Fatturazione InvoiceShelf (hub Metronic)
    Route::get('fatturazione', [BillingDocumentController::class, 'index']);
    Route::get('fatturazione/invoiceshelf', [BillingDocumentController::class, 'invoiceshelfIndex']);
    Route::get('fatturazione/invoiceshelf/enter', [BillingDocumentController::class, 'enterInvoiceShelf']);
    Route::get('fatturazione/invoiceshelf/{isId}/xml', [BillingDocumentController::class, 'exportInvoiceShelfXml'])->whereNumber('isId');
    Route::get('fatturazione/{id}', [BillingDocumentController::class, 'show'])->whereNumber('id');
    Route::get('fatturazione/{id}/pdf', [BillingDocumentController::class, 'pdf'])->whereNumber('id');
    Route::get('fatturazione/{id}/xml', [BillingDocumentController::class, 'exportXml'])->whereNumber('id');
    Route::post('fatturazione/{id}/emetti', [BillingDocumentController::class, 'emetti'])->whereNumber('id');
    Route::post('fatturazione/{id}/segna-pagata', [BillingDocumentController::class, 'segnaPagata'])->whereNumber('id');
    Route::post('fatturazione/{id}/convert-to-invoice', [BillingDocumentController::class, 'convertToInvoice'])->whereNumber('id');
    Route::get('proforma-caf-patronato', [BillingFornitoreController::class, 'caf']);
    Route::get('proforma-caf-patronato/preview', [BillingFornitoreController::class, 'previewCaf']);
    Route::post('proforma-caf-patronato/genera', [BillingFornitoreController::class, 'generaCaf']);
    Route::get('proforma-send', [BillingFornitoreController::class, 'send']);
    Route::get('proforma-send/preview', [BillingFornitoreController::class, 'previewSend']);
    Route::post('proforma-send/genera', [BillingFornitoreController::class, 'generaSend']);

    // Portafoglio
    Route::post('/portafoglio/richiedi-spostamento', [PortafoglioController::class, 'richiediSpostamento']);
    Route::post('/portafoglio/sposta', [PortafoglioController::class, 'sposta']);
    Route::post('/portafoglio/storna', [PortafoglioController::class, 'storna']);
    Route::post('/portafoglio/richieste-spostamento/{id}/applica', [PortafoglioController::class, 'applicaRichiestaSpostamento']);
    Route::resource('/portafoglio', PortafoglioController::class);

    Route::get('/ricariche-bonifico', [RicaricaBonificoPortafoglioController::class, 'index']);
    Route::post('/ricariche-bonifico', [RicaricaBonificoPortafoglioController::class, 'store']);
    Route::get('/ricariche-bonifico/{id}/download', [RicaricaBonificoPortafoglioController::class, 'download'])->whereNumber('id');
    Route::post('/ricariche-bonifico/{id}/approve', [RicaricaBonificoPortafoglioController::class, 'approve'])->whereNumber('id');
    Route::post('/ricariche-bonifico/{id}/reject', [RicaricaBonificoPortafoglioController::class, 'reject'])->whereNumber('id');
    Route::get('/ricariche-bonifico/pending-json', [RicaricaBonificoPortafoglioController::class, 'pendingJson']);
    Route::post('/ricariche-bonifico/ack-swal', [RicaricaBonificoPortafoglioController::class, 'ackSwal']);

    Route::post('/breaking-news/{id}/toggle', [BreakingNewsController::class, 'toggle'])->whereNumber('id');
    Route::resource('/breaking-news', BreakingNewsController::class)->except(['show']);

    // Ebike B2B
    Route::resource('/ebike/prodotti', EbikeProdottoController::class)->except(['show']);
    Route::post('/ebike/ordini/{id}/carica-pagamento', [EbikeOrdineController::class, 'caricaPagamento']);
    Route::post('/ebike/ordini/{id}/conferma-pagamento', [EbikeOrdineController::class, 'confermaPagamento']);
    Route::post('/ebike/ordini/{id}/imposta-tracking', [EbikeOrdineController::class, 'impostaTracking']);
    Route::post('/ebike/ordini/{id}/segna-consegnato', [EbikeOrdineController::class, 'segnaConsegnato']);
    Route::post('/ebike/ordini/{id}/annulla', [EbikeOrdineController::class, 'annulla']);
    Route::resource('/ebike/ordini', EbikeOrdineController::class)->only(['index', 'create', 'store', 'show']);

    Route::post('/pagamento/droppay/start', [PaymentController::class, 'startDropPay']);
    Route::get('/pagamento/droppay/{id}/checkout', [PaymentController::class, 'checkoutDropPay'])->whereNumber('id');
    Route::match(['get', 'post'], '/pagamento/droppay/complete', [PaymentController::class, 'completeDropPay']);
    Route::get('/pagamento-success', [PaymentController::class, 'pagamentoSuccess']);

    // Tickets
    Route::resource('/ticket', TicketsController::class, ['as' => 'ticket-admin']);

    // Chat interna (Metronic / chat_*)
    Route::get('/chat', [ChatController::class, 'index'])->name('internal-chat');
    Route::get('/chat-interna', [ChatController::class, 'index']);
    Route::get('/chat-nuova/{any?}', function (?string $any = null) {
        $threadId = ctype_digit((string) $any) ? (int) $any : (request()->integer('thread') ?: null);

        return redirect()->to(\App\Support\InternalChat::url($threadId));
    })->where('any', '.*');

    Route::post('/chat-interna/thread', [ChatController::class, 'storeThread']);
    Route::get('/chat-interna/poll', [ChatController::class, 'poll']);
    Route::get('/chat-interna/poll-global', [ChatController::class, 'pollGlobalNotifications']);
    Route::get('/chat-interna/search', [ChatController::class, 'search']);
    Route::get('/chat-interna/push/vapid-public-key', [ChatController::class, 'pushVapidPublicKey']);
    Route::post('/chat-interna/push/subscribe', [ChatController::class, 'subscribePush']);
    Route::post('/chat-interna/push/unsubscribe', [ChatController::class, 'unsubscribePush']);
    Route::get('/chat-interna/file/{id}', [ChatController::class, 'attachment'])->whereNumber('id');
    // Alias legacy: evita 404 da cache CDN sulle vecchie URL /attachment/{id}
    Route::get('/chat-interna/attachment/{id}', [ChatController::class, 'attachment'])->whereNumber('id');
    Route::get('/chat-interna/{thread}/messages', [ChatController::class, 'messages']);
    Route::post('/chat-interna/{thread}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/chat-interna/{thread}/stream', [ChatController::class, 'stream']);
    Route::post('/chat-interna/{thread}/forward', [ChatController::class, 'forwardMessages']);
    Route::post('/chat-interna/mention/resolve', [ChatController::class, 'resolveMention']);
    Route::post('/chat-interna/{thread}/typing', [ChatController::class, 'typing']);
    Route::post('/chat-interna/{thread}/close', [ChatController::class, 'closeThread']);
    Route::post('/chat-interna/{thread}/archive', [ChatController::class, 'archiveThread']);
    Route::post('/chat-interna/{thread}/mute', [ChatController::class, 'toggleThreadMute']);
    Route::post('/chat-interna/message/{message}/reaction', [ChatController::class, 'toggleReaction']);
    Route::post('/chat-interna/message/{message}/pin', [ChatController::class, 'togglePin']);
    Route::post('/chat-interna/message/{message}/favorite', [ChatController::class, 'toggleFavorite']);
    Route::get('/chat-interna/message/{message}/history', [ChatController::class, 'messageHistory']);
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
    Route::post('preferenze/tema', [PreferenzeTemaController::class, 'update'])->name('backend.preferenze.tema');

    Route::middleware(['role_or_permission:agente'])->group(function () {
        Route::get('profilo/canoni-servizi', [CanoneServiziController::class, 'index'])->name('backend.canoni.index');
        Route::post('profilo/canoni-servizi/disdici', [CanoneServiziController::class, 'disdici'])->name('backend.canoni.disdici');
        Route::get('billing/clawback', [ProvvigioneClawbackController::class, 'index'])->name('backend.billing.clawback');
        Route::get('billing/clawback/export', [ProvvigioneClawbackController::class, 'exportCsv'])->name('backend.billing.clawback.export');
        Route::post('billing/clawback/ack', [ProvvigioneClawbackController::class, 'ack'])->name('backend.billing.clawback.ack');
        Route::post('billing/clawback/ack-form', [ProvvigioneClawbackController::class, 'ackForm'])->name('backend.billing.clawback.ack-form');
    });

    Route::post('chat-interna/allegato/{id}/segnala', [ContentReportController::class, 'reportAttachment'])
        ->whereNumber('id')
        ->name('backend.content-report.attachment');

    Route::middleware(['role_or_permission:admin|agente|supervisore|operatore'])->group(function () {
        Route::get('profilo/passkey', [PasskeyController::class, 'index'])->name('backend.profilo.passkey');
        Route::post('profilo/passkey/options', [PasskeyController::class, 'options'])->name('backend.profilo.passkey.options');
        Route::post('profilo/passkey/register', [PasskeyController::class, 'register'])->name('backend.profilo.passkey.register');
        Route::delete('profilo/passkey/{id}', [PasskeyController::class, 'destroy'])->name('backend.profilo.passkey.destroy');
    });

    // Contatti Brt
    Route::resource('contatto-brt', ContattoBrtController::class)->except(['show']);

    // Simula bolletta: affiliati con servizio energia + admin (gate fine nel controller)
    Route::prefix('simula-bolletta')->name('simula-bolletta.')->group(function () {
        Route::get('/', [SimulaBollettaController::class, 'index'])->name('index');
        Route::get('/manual', [SimulaBollettaController::class, 'manualForm'])->name('manual');
        Route::post('/calcola', [SimulaBollettaController::class, 'calcola'])->name('calcola');
        Route::get('/risultato', [SimulaBollettaController::class, 'risultato'])->name('risultato');
        Route::get('/scheda/{offerta}', [SimulaBollettaController::class, 'scheda'])->name('scheda');
        Route::post('/upload', [SimulaBollettaController::class, 'upload'])->name('upload');
        Route::get('/review/{extraction}', [SimulaBollettaController::class, 'review'])->name('review');
        Route::get('/review/{extraction}/status', [SimulaBollettaController::class, 'reviewStatus'])->name('review.status');
    });

});

Route::group(['middleware' => ['auth', '2fa', 'role_or_permission:admin']], function () {
    Route::get('segnalazioni-contenuto', [ContentReportController::class, 'adminIndex'])->name('backend.content-report.index');
    Route::post('segnalazioni-contenuto/{id}/stato', [ContentReportController::class, 'adminUpdateStatus'])
        ->whereNumber('id')
        ->name('backend.content-report.status');

    Route::post('/richieste-servizi/{serviceRequest}', [AttivaServizioController::class, 'aggiorna'])
        ->name('admin.richieste-servizi.aggiorna');

    // Cliente
    Route::get('cliente/{id}/tab/{tab}', [ClienteController::class, 'tab']);
    Route::resource('cliente', ClienteController::class);
    // Impersonazione: admin già autenticato + 2FA; niente step-up password (né admin né affiliato).
    Route::get('/cliente/{id}/impersona', [ClienteController::class, 'impersona']);
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
        ->whereIn('cosa', ['login', 'email', 'backup-db', 'elenco_licenze', 'info-sito', 'errori', 'upload', 'modifiche', 'security'])
        ->name('registro.index');
    Route::post('registro/backup-db/esegui', [RegistriController::class, 'eseguiBackup'])->name('registro.backup.esegui');
    Route::get('registro/backup-db/scarica', [RegistriController::class, 'scaricaBackup'])->name('registro.backup.scarica');

    // plafond
    Route::get('/carica-plafond', [RicaricaPlafonController::class, 'show']);
    Route::post('/carica-plafond', [RicaricaPlafonController::class, 'store']);

    // Agente
    Route::get('/agente-tab/{id}/tab/{tab}', [AgenteController::class, 'tab']);
    Route::post('/agente/bulk/{azione}', [AgenteController::class, 'bulkAzioni'])
        ->where('azione', 'sospendi|riattiva|invita_rientro|export');
    Route::get('/agente/preview-email-rientro', [AgenteController::class, 'previewEmailRientro'])
        ->name('agente.preview-email-rientro');
    // whereNumber: evita che "preview-email-rientro" venga catturato da agente/{agente}
    Route::resource('/agente', AgenteController::class)->whereNumber('agente');
    Route::post('/agente/{id}/send-api-key', [AgenteController::class, 'regenerateSendApiKey'])
        ->middleware('password.confirm');
    Route::post('/agente/{id}/send-api-key/revoke', [AgenteController::class, 'revokeSendApiKey'])
        ->middleware('password.confirm');
    // Impersonazione: admin già autenticato + 2FA; niente step-up password (né admin né affiliato).
    Route::get('/agente/{id}/impersona', [AgenteController::class, 'impersona']);
    Route::post('/agente/{id}/azione/{azione}', [AgenteController::class, 'azioni']);

    Route::get('agente/{agente}/tipo-contratto/{tipocontratto}', [FasciaTipoContrattoController::class, 'edit']);
    Route::patch('agente/{agente}/tipo-contratto/{tipocontratto}', [FasciaTipoContrattoController::class, 'update']);

    // Route::resource('/operatore', \App\Http\Controllers\Backend\OperatoreController::class)->except(['show']);

    Route::resource('/notifica', NotificaController::class)->except(['show']);
    Route::get('/centro-notifiche', [CentroNotificheController::class, 'index'])->name('centro-notifiche');
    Route::post('/centro-notifiche/{id}/letta', [CentroNotificheController::class, 'markRead'])->name('centro-notifiche.mark-read');
    Route::post('/centro-notifiche/lette-tutte', [CentroNotificheController::class, 'markAllRead'])->name('centro-notifiche.mark-all');

    Route::get('produzione-operatore/{id}/preview-proforma', [ProduzioneOperatoreController::class, 'previewProforma']);
    Route::post('produzione-operatore/{id}/crea-proforma', [ProduzioneOperatoreController::class, 'creaProforma']);
    Route::post('produzione-operatore/{id}/ricalcola', [ProduzioneOperatoreController::class, 'ricalcola']);

    Route::resource('/tipo-contratto', TipoContrattoController::class)->except(['show']);
    Route::resource('/tipo-caf-patronato', TipoCafPatronatoController::class)->except(['show']);
    Route::resource('/tipo-esito', EsitoTelefoniaController::class)->except(['show']);
    Route::resource('/tipo-visura', TipoVisuraController::class)->except(['show']);
    Route::resource('/offerta-sim', OffertaSimController::class)->except(['show']);
    Route::post('/offerta-energia/sync', [EnergiaOffertaController::class, 'syncNow'])->name('offerta-energia.sync');
    Route::resource('/offerta-energia', EnergiaOffertaController::class)->except(['show']);
    Route::post('/offerta-telefonia/sync', [TelefoniaOffertaController::class, 'syncNow'])->name('offerta-telefonia.sync');
    Route::resource('/offerta-telefonia', TelefoniaOffertaController::class)->except(['show']);

    // Gestori
    Route::resource('/gestore', GestoreController::class)->except(['show']);
    Route::resource('/gestore-attivazione', GestoreAttivazioniController::class)->except(['show']);
    Route::resource('/gestore-contratto-energia', GestoreContrattoEnergiaController::class)->except(['show']);

    // Esiti
    Route::resource('esito-caf-patronato', EsitoCafPatronatoController::class)->except(['show']);
    Route::resource('esito-contratto-energia', EsitoContrattoEnergiaController::class)->except(['show']);
    Route::resource('esito-visura', EsitoVisuraController::class)->except(['show']);

    // Listini + Gare
    Route::resource('listino', ListinoController::class);
    Route::resource('gara', GaraController::class)->except(['show']);
    Route::resource('brt-listino', ListinoBrtController::class);
    Route::resource('brt-listino-europa', ListinoBrtEuropaController::class);
    Route::resource('inpost-listino', ListinoInpostController::class);
    Route::get('inpost-account', [InpostConsoleController::class, 'account']);
    Route::get('inpost-deposits', [InpostConsoleController::class, 'deposits']);
    Route::post('inpost-deposits', [InpostConsoleController::class, 'storeDeposit']);

    Route::prefix('deposito-bagagli')->group(function () {
        Route::get('dashboard', [LuggageDepositController::class, 'dashboard']);
        Route::get('pipeline', [LuggageDepositController::class, 'pipeline']);
        Route::get('check-in', [LuggageDepositController::class, 'checkInPage']);
        Route::get('check-out', [LuggageDepositController::class, 'checkOutPage']);
        Route::get('settings', [LuggageDepositController::class, 'settings']);
        Route::post('settings', [LuggageDepositController::class, 'updateSettings']);
        Route::get('mia-postazione', [LuggageDepositController::class, 'stationSettings']);
        Route::post('mia-postazione', [LuggageDepositController::class, 'updateStationSettings']);
        Route::post('mia-postazione/richiedi-api', [LuggageDepositController::class, 'requestStationApi']);
        Route::get('postazioni', [LuggageDepositController::class, 'stationsIndex']);
        Route::post('postazioni/{id}/enable-api', [LuggageDepositController::class, 'enableStationApi']);
        Route::post('postazioni/{id}/regenerate-api', [LuggageDepositController::class, 'regenerateStationApi']);
        Route::post('postazioni/{id}/disable-api', [LuggageDepositController::class, 'disableStationApi']);
        Route::get('report', [LuggageDepositController::class, 'report']);
        Route::get('export/csv', [LuggageDepositController::class, 'exportCsv']);
        Route::get('create', [LuggageDepositController::class, 'create']);
        Route::post('/', [LuggageDepositController::class, 'store']);
        Route::get('/', [LuggageDepositController::class, 'index']);
        Route::post('{id}/action', [LuggageDepositController::class, 'action']);
        Route::delete('{id}', [LuggageDepositController::class, 'destroy']);
        Route::get('{id}/pdf/receipt', [LuggageDepositController::class, 'pdfReceipt']);
        Route::get('{id}/pdf/tags', [LuggageDepositController::class, 'pdfTags']);
        Route::get('{id}/pdf/agreement', [LuggageDepositController::class, 'pdfAgreement']);
        Route::get('{id}', [LuggageDepositController::class, 'show']);
    });

    Route::prefix('locker-point')->group(function () {
        Route::get('dashboard', [LockerPackageController::class, 'dashboard']);
        Route::get('pipeline', [LockerPackageController::class, 'pipeline']);
        Route::get('settings', [LockerPackageController::class, 'settings']);
        Route::post('settings', [LockerPackageController::class, 'updateSettings']);
        Route::get('mia-postazione', [LockerPackageController::class, 'stationSettings']);
        Route::post('mia-postazione', [LockerPackageController::class, 'updateStationSettings']);
        Route::post('mia-postazione/richiedi-api', [LockerPackageController::class, 'requestStationApi']);
        Route::get('postazioni', [LockerPackageController::class, 'stationsIndex']);
        Route::post('postazioni/{id}/enable-api', [LockerPackageController::class, 'enableStationApi']);
        Route::post('postazioni/{id}/regenerate-api', [LockerPackageController::class, 'regenerateStationApi']);
        Route::post('postazioni/{id}/disable-api', [LockerPackageController::class, 'disableStationApi']);
        Route::get('create', [LockerPackageController::class, 'create']);
        Route::get('accetta', [LockerPackageController::class, 'intakePage']);
        Route::post('/', [LockerPackageController::class, 'store']);
        Route::get('/', [LockerPackageController::class, 'index']);
        Route::get('{id}/accetta', [LockerPackageController::class, 'intake']);
        Route::post('{id}/accetta', [LockerPackageController::class, 'storeIntake']);
        Route::post('{id}/action', [LockerPackageController::class, 'action']);
        Route::delete('{id}', [LockerPackageController::class, 'destroy']);
        Route::get('{id}/pdf/label', [LockerPackageController::class, 'pdfLabel']);
        Route::get('{id}/foto', [LockerPackageController::class, 'photo'])->name('locker.photo');
        Route::get('{id}', [LockerPackageController::class, 'show']);
    });

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
