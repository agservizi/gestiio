@extends('Frontend._layout.main')

@push('customCss')
    <style>
        .customer-shell {
            width: min(1280px, calc(100vw - 64px));
            margin: 0 auto;
        }

        .customer-hero {
            border-radius: 18px;
            padding: 24px;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 48%, #0ea5e9 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .customer-hero::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            right: -120px;
            top: -120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.16);
        }

        .customer-hero h1 {
            font-size: clamp(1.4rem, 2.3vw, 2rem);
            margin-bottom: 6px;
        }

        .customer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .customer-actions .btn {
            border-radius: 999px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .kpi-card {
            border-radius: 14px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .kpi-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 1.45rem;
            line-height: 1;
            font-weight: 700;
            color: #0f172a;
        }

        .customer-main {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 14px;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .panel h3 {
            margin: 0;
            font-size: 1rem;
            color: #111827;
        }

        .event-list {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .event-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f9fafb;
        }

        .event-code {
            font-weight: 700;
            color: #0f172a;
            margin-right: 8px;
        }

        .quick-grid {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .quick-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            color: #111827;
            background: #ffffff;
        }

        .quick-link:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #111827;
        }

        @media (max-width: 1200px) {
            .customer-shell {
                width: calc(100vw - 24px);
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .customer-main {
                grid-template-columns: 1fr;
            }

            .customer-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="customer-shell">
        @php
            $currentUser = \Illuminate\Support\Facades\Auth::user();
            $isCurrentAdmin = $currentUser && ($currentUser->hasPermissionTo('admin') || $currentUser->hasRole('admin'));
            $origAdmin = null;
            if (session()->has('impersona')) {
                $orig = \App\Models\User::find(session('impersona'));
                if ($orig && ($orig->hasPermissionTo('admin') || $orig->hasRole('admin'))) {
                    $origAdmin = $orig;
                }
            }
        @endphp

        <div class="customer-hero mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <h1 class="text-white">Ciao {{ Auth::user()->nome }}, benvenuto nella tua area personale</h1>
                    <div class="text-white opacity-75">Gestisci account, ticket e contratti in un unico spazio operativo.</div>
                </div>
                <div class="col-lg-4">
                    <div class="customer-actions">
                        <button class="btn btn-sm btn-light" type="button" data-kt-drawer-show="true" data-kt-drawer-target="#kt_account">Gestione account</button>
                        @if($isCurrentAdmin || $origAdmin)
                            @if($origAdmin)
                                <a href="{{ url('/stop-impersona') }}" class="btn btn-sm btn-warning">Torna admin</a>
                            @else
                                <a href="{{ url('/backend') }}" class="btn btn-sm btn-warning">Area admin</a>
                            @endif
                        @endif
                        <a href="{{ url('/logout') }}" class="btn btn-sm btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="kpi-grid mb-4">
            <div class="kpi-card">
                <div class="kpi-label">Ticket Totali</div>
                <div class="kpi-value">{{ $ticketsTotali ?? 0 }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Ticket Aperti</div>
                <div class="kpi-value">{{ $ticketsAperti ?? 0 }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Da Leggere</div>
                <div class="kpi-value">{{ $ticketsDaLeggere ?? 0 }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Contratti Totali</div>
                <div class="kpi-value">{{ $contrattiTotali ?? 0 }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">In Lavorazione</div>
                <div class="kpi-value">{{ $contrattiInLavorazione ?? 0 }}</div>
            </div>
        </div>

        <div class="customer-main">
            <div class="panel">
                <div class="d-flex justify-content-between align-items-center">
                    <h3>Attività ticket recenti</h3>
                    <button class="btn btn-sm btn-light-primary" type="button" data-kt-drawer-show="true" data-kt-drawer-target="#kt_help">Apri sidebar ticket</button>
                </div>
                <div class="event-list">
                    @if(($ticketsRecenti ?? collect())->count() === 0)
                        <div class="event-item text-muted">Nessuna attività ticket disponibile.</div>
                    @else
                        @foreach($ticketsRecenti->take(5) as $ticketRecente)
                            <div class="event-item">
                                <span class="event-code">{{ $ticketRecente->uidTicket() }}</span>
                                <span>{{ $ticketRecente->oggetto }}</span>
                                <div class="text-muted fs-8 mt-1">{{ $ticketRecente->updated_at->format('d/m/Y H:i') }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="panel">
                <h3>Azioni rapide</h3>
                <div class="quick-grid">
                    <a href="{{ action([\App\Http\Controllers\Frontend\TicketController::class,'create']) }}" data-target="kt_modal" data-toggle="modal-ajax" class="quick-link">
                        <span>Apri nuovo ticket</span>
                        <span class="badge badge-light-primary">Nuovo</span>
                    </a>
                    <button type="button" class="quick-link" data-kt-drawer-show="true" data-kt-drawer-target="#kt_help">
                        <span>Visualizza ticket dalla sidebar</span>
                        <span class="badge badge-light">Tickets</span>
                    </button>
                    <button type="button" class="quick-link" data-kt-drawer-show="true" data-kt-drawer-target="#kt_engage_demos">
                        <span>Visualizza contratti dalla sidebar</span>
                        <span class="badge badge-light">Contratti</span>
                    </button>
                    <a href="{{ action([\App\Http\Controllers\Frontend\TicketController::class,'index']) }}" class="quick-link">
                        <span>Apri pagina completa ticket</span>
                        <span class="badge badge-light">Lista</span>
                    </a>
                    <a href="{{ url('/area-personale/contratti') }}" class="quick-link">
                        <span>Apri pagina completa contratti</span>
                        <span class="badge badge-light">Lista</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('sidebar')
@endsection
