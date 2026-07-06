@extends('Frontend.Marketing._layout')

@push('pageCss')
    <style>
        .collab-page {
            --collab-ink: #07111f;
            --collab-blue: #0795e8;
            --collab-cyan: #5ed4ff;
            --collab-green: #14b67b;
            --collab-soft: #eef6fb;
            background: #fbfdff;
        }

        .collab-hero {
            position: relative;
            min-height: calc(100dvh - 74px);
            overflow: hidden;
            display: grid;
            align-items: center;
            background:
                linear-gradient(115deg, rgba(7, 17, 31, .98) 0%, rgba(7, 28, 47, .94) 46%, rgba(9, 87, 135, .78) 100%),
                url('/images/screen_1.png') right center / min(860px, 58vw) auto no-repeat;
            color: #fff;
        }

        .collab-hero .section-inner {
            width: min(1500px, calc(100% - 96px));
        }

        .collab-hero::before {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 190px;
            background: linear-gradient(180deg, rgba(251, 253, 255, 0), #fbfdff 82%);
            pointer-events: none;
        }

        .collab-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(520px, 660px) minmax(520px, 1fr);
            align-items: center;
            gap: clamp(28px, 5vw, 88px);
            padding: 64px 0 96px;
        }

        .collab-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 13px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            color: rgba(255, 255, 255, .86);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
            backdrop-filter: blur(18px);
        }

        .collab-kicker span {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--collab-green);
            box-shadow: 0 0 0 6px rgba(20, 182, 123, .16);
        }

        .collab-hero h1 {
            max-width: 650px;
            margin: 24px 0 22px;
            color: #fff;
            font-size: clamp(48px, 5.4vw, 76px);
            line-height: .96;
            letter-spacing: 0;
        }

        .collab-hero h1 span {
            display: block;
        }

        .collab-lead {
            max-width: 560px;
            color: rgba(255, 255, 255, .74);
            font-size: clamp(18px, 1.7vw, 22px);
            line-height: 1.58;
            margin: 0 0 32px;
        }

        .collab-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .collab-primary,
        .collab-secondary {
            border-radius: 999px;
            min-height: 50px;
            padding: 13px 22px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }

        .collab-primary {
            color: #06111d;
            background: #fff;
            border: 1px solid #fff;
        }

        .collab-secondary {
            color: #fff;
            background: rgba(255, 255, 255, .09);
            border: 1px solid rgba(255, 255, 255, .22);
        }

        .collab-primary:hover,
        .collab-secondary:hover {
            transform: translateY(-2px);
        }

        .collab-secondary:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, .42);
        }

        .collab-screen {
            position: relative;
            min-height: 540px;
            justify-self: stretch;
        }

        .collab-screen-main {
            position: absolute;
            inset: 28px 0 auto auto;
            width: min(660px, 100%);
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .12);
            box-shadow: 0 38px 90px rgba(0, 0, 0, .34);
            transform: rotate(-2deg);
        }

        .collab-screen-main img {
            width: 100%;
            display: block;
        }

        .collab-panel {
            position: absolute;
            left: clamp(12px, 4vw, 72px);
            bottom: 24px;
            width: min(340px, 88%);
            border-radius: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, .92);
            color: var(--collab-ink);
            box-shadow: 0 28px 72px rgba(0, 0, 0, .22);
            backdrop-filter: blur(20px);
        }

        .collab-panel strong {
            display: block;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .collab-panel p {
            margin: 0;
            color: #587087;
            line-height: 1.5;
        }

        .collab-flow {
            padding: 36px 0 86px;
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }

        .collab-flow-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border-radius: 24px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #dbe8f3;
            box-shadow: 0 24px 70px rgba(20, 58, 92, .12);
        }

        .flow-item {
            padding: 24px;
            border-right: 1px solid #dbe8f3;
        }

        .flow-item:last-child {
            border-right: 0;
        }

        .flow-item small {
            display: block;
            color: var(--collab-blue);
            font-weight: 800;
            margin-bottom: 9px;
        }

        .flow-item strong {
            display: block;
            font-size: 17px;
            margin-bottom: 8px;
        }

        .flow-item span {
            color: #63758a;
            line-height: 1.48;
        }

        .collab-story {
            padding: 28px 0 90px;
        }

        .story-grid {
            display: grid;
            grid-template-columns: minmax(280px, .8fr) minmax(0, 1fr);
            gap: 44px;
            align-items: start;
        }

        .story-copy h2,
        .toolkit-head h2 {
            font-size: clamp(34px, 5vw, 60px);
            line-height: .98;
            letter-spacing: 0;
            margin: 0 0 18px;
            color: var(--collab-ink);
        }

        .story-copy p,
        .toolkit-head p {
            color: #5f7186;
            font-size: 18px;
            line-height: 1.62;
            margin: 0;
        }

        .story-stack {
            display: grid;
            gap: 14px;
        }

        .story-row {
            display: grid;
            grid-template-columns: 132px 1fr;
            gap: 18px;
            align-items: start;
            padding: 22px 0;
            border-top: 1px solid #dce9f3;
        }

        .story-row:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .story-row span {
            color: var(--collab-blue);
            font-weight: 800;
        }

        .story-row strong {
            display: block;
            color: var(--collab-ink);
            font-size: 20px;
            margin-bottom: 7px;
        }

        .story-row p {
            color: #607489;
            line-height: 1.58;
            margin: 0;
        }

        .collab-toolkit {
            padding: 88px 0;
            background:
                linear-gradient(180deg, rgba(238, 246, 251, .92), rgba(251, 253, 255, 1)),
                url('/images/screen_2.png') right 80px / min(640px, 46vw) auto no-repeat;
        }

        .toolkit-head {
            max-width: 720px;
            margin-bottom: 34px;
        }

        .toolkit-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 18px;
            max-width: 880px;
        }

        .toolkit-tile {
            min-height: 192px;
            padding: 26px;
            border-radius: 22px;
            background: rgba(255, 255, 255, .88);
            border: 1px solid #dce8f2;
            box-shadow: 0 20px 60px rgba(16, 70, 116, .08);
        }

        .toolkit-tile:nth-child(3) {
            grid-column: span 2;
            background:
                linear-gradient(120deg, rgba(255, 255, 255, .92), rgba(234, 247, 255, .88)),
                url('/images/screen_3.png') right center / min(430px, 42%) auto no-repeat;
        }

        .toolkit-tile strong {
            display: block;
            max-width: 430px;
            color: var(--collab-ink);
            font-size: 22px;
            margin-bottom: 10px;
        }

        .toolkit-tile p {
            max-width: 480px;
            color: #61758a;
            line-height: 1.58;
            margin: 0;
        }

        .collab-final {
            padding: 92px 0;
            background: #fbfdff;
        }

        .final-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            padding: 38px;
            border-radius: 26px;
            background:
                linear-gradient(120deg, rgba(7, 17, 31, .97), rgba(7, 84, 132, .9)),
                url('/images/screen_4.png') right center / min(560px, 48vw) auto no-repeat;
            color: #fff;
            overflow: hidden;
        }

        .final-panel h2 {
            font-size: clamp(30px, 4.2vw, 54px);
            line-height: 1;
            letter-spacing: 0;
            margin: 0 0 12px;
            max-width: 720px;
        }

        .final-panel p {
            color: rgba(255, 255, 255, .74);
            margin: 0;
            max-width: 620px;
            font-size: 17px;
            line-height: 1.6;
        }

        @media (prefers-reduced-motion: no-preference) {
            .collab-screen-main {
                animation: collabFloat 7s ease-in-out infinite;
            }

            .collab-panel {
                animation: collabFloat 8s ease-in-out infinite reverse;
            }

            .flow-item,
            .story-row,
            .toolkit-tile {
                transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            }

            .flow-item:hover,
            .story-row:hover,
            .toolkit-tile:hover {
                transform: translateY(-4px);
            }
        }

        @keyframes collabFloat {
            0%, 100% {
                transform: translateY(0) rotate(-2deg);
            }
            50% {
                transform: translateY(-10px) rotate(-1deg);
            }
        }

        @media (max-width: 1120px) {
            .collab-hero .section-inner {
                width: min(1180px, calc(100% - 32px));
            }

            .collab-hero-grid,
            .story-grid,
            .toolkit-grid,
            .final-panel {
                grid-template-columns: 1fr;
            }

            .collab-hero-grid {
                padding-top: 48px;
            }

            .collab-screen {
                min-height: 380px;
            }

            .collab-screen-main {
                width: 100%;
                right: -18px;
            }

            .collab-flow-strip {
                grid-template-columns: 1fr 1fr;
            }

            .flow-item:nth-child(2) {
                border-right: 0;
            }

            .flow-item:nth-child(n+3) {
                border-top: 1px solid #dbe8f3;
            }

            .collab-toolkit {
                background:
                    linear-gradient(180deg, rgba(238, 246, 251, .94), rgba(251, 253, 255, 1)),
                    url('/images/screen_2.png') center bottom / 760px auto no-repeat;
                padding-bottom: 320px;
            }

            .toolkit-tile:nth-child(3) {
                grid-column: auto;
            }
        }

        @media (max-width: 640px) {
            .collab-hero h1 {
                font-size: 42px;
            }

            .collab-actions,
            .final-panel .collab-primary {
                width: 100%;
            }

            .collab-primary,
            .collab-secondary {
                width: 100%;
            }

            .collab-flow-strip {
                grid-template-columns: 1fr;
            }

            .flow-item {
                border-right: 0;
                border-top: 1px solid #dbe8f3;
            }

            .flow-item:first-child {
                border-top: 0;
            }

            .story-row {
                grid-template-columns: 1fr;
            }

            .collab-panel {
                left: 8px;
                bottom: 8px;
            }

            .collab-screen {
                min-height: 330px;
            }

            .final-panel {
                padding: 28px;
            }
        }
    </style>
