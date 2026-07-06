<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $metaTitle ?? 'Gestiio AG SERVIZI' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Portale agenti e collaboratori AG SERVIZI.' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="{{ $metaTitle ?? 'Gestiio AG SERVIZI' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Portale agenti e collaboratori AG SERVIZI.' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:image" content="{{ url('/images/screen_1.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700,800">
    <link href="/assets_backend/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="/assets_backend/css/style10.bundle.css" rel="stylesheet" type="text/css">
    <style>
        :root {
            --ag-ink: #07111f;
            --ag-muted: #5d6b82;
            --ag-line: #dce7f3;
            --ag-blue: #0795e8;
            --ag-blue-dark: #045f9b;
            --ag-green: #16a36f;
            --ag-soft: #f4f8fc;
            --ag-card: #ffffff;
        }

        body {
            margin: 0;
            color: var(--ag-ink);
            background: #ffffff;
            font-family: Inter, Arial, sans-serif;
        }

        .site-shell {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .public-nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid rgba(220, 231, 243, .9);
            backdrop-filter: blur(14px);
        }

        .public-nav-inner {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--ag-ink);
            font-weight: 800;
        }

        .brand-mark img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .nav-actions a {
            white-space: nowrap;
        }

        .btn-ag-primary {
            background: var(--ag-blue);
            color: #fff;
            border: 1px solid var(--ag-blue);
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
        }

        .btn-ag-primary:hover {
            background: var(--ag-blue-dark);
            color: #fff;
        }

        .btn-ag-secondary {
            color: var(--ag-ink);
            border: 1px solid var(--ag-line);
            background: #fff;
            border-radius: 8px;
            padding: 12px 18px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
        }

        .hero {
            position: relative;
            min-height: calc(100vh - 74px);
            display: grid;
            align-items: center;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, .98) 0%, rgba(255, 255, 255, .9) 50%, rgba(255, 255, 255, .58) 100%),
                url('/images/screen_1.png') right center / min(760px, 55vw) auto no-repeat,
                #ffffff;
        }

        .section-inner {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .hero-copy {
            max-width: 660px;
            padding: 64px 0 92px;
        }

        .eyebrow {
            color: var(--ag-blue-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        h1 {
            font-size: clamp(42px, 6vw, 76px);
            line-height: .95;
            letter-spacing: 0;
            font-weight: 800;
            margin: 0 0 22px;
        }

        .lead {
            color: var(--ag-muted);
            font-size: 19px;
            line-height: 1.65;
            max-width: 610px;
            margin: 0 0 30px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .trust-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            color: var(--ag-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .trust-row img {
            height: 26px;
            max-width: 110px;
            object-fit: contain;
            filter: grayscale(1);
            opacity: .72;
        }

        .band {
            padding: 78px 0;
        }

        .band-soft {
            background: var(--ag-soft);
        }

        .section-heading {
            max-width: 780px;
            margin-bottom: 30px;
        }

        .section-heading h2 {
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.08;
            letter-spacing: 0;
            font-weight: 800;
            margin: 0 0 14px;
        }

        .section-heading p {
            color: var(--ag-muted);
            font-size: 17px;
            line-height: 1.6;
            margin: 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .feature-card {
            background: var(--ag-card);
            border: 1px solid var(--ag-line);
            border-radius: 8px;
            padding: 24px;
            min-height: 168px;
        }

        .feature-card strong {
            display: block;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: var(--ag-muted);
            line-height: 1.55;
            margin: 0;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            counter-reset: steps;
        }

        .step {
            border-left: 3px solid var(--ag-blue);
            padding: 4px 18px 4px 18px;
            counter-increment: steps;
        }

        .step::before {
            content: counter(steps, decimal-leading-zero);
            display: block;
            color: var(--ag-green);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .step strong {
            display: block;
            margin-bottom: 6px;
        }

        .step span {
            color: var(--ag-muted);
            line-height: 1.5;
        }

        .cta-band {
            background: var(--ag-ink);
            color: #fff;
            padding: 58px 0;
        }

        .cta-band .section-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
        }

        .cta-band h2 {
            font-size: clamp(28px, 4vw, 42px);
            margin: 0 0 10px;
            letter-spacing: 0;
        }

        .cta-band p {
            color: rgba(255, 255, 255, .72);
            margin: 0;
            font-size: 16px;
            line-height: 1.55;
        }

        .public-footer {
            padding: 28px 0;
            border-top: 1px solid var(--ag-line);
            color: var(--ag-muted);
            font-size: 14px;
        }

        .public-footer .section-inner {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .public-footer a {
            color: var(--ag-muted);
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .hero {
                min-height: auto;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .92)),
                    url('/images/screen_1.png') center bottom / 760px auto no-repeat,
                    #ffffff;
            }

            .hero-copy {
                padding: 52px 0 320px;
            }

            .feature-grid,
            .steps {
                grid-template-columns: 1fr;
            }

            .cta-band .section-inner,
            .public-nav-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-actions {
                width: 100%;
            }

            .nav-actions a {
                flex: 1 1 0;
            }
        }

        @media (max-width: 520px) {
            h1 {
                font-size: 40px;
            }

            .hero-copy {
                padding-bottom: 260px;
            }

            .hero-actions,
            .nav-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
    @stack('pageCss')
    @if(! empty($schema))
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
</head>
<body>
<div class="site-shell">
    <nav class="public-nav" aria-label="Navigazione principale">
        <div class="public-nav-inner">
            <a class="brand-mark" href="{{ url('/') }}" aria-label="Gestiio AG SERVIZI">
                <img src="/loghi/logo.png" alt="Gestiio">
                <span>Gestiio</span>
            </a>
            <div class="nav-actions">
                <a class="btn-ag-secondary" href="{{ url('/collabora-con-ag-servizi') }}">Collabora</a>
                <a class="btn-ag-secondary" href="{{ route('login') }}">Accedi</a>
                <a class="btn-ag-primary" href="{{ route('register') }}">Registrati agente</a>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="public-footer">
        <div class="section-inner">
            <span>AG SERVIZI VIA PLINIO 72 - Gestiio</span>
            <span><a href="/policies">Termini e condizioni</a> · <a href="{{ route('login') }}">Accesso piattaforma</a></span>
        </div>
    </footer>
</div>
</body>
</html>
