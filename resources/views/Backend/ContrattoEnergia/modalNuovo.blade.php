@extends('Backend._layout._main')
@section('toolbar') @endsection

@section('content')
    @php
        $selected = old('tipo_servizio');
        $isBackoffice = Auth::user()->hasAnyPermission(['admin','operatore','supervisore']);
    @endphp

    <div class="energy-create-page">
        <div class="energy-create-hero">
            <div>
                <div class="energy-eyebrow">Contratti energia</div>
                <h1>Nuovo contratto energia</h1>
                <p>Scegli il gestore e apri un flusso guidato con dati cliente, categoria pratica, fornitura e allegati in un'unica pagina ordinata.</p>
            </div>
            <div class="energy-hero-panel">
                <span class="energy-panel-label">Gestori disponibili</span>
                <strong>{{ $servizi->count() }}</strong>
                <span class="energy-panel-subtitle">{{ $isBackoffice ? 'Creazione backoffice attiva' : 'Scrivania agente attiva' }}</span>
            </div>
        </div>

        @include('Backend._components.alertErrori')

        <div class="energy-service-toolbar">
            <div>
                <h2>Seleziona gestore</h2>
                <span>Apri il form corretto in base al gestore e alla categoria configurata.</span>
            </div>
            <div class="energy-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="energy-service-search" placeholder="Cerca gestore, es. A2A, Enel, Illumia" autocomplete="off">
            </div>
        </div>

        <div class="energy-service-grid" id="energy-service-grid">
            @foreach($servizi as $servizio)
                @php
                    $serviceName = trim($servizio->nome);
                    $categoria = method_exists($servizio, 'categoriaLabel') ? $servizio->categoriaLabel() : ucfirst((string) $servizio->categoria_pratica);
                    $warning = method_exists($servizio, 'warningConfigurazioneProvvigioniBusiness') ? $servizio->warningConfigurazioneProvvigioniBusiness() : null;
                @endphp
                <article class="energy-service-card" data-service-name="{{ \Illuminate\Support\Str::lower($serviceName.' '.$categoria) }}">
                    <div class="energy-service-top">
                        <span class="energy-logo">
                            @if($servizio->logo || $servizio->logo_contenuto_base64)
                                <img src="{{ $servizio->immagineLogo() }}" alt="{{ $serviceName }}">
                            @else
                                <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                            @endif
                        </span>
                        <span class="energy-service-status {{ $warning ? 'is-warning' : 'is-ready' }}">
                            {{ $warning ? 'Da verificare' : 'Disponibile' }}
                        </span>
                    </div>

                    <h3>{{ $serviceName }}</h3>
                    <div class="energy-service-meta">
                        <div>
                            <span>Categoria</span>
                            <strong>{{ $categoria ?: 'Generica' }}</strong>
                        </div>
                        <div>
                            <span>Form</span>
                            <strong>{{ $servizio->model_prodotto ? 'Guidato' : 'Base' }}</strong>
                        </div>
                    </div>

                    @if($warning)
                        <div class="energy-service-warning">{{ $warning }}</div>
                    @endif

                    <div class="energy-service-action">
                        <a href="{{ action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'create'], $servizio->id) }}"
                           class="btn btn-primary w-100">
                            Crea contratto
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="energy-empty-state d-none" id="energy-empty-state">
            <i class="bi bi-search" aria-hidden="true"></i>
            <strong>Nessun gestore trovato</strong>
            <span>Prova con un altro nome o svuota la ricerca.</span>
        </div>
    </div>
@endsection

