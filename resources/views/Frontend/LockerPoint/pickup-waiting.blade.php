<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>In attesa — {{ $package->code }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,600,700">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Inter, Arial, sans-serif;
            background: #f4f7fb;
            color: #07111f;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 32px 24px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 12px 40px rgba(7, 17, 31, .08);
            border: 1px solid #e4ebf3;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fff4de;
            color: #9a6700;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        h1 { margin: 0 0 8px; font-size: 1.45rem; }
        .code {
            font-family: ui-monospace, monospace;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: .06em;
            margin: 12px 0;
        }
        p { color: #5d6b82; line-height: 1.5; margin: 8px 0; }
        .meta { margin-top: 20px; font-size: .9rem; }
        a.refresh {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            border-radius: 12px;
            background: #0795e8;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">{{ $package->status->label() }}</div>
        <h1>Pacco non ancora in giacenza</h1>
        <div class="code">{{ $package->code }}</div>
        <p>
            Il ritiro mobile sarà disponibile dopo l’accettazione in sportello
            (foto del pacco e passaggio a <strong>In giacenza</strong>).
        </p>
        <div class="meta">
            <div><strong>{{ $package->recipient_name }}</strong></div>
            @if($package->expected_pickup_date)
                <div>Ritiro previsto: {{ $package->expected_pickup_date->format('d/m/Y') }}</div>
            @endif
            @if($package->carrier || $package->tracking_code)
                <div>
                    {{ $package->carrier ?: 'Corriere' }}
                    @if($package->tracking_code) · {{ $package->tracking_code }} @endif
                </div>
            @endif
        </div>
        <a class="refresh" href="{{ url()->current().'?t='.urlencode($token) }}">Aggiorna stato</a>
    </div>
</body>
</html>
