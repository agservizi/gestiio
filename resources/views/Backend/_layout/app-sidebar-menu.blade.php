@if(Auth::user()->hasAnyPermission(['admin','agente','supervisore']))
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
             data-kt-scroll="true" data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
             data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu"
                 data-kt-menu="true" data-kt-menu-expand="false">
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-abstract-4 fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Dashboards</span>
                    </a>
                </div>

                @if(Auth::user()->hasAnyPermission(['admin']))
                    {{-- Admin: voci operative raggruppate (icone = keenicons duotone presenti nel progetto) --}}
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-menu fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Servizi</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Contratti Telefonia</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Contratti Energia</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Caf / Patronato</span>
                                </a>
                            </div>
                            @can('viewAny', \App\Models\SendRequest::class)
                                <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                                    <span class="menu-link">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">SEND</span>
                                        <span class="menu-arrow"></span>
                                    </span>
                                    <div class="menu-sub menu-sub-accordion">
                                        <div class="menu-item">
                                            <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'dashboard']) }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Dashboard</span>
                                            </a>
                                        </div>
                                        @can('send.requests.process')
                                            <div class="menu-item">
                                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'queue']) }}">
                                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                    <span class="menu-title">Coda</span>
                                                </a>
                                            </div>
                                        @endcan
                                        <div class="menu-item">
                                            <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'integrations']) }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Integrazioni</span>
                                            </a>
                                        </div>
                                        @can('viewReports', \App\Models\SendRequest::class)
                                            <div class="menu-item">
                                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'report']) }}">
                                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                    <span class="menu-title">Report</span>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('manageSettings', \App\Models\SendRequest::class)
                                            <div class="menu-item">
                                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'settings']) }}">
                                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                    <span class="menu-title">Impostazioni</span>
                                                </a>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            @endcan
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Visure</span>
                                </a>
                            </div>
                            @can('viewAny', \App\Models\LuggageDeposit::class)
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ url('/backend/deposito-bagagli/dashboard') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Deposito Bagagli</span>
                                    </a>
                                </div>
                                @can('manageStationSettings', \App\Models\LuggageDeposit::class)
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'stationSettings']) }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Mia postazione bagagli</span>
                                        </a>
                                    </div>
                                @endcan
                            @endcan
                            @can('viewAny', \App\Models\LockerPackage::class)
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ url('/backend/locker-point/dashboard') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Locker Point</span>
                                    </a>
                                </div>
                                @can('manageStationSettings', \App\Models\LockerPackage::class)
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\LockerPackageController::class, 'stationSettings']) }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Mia postazione locker</span>
                                        </a>
                                    </div>
                                @endcan
                            @endcan
                        </div>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-route fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Logistica</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Spedizione BRT</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{ url('/backend/spedizione-inpost') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Spedizione InPost</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-people fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title">Anagrafiche</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ClienteController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Clienti</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\AgenteController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Agenti</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ClienteAssistenzaController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Clienti assistenza</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Richieste assistenza</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-archive fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <span class="menu-title">Documenti e pagamenti</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            @canany(['admin', 'agente'])
                            <div class="menu-item">
                                <a class="menu-link" href="{{ route('backend.documenti') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Documenti</span>
                                </a>
                            </div>
                            @endcanany
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\RicaricaCartaIbanController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">IBAN Ricariche Carte</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Ricarica plafond agenti</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\BillingFornitoreController::class,'caf'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Proforma CAF/Patronato</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\BillingFornitoreController::class,'send'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Proforma SEND</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-gift fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Ebike</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\EbikeProdottoController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Catalogo ebike</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'index'])}}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Ordini ebike</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Agente / supervisore: lista flat --}}
                    @can('servizio_contratti_telefonia')
                    <div class="menu-item">
                        <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-router fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Contratti Telefonia</span>
                        </a>
                    </div>
                    @endcan
                    @can('servizio_contratti_energia')
                    <div class="menu-item">
                        <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-electricity fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Contratti Energia</span>
                        </a>
                    </div>
                    @endcan
                    @can('servizio_caf_patronato')
                    <div class="menu-item">
                        <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-profile-user fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Caf / Patronato</span>
                        </a>
                    </div>
                    @endcan
                    @can('servizio_visure')
                    <div class="menu-item">
                        <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-magnifier fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Visure</span>
                        </a>
                    </div>
                    @endcan
                    @can('viewAny', \App\Models\LuggageDeposit::class)
                        <div class="menu-item">
                            <a class="menu-link" href="{{ url('/backend/deposito-bagagli/dashboard') }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-safe-home fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Deposito Bagagli</span>
                            </a>
                        </div>
                        @can('manageStationSettings', \App\Models\LuggageDeposit::class)
                            <div class="menu-item">
                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'stationSettings']) }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-geolocation fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Mia postazione bagagli</span>
                                </a>
                            </div>
                        @endcan
                    @endcan
                    @include('Backend._layout.sidebar-send-menu-item')
                    @can('viewAny', \App\Models\LockerPackage::class)
                        <div class="menu-item">
                            <a class="menu-link" href="{{ url('/backend/locker-point/dashboard') }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-archive fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Locker Point</span>
                            </a>
                        </div>
                        @can('manageStationSettings', \App\Models\LockerPackage::class)
                            <div class="menu-item">
                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\LockerPackageController::class, 'stationSettings']) }}">
                                    <span class="menu-icon">
                                        <i class="ki-duotone ki-geolocation fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="menu-title">Mia postazione locker</span>
                                </a>
                            </div>
                        @endcan
                    @endcan
                    @canany(['admin', 'agente'])
                    <div class="menu-item">
                        <a class="menu-link" href="{{ route('backend.documenti') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-archive fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Documenti</span>
                        </a>
                    </div>
                    @endcanany
                    @canany(['admin', 'agente'])
                    <div class="menu-item">
                        <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\RicaricaCartaIbanController::class,'index'])}}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-receipt-square fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">IBAN Ricariche Carte</span>
                        </a>
                    </div>
                    @endcanany
                    @can('agente')
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-diamonds fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Portafoglio</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion">
                                <div class="menu-item">
                                    <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'index'])}}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Movimenti</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Carica portafoglio</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\FatturaProformaController::class,'index'])}}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-receipt-square fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Fatture proforma</span>
                            </a>
                        </div>
                    @endcan
                    @can('ebike-b2b')
                        <div class="menu-item">
                            <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'index'])}}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-gift fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Ebike B2B</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\EbikeOrdineController::class,'create'])}}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Nuovo ordine ebike</span>
                            </a>
                        </div>
                    @endcan
                @endif

                <div class="menu-item">
                    <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\ChatController::class,'index'])}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-send fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Chat interna
                            @php($chatDaLeggere=\App\Models\ChatThreadUser::conteggioNonLetti(Auth::id()))
                            <span class="badge badge-danger fw-bolder my-2 ms-2 js-chat-unread-wrap {{$chatDaLeggere ? '' : 'd-none'}}">
                                <span class="js-chat-unread-total">{{$chatDaLeggere}}</span>
                            </span>
                        </span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-information fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Tickets
                            @php($daLeggere=\App\Http\MieClassiCache\CacheConteggioTicketsDaLeggere::get(Auth::id()))
                            @if($daLeggere)
                                <span class="badge badge-danger fw-bolder my-2 ms-2 animation-blink">
                                    {{\App\singolareOplurale($daLeggere,'nuovo','nuovi')}}
                                </span>
                            @endif
                        </span>
                    </a>
                </div>

                @can('admin')

                    <div class="menu-item">
                        <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\PdfToolsController::class, 'index']) }}">
                            <span class="menu-icon">
                                <i class="fas fa-file-pdf fs-2"></i>
                            </span>
                            <span class="menu-title">PDF Tools</span>
                        </a>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-receipt-square fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                    <span class="menu-title">Fatturazione</span>
                    <span class="menu-arrow"></span>
                </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Produzioni</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\FatturaProformaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Proforma agenti</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\BillingDocumentController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Documenti fatturazione</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\BillingDocumentController::class,'invoiceshelfIndex'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">XML FatturaPA (SDI)</span>
                                </a>
                            </div>

                        </div>
                        <!--end:Menu sub-->
                    </div>





                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">

                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="fas fa-cog fs-2"></i>
                        </span>
                        <span class="menu-title">Impostazioni</span>
                        <span class="menu-arrow"></span>
                    </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <span class="menu-link">
                                    <span class="menu-title">Controlli contratti</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{ route('controlli-contratti') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot"></span>
                                            </span>
                                            <span class="menu-title">Verifica CF rischio</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <span class="menu-link">
                                    <span class="menu-title">Locker Point</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ url('/backend/locker-point/settings') }}#tariffe">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Tariffe e capacità</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ url('/backend/locker-point/settings') }}#prenotazioni">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Accettazione e portale</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ url('/backend/locker-point/settings') }}#notifiche">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Notifiche</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ url('/backend/locker-point/settings') }}#api">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Integrazione API REST</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link" href="{{ url('/backend/locker-point/postazioni') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Postazioni agenti / API</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Contratti Telefonia</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\GestoreController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Gestori</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoContrattoController::class,'index'])}}">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
                                            <span class="menu-title">Tipi contratto</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoTelefoniaController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Listini</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Contratti Energia</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoContrattoEnergiaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\GestoreContrattoEnergiaController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Gestori</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Caf Patronato</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoCafPatronatoController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoCafPatronatoController::class,'index'])}}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                            <span class="menu-title">Tipo caf patronato</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Visure</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoVisuraController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoVisuraController::class,'index'])}}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                            <span class="menu-title">Tipi visure</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Assistenza</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ProdottoAssistenzaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Prodotti</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Gestione spedizioni</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'showPrezziAgenti'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Ricarico agenti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoBrtController::class,'index'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Listino Italia</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoBrtEuropaController::class,'index'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Listino Europa</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoInpostController::class,'index'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Listino InPost</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\InpostConsoleController::class,'account'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Account InPost</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\InpostConsoleController::class,'deposits'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Deposits InPost</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Ticket</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\CausaleTicketController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Causali ticket</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <span class="menu-link">
                                    <span class="menu-title">Deposito Bagagli</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'settings']) }}#tariffe">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Tariffe e capacità</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'settings']) }}#prenotazioni">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Prenotazioni e portale</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'settings']) }}#notifiche">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Notifiche</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{ action([\App\Http\Controllers\Backend\LuggageDepositController::class, 'settings']) }}#api">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Integrazione API REST</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            @can('manageSettings', \App\Models\SendRequest::class)
                                <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                    <span class="menu-link">
                                        <span class="menu-title">SEND</span>
                                        <span class="menu-arrow"></span>
                                    </span>
                                    <div class="menu-sub menu-sub-accordion">
                                        <div class="menu-item">
                                            <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'dashboard']) }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Dashboard</span>
                                            </a>
                                        </div>
                                        @can('viewReports', \App\Models\SendRequest::class)
                                            <div class="menu-item">
                                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'report']) }}">
                                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                    <span class="menu-title">Report</span>
                                                </a>
                                            </div>
                                        @endcan
                                        <div class="menu-item">
                                            <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'settings']) }}">
                                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                                <span class="menu-title">Impostazioni modulo</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endcan

                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{ route('settings') }}#ebike-b2b">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Ebike B2B</span>
                                </a>
                            </div>

                        </div>
                        <!--end:Menu sub-->
                    </div>


                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-status fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                    <span class="menu-title">Registri</span>
                    <span class="menu-arrow"></span>
                </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'login')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Login</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'email')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Email inviate</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'backup-db')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Backup DB</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'errori')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Errori server</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'upload')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Upload e allegati</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'modifiche')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Modifiche</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\ChiamataApiController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Chiamate API</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'info-sito')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Info varie</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'elenco_licenze')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Licenze</span>
                                </a>
                            </div>
                            @if(Auth::id()==1 || env('APP_ENV')=='local')
                                <div class="menu-item">
                                    <a class="menu-link" href="/backend/log-viewer/logs" target="_blank">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Log viewer</span>
                                    </a>
                                </div>
                            @endif

                        </div>
                        <!--end:Menu sub-->
                    </div>

                @endcan
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
@else
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
             data-kt-scroll="true" data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
             data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu"
                 data-kt-menu="true" data-kt-menu-expand="false">
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <rect x="2" y="2" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="13" y="2" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="13" y="13" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="2" y="13" width="9" height="9" rx="2" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </div>

                @can('servizio_contratti_telefonia')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                            <span class="menu-title">Contratti</span>
                        </a>
                    </div>
                @endcan
                @can('servizio_spedizioni')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                            <span class="menu-title">Spedizione BRT</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\SpedizioneInpostController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z" fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                            <span class="menu-title">Spedizione InPost</span>
                        </a>
                    </div>
                @endcan
                @can('servizio_caf_patronato')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                            <span class="menu-title">Pratiche Caf/Patronato</span>
                        </a>
                    </div>
                @endcan
                @can('viewAny', \App\Models\SendRequest::class)
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            @include('Backend._layout.sidebar-send-icon')
                            <span class="menu-title">SEND</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'dashboard']) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Dashboard</span>
                                </a>
                            </div>
                            @can('send.requests.process')
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'queue']) }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Coda</span>
                                    </a>
                                </div>
                            @endcan
                            <div class="menu-item">
                                <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'integrations']) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Integrazioni</span>
                                </a>
                            </div>
                            @can('viewReports', \App\Models\SendRequest::class)
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'report']) }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Report</span>
                                    </a>
                                </div>
                            @endcan
                            @can('manageSettings', \App\Models\SendRequest::class)
                                <div class="menu-item">
                                    <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'settings']) }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Impostazioni</span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endcan
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
@endif
