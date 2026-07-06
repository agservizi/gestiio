@extends('Backend._layout._main')
@section('toolbar') @endsection

@section('content')
    @php
        $servizi = \App\Models\TipoCafPatronato::orderBy('nome')->get();
        $isBackoffice = $isBackoffice ?? Auth::user()->hasAnyPermission(['admin','operatore','supervisore']);
    @endphp

    <div class="caf-create-page">
        <div class="caf-create-hero">
            <div>
                <div class="caf-eyebrow">CAF / Patronato</div>
                <h1>Nuova pratica</h1>
                <p>Scegli il servizio da aprire. Dopo la selezione compilerai anagrafica, dettagli richiesti e allegati in un unico flusso guidato.</p>
            </div>
            <div class="caf-hero-panel">
                <span class="caf-panel-label">Servizi disponibili</span>
                <strong>{{ $servizi->count() }}</strong>
                @if(! $isBackoffice)
                    <span class="caf-panel-subtitle">Plafond servizi {{ importo($portafoglioServizi ?? 0) }}</span>
                @else
                    <span class="caf-panel-subtitle">Creazione backoffice attiva</span>
                @endif
            </div>
        </div>

        @include('Backend._components.alertErrori')

        <div class="caf-service-toolbar">
            <div>
                <h2>Seleziona servizio</h2>
                <span>Prezzo cliente e costo agente sempre visibili prima dell'apertura.</span>
            </div>
            <div class="caf-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="caf-service-search" placeholder="Cerca servizio, es. 730, ISEE, NASPI" autocomplete="off">
            </div>
        </div>

        <div class="caf-service-grid" id="caf-service-grid">
            @foreach($servizi as $servizio)
                @php
                    $hasWallet = $isBackoffice || (($portafoglioServizi ?? 0) >= $servizio->prezzo_agente);
                    $serviceName = trim($servizio->nome);
                @endphp
                <article class="caf-service-card" data-service-name="{{ \Illuminate\Support\Str::lower($serviceName) }}">
                    <div class="caf-service-top">
                        <span class="caf-service-icon">
                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                        </span>
                        <span class="caf-service-status {{ $hasWallet ? 'is-ready' : 'is-blocked' }}">
                            {{ $hasWallet ? 'Disponibile' : 'Plafond insufficiente' }}
                        </span>
                    </div>

                    <h3>{{ $serviceName }}</h3>
                    <div class="caf-service-meta">
                        <div>
                            <span>Prezzo consigliato</span>
                            <strong>{{ importo($servizio->prezzo_cliente) }}</strong>
                        </div>
                        <div>
                            <span>Costo</span>
                            <strong>{{ importo($servizio->prezzo_agente) }}</strong>
                        </div>
                    </div>

                    <div class="caf-service-action">
                        @if($hasWallet)
                            <a href="{{ action([\App\Http\Controllers\Backend\CafPatronatoController::class,'create'], $servizio->id) }}"
                               class="btn btn-primary w-100">
                                Crea pratica
                            </a>
                        @else
                            <a href="{{ action([\App\Http\Controllers\Backend\PortafoglioController::class,'create']) }}"
                               class="btn btn-light-danger w-100">
                                Ricarica portafoglio
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="caf-empty-state d-none" id="caf-empty-state">
            <i class="bi bi-search" aria-hidden="true"></i>
            <strong>Nessun servizio trovato</strong>
            <span>Prova con un altro nome o svuota la ricerca.</span>
        </div>
    </div>
@endsection

