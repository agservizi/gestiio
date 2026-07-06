@extends('Frontend.Marketing._layout')

@section('content')
    <main>
        <section class="hero">
            <div class="section-inner">
                <div class="hero-copy">
                    <div class="eyebrow">Portale agenti AG SERVIZI</div>
                    <h1>Collabora con AG SERVIZI dentro un sistema tracciato.</h1>
                    <p class="lead">Gestiio e il portale per agenti e partner che vogliono lavorare con AG SERVIZI su pratiche, contratti, ticket, documenti, spedizioni e plafond operativo.</p>
                    <div class="hero-actions">
                        <a class="btn-ag-primary" href="{{ route('register') }}">Crea account agente</a>
                        <a class="btn-ag-secondary" href="{{ url('/collabora-con-ag-servizi') }}">Scopri come funziona</a>
                    </div>
                    <div class="trust-row" aria-label="Servizi gestiti in piattaforma">
                        <span>Telefonia</span>
                        <span>Energia</span>
                        <span>CAF / Patronato</span>
                        <span>Visure</span>
                        <img src="/images/Vodafone_Logo.png" alt="Vodafone">
                        <img src="/images/fastweb.png" alt="Fastweb">
                        <img src="/loghi/Logo_BRT.png" alt="BRT">
                    </div>
                </div>
            </div>
        </section>

        <section class="band band-soft">
            <div class="section-inner">
                <div class="section-heading">
                    <h2>Un unico posto per lavorare, seguire e comunicare.</h2>
                    <p>La piattaforma mette insieme le attivita operative che un collaboratore deve gestire ogni giorno, senza disperdere richieste e documenti in canali separati.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <strong>Pratiche e contratti</strong>
                        <p>Telefonia, energia, CAF/patronato, visure e servizi collegati con stati e lavorazioni consultabili.</p>
                    </article>
                    <article class="feature-card">
                        <strong>Ticket e notifiche</strong>
                        <p>Richieste operative tracciate, aggiornamenti in piattaforma e comunicazioni meno disperse.</p>
                    </article>
                    <article class="feature-card">
                        <strong>Documenti e plafond</strong>
                        <p>File condivisi, portafogli servizi/visure e movimenti consultabili nello stesso ecosistema.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="band">
            <div class="section-inner">
                <div class="section-heading">
                    <h2>Come iniziare la collaborazione con AG SERVIZI</h2>
                    <p>Il percorso e diretto: registrazione, verifica, accesso al portale e gestione delle prime attivita operative.</p>
                </div>
                <div class="steps">
                    <div class="step"><strong>Registrati</strong><span>Compila il form agente con dati personali, contatti e dati operativi.</span></div>
                    <div class="step"><strong>Entra nel portale</strong><span>Dopo la registrazione accedi al tuo spazio e alle funzioni disponibili.</span></div>
                    <div class="step"><strong>Gestisci pratiche</strong><span>Inserisci richieste, segui stati, allega documenti e apri ticket quando serve.</span></div>
                    <div class="step"><strong>Resta allineato</strong><span>Notifiche, chat interna e dashboard riducono passaggi manuali e messaggi persi.</span></div>
                </div>
            </div>
        </section>

        <section class="cta-band">
            <div class="section-inner">
                <div>
                    <h2>Pronto a lavorare con AG SERVIZI?</h2>
                    <p>Apri il tuo account agente e porta le pratiche dentro un flusso operativo piu chiaro.</p>
                </div>
                <a class="btn-ag-primary" href="{{ route('register') }}">Registrati agente</a>
            </div>
        </section>
    </main>
@endsection
