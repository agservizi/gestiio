@extends('Frontend._layout.main')

@push('customCss')
    <style>
        .ticket-shell {
            width: min(1280px, calc(100vw - 64px));
            margin: 0 auto;
        }

        .ticket-hero {
            border-radius: 18px;
            padding: 22px;
            background: linear-gradient(135deg, #111827 0%, #1d4ed8 55%, #38bdf8 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .ticket-hero::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            right: -90px;
            top: -100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .ticket-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .ticket-actions .btn {
            border-radius: 999px;
        }

        .ticket-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .ticket-kpi {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .ticket-kpi .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .ticket-kpi .value {
            margin-top: 6px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }

        .ticket-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .ticket-list {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .ticket-item {
            display: grid;
            grid-template-columns: 1.4fr 1fr auto;
            align-items: center;
            gap: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
            padding: 12px;
        }

        .ticket-code {
            font-weight: 700;
            color: #0f172a;
        }

        .ticket-sub {
            color: #6b7280;
            font-size: .86rem;
            margin-top: 2px;
        }

        .ticket-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: #374151;
            font-size: .85rem;
        }

        @media (max-width: 1200px) {
            .ticket-shell {
                width: calc(100vw - 24px);
            }

            .ticket-kpis {
                grid-template-columns: 1fr;
            }

            .ticket-item {
                grid-template-columns: 1fr;
            }

            .ticket-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="ticket-shell">
        <div class="ticket-hero mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-1 text-white fw-bold">I tuoi ticket</h2>
                    <div class="opacity-75">Monitora richieste, stati e risposte in modo rapido.</div>
                </div>
                <div class="col-lg-4">
                    <div class="ticket-actions">
                        <a href="{{ action([\App\Http\Controllers\Frontend\TicketController::class, 'create']) }}" data-target="kt_modal" data-toggle="modal-ajax" class="btn btn-sm btn-light">
                            Nuovo ticket
                        </a>
                        <button class="btn btn-sm btn-light-primary" type="button" data-kt-drawer-show="true" data-kt-drawer-target="#kt_help">
                            Sidebar ticket
                        </button>
                        <a href="{{ url('/area-personale') }}" class="btn btn-sm btn-warning">Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-kpis mb-4">
            <div class="ticket-kpi">
                <div class="label">Totale ticket pagina</div>
                <div class="value">{{ $records->total() }}</div>
            </div>
            <div class="ticket-kpi">
                <div class="label">Messaggi non letti</div>
                <div class="value">{{ $unreadCount }}</div>
            </div>
            <div class="ticket-kpi">
                <div class="label">Pagina corrente</div>
                <div class="value">{{ $records->currentPage() }}/{{ max(1, $records->lastPage()) }}</div>
            </div>
        </div>

        <div class="ticket-panel">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0 fs-5">Elenco ticket</h3>
                <span class="text-muted fs-7">Ordinati per aggiornamento</span>
            </div>

            @if($records->count() === 0)
                <div class="alert alert-info mb-0 mt-3">Non hai ancora aperto ticket.</div>
            @else
                <div class="ticket-list">
                    @foreach($records as $record)
                        <div class="ticket-item">
                            <div>
                                <div class="ticket-code">{{ $record->uidTicket() }} - {{ $record->oggetto }}</div>
                                <div class="ticket-sub">Aggiornato il {{ $record->updated_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="ticket-meta">
                                <span>{{ \App\Models\Ticket::TIPI_TICKETS[$record->tipo] ?? $record->tipo }}</span>
                                <span>{!! $record->labelStatoTicket() !!}</span>
                            </div>
                            <div>
                                <a data-target="kt_modal" data-toggle="modal-ajax"
                                   class="btn btn-sm btn-light btn-active-light-primary"
                                   href="{{ action([\App\Http\Controllers\Frontend\TicketController::class, 'show'], $record->id) }}">Apri</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@section('sidebar')
@endsection
