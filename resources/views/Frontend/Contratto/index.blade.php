@extends('Frontend._layout.main')

@push('customCss')
    <style>
        .contract-shell {
            width: min(1280px, calc(100vw - 64px));
            margin: 0 auto;
        }

        .contract-hero {
            border-radius: 18px;
            padding: 22px;
            background: linear-gradient(135deg, #111827 0%, #0f766e 55%, #2dd4bf 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .contract-hero::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            right: -90px;
            top: -100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .contract-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .contract-actions .btn {
            border-radius: 999px;
        }

        .contract-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .contract-kpi {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .contract-kpi .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .contract-kpi .value {
            margin-top: 6px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }

        .contract-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .contract-list {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .contract-item {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 12px;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
            padding: 12px;
        }

        .contract-type {
            font-weight: 700;
        }

        .contract-sub {
            color: #6b7280;
            font-size: .86rem;
            margin-top: 2px;
        }

        .contract-meta {
            color: #374151;
            font-size: .85rem;
        }

        @media (max-width: 1200px) {
            .contract-shell {
                width: calc(100vw - 24px);
            }

            .contract-kpis {
                grid-template-columns: 1fr;
            }

            .contract-item {
                grid-template-columns: 1fr;
            }

            .contract-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="contract-shell">
        <div class="contract-hero mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-1 text-white fw-bold">I tuoi contratti</h2>
                    <div class="opacity-75">Panoramica completa dei contratti associati al tuo account.</div>
                </div>
                <div class="col-lg-4">
                    <div class="contract-actions">
                        <button class="btn btn-sm btn-light" type="button" data-kt-drawer-show="true" data-kt-drawer-target="#kt_engage_demos">
                            Sidebar contratti
                        </button>
                        <a href="{{ url('/area-personale') }}" class="btn btn-sm btn-warning">Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="contract-kpis mb-4">
            <div class="contract-kpi">
                <div class="label">Totale contratti pagina</div>
                <div class="value">{{ $records->total() }}</div>
            </div>
            <div class="contract-kpi">
                <div class="label">Pagina corrente</div>
                <div class="value">{{ $records->currentPage() }}/{{ max(1, $records->lastPage()) }}</div>
            </div>
            <div class="contract-kpi">
                <div class="label">Record nella pagina</div>
                <div class="value">{{ $records->count() }}</div>
            </div>
        </div>

        <div class="contract-panel">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0 fs-5">Elenco contratti</h3>
                <span class="text-muted fs-7">Aggiornato in tempo reale</span>
            </div>

            @if($records->count() === 0)
                <div class="alert alert-info mb-0 mt-3">Non hai ancora contratti associati al tuo account.</div>
            @else
                <div class="contract-list">
                    @foreach($records as $record)
                        <div class="contract-item">
                            <div>
                                <div class="contract-type" style="color: {{ $record->tipoContratto?->gestore?->colore_hex ?? '#3F4254' }};">
                                    {{ $record->tipoContratto?->nome ?? '-' }}
                                </div>
                                <div class="contract-sub">{{ $record->comune?->comuneConTarga() ?? 'Località non disponibile' }}</div>
                            </div>
                            <div class="contract-meta">
                                <div>Data inserimento</div>
                                <div class="fw-bold">{{ optional($record->data)->format('d/m/Y') ?: '-' }}</div>
                            </div>
                            <div class="contract-meta">
                                <div>Stato</div>
                                <div>{!! $record->esito?->labelStato() !!}</div>
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