@endpush

@section('content')
    <main class="collab-page">
        <section class="collab-hero">
            <div class="section-inner">
                <div class="collab-hero-grid">
                    <div>
                        <div class="collab-kicker"><span></span>Collaborazione agenti AG SERVIZI</div>
                        <h1><span>Lavora con AG SERVIZI.</span><span>Senza perdere pezzi per strada.</span></h1>
                        <p class="collab-lead">Contratti, documenti, ticket, visure, spedizioni e plafond finiscono in un unico posto. Tu lavori. Il portale tiene il filo.</p>
                        <div class="collab-actions">
                            <a class="collab-primary" href="{{ route('register') }}">Registrati come agente</a>
                            <a class="collab-secondary" href="{{ route('login') }}">Ho gia un account</a>
                        </div>
                    </div>
                    <div class="collab-screen" aria-hidden="true">
                        <div class="collab-screen-main">
                            <img src="/images/screen_1.png" alt="">
                        </div>
                        <div class="collab-panel">
                            <strong>Un cruscotto, non una chat infinita.</strong>
                            <p>Ogni pratica resta visibile: stato, allegati, richieste e prossima azione.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="collab-flow">
            <div class="section-inner">
                <div class="collab-flow-strip" aria-label="Percorso collaborazione">
                    <div class="flow-item"><small>01</small><strong>Ti registri</strong><span>Inserisci i dati agente e crei l'accesso alla piattaforma.</span></div>
                    <div class="flow-item"><small>02</small><strong>AG SERVIZI riceve tutto</strong><span>La richiesta arriva al team, con i dati pronti per la verifica.</span></div>
                    <div class="flow-item"><small>03</small><strong>Inizi a lavorare</strong><span>Usi dashboard, pratiche, ticket e documenti dal tuo spazio.</span></div>
                    <div class="flow-item"><small>04</small><strong>Resti allineato</strong><span>Notifiche e storico riducono messaggi persi e richieste duplicate.</span></div>
                </div>
            </div>
        </section>

        <section class="collab-story">
            <div class="section-inner story-grid">
                <div class="story-copy">
                    <h2>Per chi lavora ogni giorno tra pratiche e clienti.</h2>
                    <p>Se oggi stai usando chat, fogli, email e cartelle separate, Gestiio serve a mettere ordine senza rendere il lavoro piu pesante.</p>
                </div>
                <div class="story-stack">
                    <div class="story-row"><span>Agenti</span><div><strong>Vedi cosa succede dopo l'invio.</strong><p>Contratti, esiti, allegati e ticket restano collegati alla pratica. Non devi ricostruire lo storico ogni volta.</p></div></div>
                    <div class="story-row"><span>Agenzie partner</span><div><strong>Coordini piu lavorazioni con meno rumore.</strong><p>Le richieste operative passano dal portale, con stati e documenti nello stesso ambiente.</p></div></div>
                    <div class="story-row"><span>Collaboratori</span><div><strong>Hai una base comune con il team AG SERVIZI.</strong><p>Quando serve supporto, la conversazione parte gia dal contesto giusto.</p></div></div>
                </div>
            </div>
        </section>

        <section class="collab-toolkit">
            <div class="section-inner">
                <div class="toolkit-head">
                    <h2>Dentro trovi gli strumenti che usi davvero.</h2>
                    <p>Non una vetrina. Un ambiente operativo per seguire lavoro, richieste e credito disponibile.</p>
                </div>
                <div class="toolkit-grid">
                    <article class="toolkit-tile"><strong>Contratti telefonia ed energia</strong><p>Inserimento, allegati, controlli e avanzamento pratica in un percorso leggibile.</p></article>
                    <article class="toolkit-tile"><strong>CAF, patronato e visure</strong><p>Servizi documentali e richieste gestite con meno passaggi fuori piattaforma.</p></article>
                    <article class="toolkit-tile"><strong>Ticket, documenti, spedizioni e plafond</strong><p>Quando devi chiedere supporto, caricare file, controllare un movimento o seguire una spedizione, non cambi strumento.</p></article>
                </div>
            </div>
        </section>

        <section class="collab-final">
            <div class="section-inner">
                <div class="final-panel">
                    <div>
                        <h2>Se vuoi collaborare con AG SERVIZI, parti da qui.</h2>
                        <p>Compila la registrazione agente. Il team riceve la richiesta e tu accedi al portale Gestiio per iniziare a lavorare in modo tracciato.</p>
                    </div>
                    <a class="collab-primary" href="{{ route('register') }}">Crea account agente</a>
                </div>
            </div>
        </section>
    </main>
@endsection
