<div class="row g-5 g-xl-10 mb-5 mb-xl-10 admin-kpi-strip">
    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M4 19C4 17.8954 4.89543 17 6 17H20V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19Z" fill="currentColor"/>
                            <path d="M6 3C4.89543 3 4 3.89543 4 5V15H20V5C20 3.89543 19.1046 3 18 3H6ZM7 12L10.2 8.8L12.4 11L16.5 6.9L18 8.4L12.4 14L10.2 11.8L8.5 13.5L7 12Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <h3 class="card-title m-0">Produzione mese</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="bg-light-primary rounded p-4 mb-4">
                    <div class="text-muted fw-semibold fs-8 mb-1">Contratti totali</div>
                    <div class="fs-2 fw-bolder text-primary">{{ number_format($produzioneConteggio) }}</div>
                </div>
                <div class="d-flex justify-content-between fw-semibold mb-2">
                    <span class="text-muted">In lavorazione</span>
                    <span>{{ number_format($produzioneInLavorazione) }} ({{ $percentualeProduzione }}%)</span>
                </div>
                <div class="progress h-8px bg-light-primary">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentualeProduzione }}%" aria-valuenow="{{ $percentualeProduzione }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M4 19C4 17.8954 4.89543 17 6 17H20V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19Z" fill="currentColor"/>
                            <path d="M6 3H18C19.1046 3 20 3.89543 20 5V15H6C4.89543 15 4 15.8954 4 17V5C4 3.89543 4.89543 3 6 3Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <h3 class="card-title m-0">Economico mese</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="bg-light-primary rounded p-4 mb-4">
                    <div class="text-muted fw-semibold fs-8 mb-1">Utile stimato</div>
                    <div class="fs-2 fw-bolder text-primary">{{ importo($guadagno->utile,true) }}</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-semibold">Entrate</span>
                    <span class="badge badge-light-success fw-bolder px-4 py-2">{{ importo($guadagno->entrate,true) }}</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted fw-semibold">Uscite</span>
                    <span class="badge badge-light-danger fw-bolder px-4 py-2">{{ importo($guadagno->uscite,true) }}</span>
                </div>

                <div class="d-flex justify-content-between fw-semibold mb-2">
                    <span class="text-muted">Incidenza utile</span>
                    <span>{{ $percentualeUtile }}%</span>
                </div>
                <div class="progress h-8px bg-light-success">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentualeUtile }}%" aria-valuenow="{{ $percentualeUtile }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M4 5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V8C18.8954 8 18 8.89543 18 10C18 11.1046 18.8954 12 20 12V15C20 16.1046 19.1046 17 18 17H6C4.89543 17 4 16.1046 4 15V12C5.10457 12 6 11.1046 6 10C6 8.89543 5.10457 8 4 8V5Z" fill="currentColor"/>
                            <path d="M9 7.5C9 6.67157 9.67157 6 10.5 6H13.5C14.3284 6 15 6.67157 15 7.5C15 8.32843 14.3284 9 13.5 9H10.5C9.67157 9 9 8.32843 9 7.5Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <h3 class="card-title m-0">Ticket</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="bg-light-primary rounded p-4 mb-4">
                    <div class="text-muted fw-semibold fs-8 mb-1">Aperti / in lavorazione</div>
                    <div class="fs-2 fw-bolder text-primary">{{ number_format($ticketAperti) }}</div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-5">
                    <span class="text-muted fw-semibold">Chiusi</span>
                    <span class="badge badge-light-success fw-bolder px-4 py-2">{{ number_format($ticketChiusi) }}</span>
                </div>

                <a href="{{ action([\App\Http\Controllers\Backend\TicketsController::class, 'index']) }}" class="btn btn-light-primary btn-sm">Apri ticket</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M12 3C7.02944 3 3 6.80558 3 11.5C3 14.0409 4.18896 16.3218 6.07143 17.8898V21L9.02857 19.3716C9.94801 19.623 10.9482 19.75 12 19.75C16.9706 19.75 21 15.9444 21 11.25C21 6.55558 16.9706 3 12 3Z" fill="currentColor"/>
                            <path d="M8.5 11.25C8.5 10.6977 8.94772 10.25 9.5 10.25H14.5C15.0523 10.25 15.5 10.6977 15.5 11.25C15.5 11.8023 15.0523 12.25 14.5 12.25H9.5C8.94772 12.25 8.5 11.8023 8.5 11.25Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <h3 class="card-title m-0">Assistenza</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="bg-light-primary rounded p-4 mb-4">
                    <div class="text-muted fw-semibold fs-8 mb-1">Richieste totali</div>
                    <div class="fs-2 fw-bolder text-primary">{{ number_format($kpiDashboard['richieste_assistenza_totali']) }}</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-semibold">Nuove oggi</span>
                    <span class="badge badge-light-primary fw-bolder px-4 py-2">{{ number_format($kpiDashboard['richieste_assistenza_oggi']) }}</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-5">
                    <span class="text-muted fw-semibold">Senza credenziali</span>
                    <span class="badge badge-light-danger fw-bolder px-4 py-2">{{ number_format($alertDashboard['richieste_senza_credenziali']) }}</span>
                </div>

                <a href="{{ action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class, 'index']) }}" class="btn btn-light-warning btn-sm">Apri richieste</a>
            </div>
        </div>
    </div>
</div>
