@can('servizio_send')
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
                @can('send.requests.view-own')
                    <div class="menu-item">
                        <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'index']) }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Elenco richieste</span>
                        </a>
                    </div>
                @endcan
                @can('send.requests.update')
                    @unless(app(\App\Policies\SendRequestPolicy::class)->isSupervisorOnly(auth()->user()))
                        <div class="menu-item">
                            <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'integrations']) }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Integrazioni</span>
                            </a>
                        </div>
                    @endunless
                @endcan
                @can('create', \App\Models\SendRequest::class)
                    <div class="menu-item">
                        <a class="menu-link" href="{{ action([\App\Http\Controllers\Backend\SendRequestController::class, 'create']) }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">Nuova richiesta</span>
                        </a>
                    </div>
                @endcan
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
@endcan
