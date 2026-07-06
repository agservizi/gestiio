<div class="row g-5 g-xl-10 mb-5 mb-xl-10 admin-overview-strip">
    <div class="col-xl-6">
        <div class="card card-flush h-lg-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M3 13.2C3 9.32002 6.14 6.18002 10.02 6.18002C11.76 6.18002 13.36 6.82002 14.6 7.86002L16.32 6.14002C16.64 5.82002 17.18 5.98002 17.26 6.42002L17.84 9.76002C17.88 10.02 17.7 10.26 17.44 10.3L14.1 10.88C13.66 10.96 13.5 10.42 13.82 10.1L15.5 8.42002C14.48 7.56002 13.2 7.04002 11.78 7.04002C8.46002 7.04002 5.76002 9.74002 5.76002 13.06C5.76002 16.38 8.46002 19.08 11.78 19.08C14.1 19.08 16.12 17.76 17.12 15.84C17.3 15.5 17.74 15.36 18.08 15.54C18.42 15.72 18.56 16.16 18.38 16.5C17.14 18.88 14.62 20.52 11.78 20.52C7.90002 20.52 3 17.08 3 13.2Z" fill="currentColor"/>
                            <path d="M20 5.00002C20 4.44002 19.56 4.00002 19 4.00002C18.44 4.00002 18 4.44002 18 5.00002V11C18 11.56 18.44 12 19 12C19.56 12 20 11.56 20 11V5.00002Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="fw-bolder mb-0">Esito finale</h3>
                        <div class="fs-7 fw-semibold text-gray-400">Monitoraggio esiti contratti</div>
                    </div>
                </div>
            </div>
            <div class="card-body p-8 pt-4">
                <div class="bg-light-primary rounded p-4 mb-5 d-flex align-items-center justify-content-between">
                    <span class="text-muted fw-semibold">Ordini totali</span>
                    <span class="fs-2 fw-bolder text-primary">{{ number_format((int) $datiTortaEsiti['totale']) }}</span>
                </div>

                <div class="d-flex flex-wrap align-items-center">
                    <div class="position-relative d-flex flex-center h-280px w-280px me-5 mb-7 mb-xl-0">
                        <div id="kt_card_widget_17_chart" style="width: 280px; height: 280px;"></div>
                    </div>
                    <div class="d-flex flex-column justify-content-center flex-row-fluid pe-0 pe-xl-5">
                        @for($n=0;$n<count($datiTortaEsiti['labels']);$n++)
                            <div class="d-flex fs-6 fw-bold align-items-center mb-2 p-2 rounded bg-light">
                                <div class="bullet me-3 h-6px w-20px" style="background-color: {{ $datiTortaEsiti['backgroundColor'][$n] }};"></div>
                                <div class="text-gray-700">{{ $datiTortaEsiti['labels'][$n] }}</div>
                                <div class="ms-auto fw-bolder text-gray-900">{{ number_format((int) $datiTortaEsiti['data'][$n]) }}</div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush h-lg-100">
            <div class="card-header border-0 pt-5 pb-0">
                <div class="card-title d-flex align-items-center gap-2">
                    <span class="svg-icon svg-icon-2 text-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3" d="M4 6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H10L6 20V16C4.89543 16 4 15.1046 4 14V6Z" fill="currentColor"/>
                            <path d="M8 8C8 7.44772 8.44772 7 9 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H9C8.44772 9 8 8.55228 8 8ZM8 11.5C8 10.9477 8.44772 10.5 9 10.5H13C13.5523 10.5 14 10.9477 14 11.5C14 12.0523 13.5523 12.5 13 12.5H9C8.44772 12.5 8 12.0523 8 11.5Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="fw-bolder mb-0">Chat interna</h3>
                        <div class="fs-7 fw-semibold text-gray-400">Monitoraggio conversazioni</div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="bg-light-primary rounded p-4 mb-4">
                    <div class="text-muted fw-semibold fs-8 mb-1">Messaggi non letti</div>
                    <div class="fs-2 fw-bolder text-primary">{{ number_format((int) $chatDashboard['messaggi_non_letti']) }}</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted fw-semibold">Thread attive (7 giorni)</span>
                    <span class="badge badge-light-primary fw-bolder px-4 py-2">{{ number_format((int) $chatDashboard['thread_attive']) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <span class="text-muted fw-semibold">Nuovi messaggi oggi</span>
                    <span class="badge badge-light-success fw-bolder px-4 py-2">{{ number_format((int) $chatDashboard['nuovi_messaggi_oggi']) }}</span>
                </div>

                <a href="{{ action([\App\Http\Controllers\Backend\ChatController::class, 'index']) }}" class="btn btn-light-primary btn-sm">Apri chat interna</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        @include('Backend.Dashboard.linksGestori',['altezza'=>'h-lg-100'])
    </div>
</div>