@push('customCss')
    <style>
        .caf-create-page {
            --caf-bg: #f8fafc;
            --caf-surface: #ffffff;
            --caf-text: #020617;
            --caf-muted: #64748b;
            --caf-border: #e2e8f0;
            --caf-soft: #eef6ff;
            --caf-primary: #0ea5e9;
            --caf-primary-dark: #0369a1;
            color: var(--caf-text);
        }

        .caf-create-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 320px);
            gap: 24px;
            align-items: stretch;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .caf-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--caf-primary-dark);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .caf-create-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .caf-create-hero p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--caf-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .caf-hero-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 130px;
            padding: 20px;
            border: 1px solid #cfe8ff;
            border-radius: 8px;
            background: rgba(255,255,255,.82);
        }

        .caf-panel-label,
        .caf-panel-subtitle {
            color: var(--caf-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .caf-hero-panel strong {
            margin: 6px 0;
            color: var(--caf-primary-dark);
            font-size: 34px;
            line-height: 1;
        }

        .caf-service-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 420px);
            gap: 16px;
            align-items: center;
            padding: 18px 20px;
            margin-bottom: 16px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-surface);
        }

        .caf-service-toolbar h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
        }

        .caf-service-toolbar span {
            color: var(--caf-muted);
            font-size: 13px;
        }

        .caf-search {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 14px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-bg);
        }

        .caf-search i {
            color: var(--caf-primary);
            font-size: 16px;
        }

        .caf-search input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--caf-text);
            font-size: 14px;
        }

        .caf-service-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .caf-service-card {
            display: flex;
            flex-direction: column;
            min-height: 244px;
            padding: 18px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-surface);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .caf-service-card:hover {
            border-color: #bae6fd;
            box-shadow: 0 18px 42px rgba(14, 165, 233, .10);
            transform: translateY(-2px);
        }

        .caf-service-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
        }

        .caf-service-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--caf-soft);
            color: var(--caf-primary-dark);
        }

        .caf-service-status {
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .caf-service-status.is-ready {
            background: #dcfce7;
            color: #15803d;
        }

        .caf-service-status.is-blocked {
            background: #ffe4e6;
            color: #be123c;
        }

        .caf-service-card h3 {
            min-height: 46px;
            margin: 0 0 16px;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.45;
        }

        .caf-service-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 18px;
        }

        .caf-service-meta div {
            padding: 12px;
            border-radius: 8px;
            background: var(--caf-bg);
        }

        .caf-service-meta span {
            display: block;
            color: var(--caf-muted);
            font-size: 11px;
            font-weight: 700;
        }

        .caf-service-meta strong {
            display: block;
            margin-top: 4px;
            font-size: 15px;
            font-weight: 800;
        }

        .caf-service-action {
            margin-top: auto;
        }

        .caf-empty-state {
            margin-top: 16px;
            padding: 30px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #fff;
            text-align: center;
        }

        .caf-empty-state i,
        .caf-empty-state strong,
        .caf-empty-state span {
            display: block;
        }

        .caf-empty-state i {
            color: var(--caf-primary);
            font-size: 24px;
            margin-bottom: 8px;
        }

        .caf-empty-state span {
            margin-top: 4px;
            color: var(--caf-muted);
        }

        @media (max-width: 1399.98px) {
            .caf-service-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .caf-create-hero,
            .caf-service-toolbar {
                grid-template-columns: 1fr;
            }

            .caf-service-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .caf-create-hero,
            .caf-service-toolbar {
                padding: 18px;
            }

            .caf-create-hero h1 {
                font-size: 24px;
            }

            .caf-service-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('customScript')
    <script>
        (function () {
            function normalize(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();
            }

            function filterCafServices(input) {
                const page = input.closest('.caf-create-page');
                if (! page) {
                    return;
                }

                const value = normalize(input.value);
                const cards = page.querySelectorAll('.caf-service-card');
                const empty = page.querySelector('#caf-empty-state');
                let visible = 0;

                cards.forEach(function (card) {
                    const haystack = normalize((card.dataset.serviceName || '') + ' ' + card.textContent);
                    const match = value === '' || haystack.includes(value);
                    card.classList.toggle('d-none', !match);
                    if (match) {
                        visible++;
                    }
                });

                if (empty) {
                    empty.classList.toggle('d-none', visible > 0);
                }
            }

            document.addEventListener('input', function (event) {
                if (event.target && event.target.id === 'caf-service-search') {
                    filterCafServices(event.target);
                }
            });

            document.addEventListener('keyup', function (event) {
                if (event.target && event.target.id === 'caf-service-search') {
                    filterCafServices(event.target);
                }
            });

            document.addEventListener('click', function (event) {
                const button = event.target.closest('.caf-service-action .btn-primary');
                if (! button) {
                    return;
                }

                button.classList.add('disabled');
                button.setAttribute('aria-disabled', 'true');
                button.textContent = 'Apro pratica...';
            });

            const existingSearch = document.getElementById('caf-service-search');
            if (existingSearch) {
                filterCafServices(existingSearch);
            }
        })();
    </script>
@endpush