@push('customCss')
    <style>
        .energy-create-page {
            --energy-bg: #f8fafc;
            --energy-surface: #ffffff;
            --energy-text: #020617;
            --energy-muted: #64748b;
            --energy-border: #e2e8f0;
            --energy-primary: #0ea5e9;
            --energy-primary-dark: #0369a1;
            color: var(--energy-text);
        }

        .energy-create-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 320px);
            gap: 24px;
            align-items: stretch;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .energy-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--energy-primary-dark);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .energy-create-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .energy-create-hero p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--energy-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .energy-hero-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 130px;
            padding: 20px;
            border: 1px solid #cfe8ff;
            border-radius: 8px;
            background: rgba(255,255,255,.86);
        }

        .energy-panel-label,
        .energy-panel-subtitle {
            color: var(--energy-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .energy-hero-panel strong {
            margin: 6px 0;
            color: var(--energy-primary-dark);
            font-size: 34px;
            line-height: 1;
        }

        .energy-service-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 420px);
            gap: 16px;
            align-items: center;
            padding: 18px 20px;
            margin-bottom: 16px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: var(--energy-surface);
        }

        .energy-service-toolbar h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
        }

        .energy-service-toolbar span {
            color: var(--energy-muted);
            font-size: 13px;
        }

        .energy-search {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: var(--energy-bg);
        }

        .energy-search input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--energy-text);
            font-size: 14px;
        }

        .energy-search i {
            color: var(--energy-primary);
        }

        .energy-service-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .energy-service-card {
            display: flex;
            flex-direction: column;
            min-height: 260px;
            padding: 18px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: var(--energy-surface);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .energy-service-card:hover {
            border-color: #bae6fd;
            box-shadow: 0 18px 42px rgba(14, 165, 233, .10);
            transform: translateY(-2px);
        }

        .energy-service-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
        }

        .energy-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 52px;
            border-radius: 8px;
            background: var(--energy-bg);
            color: var(--energy-primary-dark);
            overflow: hidden;
        }

        .energy-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .energy-logo i {
            font-size: 24px;
        }

        .energy-service-status {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .energy-service-status.is-ready {
            background: #dcfce7;
            color: #166534;
        }

        .energy-service-status.is-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .energy-service-card h3 {
            min-height: 48px;
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.35;
        }

        .energy-service-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .energy-service-meta div,
        .energy-service-warning {
            padding: 12px;
            border-radius: 8px;
            background: var(--energy-bg);
        }

        .energy-service-meta span {
            display: block;
            color: var(--energy-muted);
            font-size: 11px;
            font-weight: 700;
        }

        .energy-service-meta strong {
            display: block;
            margin-top: 4px;
            font-size: 14px;
            font-weight: 800;
        }

        .energy-service-warning {
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
        }

        .energy-service-action {
            margin-top: auto;
        }

        .energy-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            margin-top: 16px;
            border: 1px dashed #bfdbfe;
            border-radius: 8px;
            background: #f8fbff;
            color: var(--energy-muted);
            text-align: center;
        }

        .energy-empty-state i {
            color: var(--energy-primary);
            font-size: 28px;
            margin-bottom: 10px;
        }

        .energy-empty-state strong {
            color: var(--energy-text);
            font-size: 16px;
        }

        @media (max-width: 1399.98px) {
            .energy-service-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .energy-create-hero,
            .energy-service-toolbar {
                grid-template-columns: 1fr;
            }

            .energy-service-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .energy-create-hero,
            .energy-service-toolbar {
                padding: 18px;
            }

            .energy-service-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('customScript')
    <script>
        (function () {
            var input = document.getElementById('energy-service-search');
            var grid = document.getElementById('energy-service-grid');
            var empty = document.getElementById('energy-empty-state');

            function normalize(value) {
                return (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
            }

            function filterEnergyServices() {
                var term = normalize(input ? input.value : '');
                var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.energy-service-card')) : [];
                var visible = 0;

                cards.forEach(function (card) {
                    var haystack = normalize(card.getAttribute('data-service-name') || card.textContent);
                    var match = !term || haystack.indexOf(term) !== -1;
                    card.classList.toggle('d-none', !match);
                    if (match) {
                        visible++;
                    }
                });

                if (empty) {
                    empty.classList.toggle('d-none', visible !== 0);
                }
            }

            if (input) {
                input.addEventListener('input', filterEnergyServices);
                input.addEventListener('search', filterEnergyServices);
            }
        })();
    </script>
@endpush
