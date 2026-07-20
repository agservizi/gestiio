<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Fermo deposito {{ $package->code }}</title>
    <style>
        @page {
            margin: 0;
            size: {{ $paperWidthMm ?? 105 }}mm {{ $paperHeightMm ?? 148 }}mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            background: #fff;
        }

        .sheet {
            width: {{ $paperWidthMm ?? 105 }}mm;
            border: 0.5mm solid #000;
            page-break-inside: avoid;
        }

        .masthead {
            background: #000;
            color: #fff;
            padding: 2mm 3mm 1.8mm;
            text-align: center;
        }
        .masthead-kicker {
            font-size: 5.5pt;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .masthead-title {
            margin-top: 0.5mm;
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            line-height: 1.05;
        }
        .masthead-sub {
            margin-top: 0.5mm;
            font-size: 5.5pt;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #ccc;
        }

        .band {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 5.8pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 1.1mm 2mm;
        }

        .hero {
            padding: 2mm 3mm 1.6mm;
            border-bottom: 0.35mm solid #000;
            text-align: center;
        }
        .hero-label {
            font-size: 5.5pt;
            letter-spacing: 1.1px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .hero-code {
            margin-top: 0.6mm;
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 0.8px;
            line-height: 1;
            font-family: DejaVu Sans Mono, monospace;
        }

        .block {
            padding: 1.6mm 3mm;
            border-bottom: 0.3mm solid #000;
        }
        .block-label {
            font-size: 5.2pt;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 0.4mm;
        }
        .block-value {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1.1;
        }
        .block-meta {
            margin-top: 0.4mm;
            font-size: 7pt;
            line-height: 1.2;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 1.4mm 2.8mm;
            border-bottom: 0.3mm solid #000;
        }
        .grid td + td { border-left: 0.3mm solid #000; }
        .grid .block-value { font-size: 8.5pt; }

        .bc-wrap {
            padding: 1.5mm 3mm 1.2mm;
            border-bottom: 0.3mm solid #000;
        }
        .bc-hint {
            font-size: 5.2pt;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
            margin-bottom: 0.8mm;
        }
        .bc-block { width: 100%; }
        .bc-frame {
            border: 0.4mm solid #000;
            padding: 1.2mm 1.8mm 1mm;
            text-align: center;
        }
        .bc-img {
            width: 100%;
            max-width: 88mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .bc-hri {
            margin-top: 0.6mm;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            font-family: DejaVu Sans Mono, monospace;
        }
        .bc-type {
            margin-top: 0.2mm;
            font-size: 4.8pt;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #333;
        }

        .notice {
            padding: 1.4mm 3mm;
            border-bottom: 0.3mm solid #000;
            font-size: 5.8pt;
            line-height: 1.25;
            text-align: center;
        }
        .notice strong {
            display: block;
            font-size: 5.8pt;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            margin-bottom: 0.3mm;
        }

        .footer {
            padding: 1.2mm 3mm 1.4mm;
            font-size: 5.2pt;
            letter-spacing: 0.2px;
            text-align: center;
            text-transform: uppercase;
        }
        .footer-code {
            font-weight: bold;
            font-family: DejaVu Sans Mono, monospace;
            letter-spacing: 0.6px;
        }
    </style>
</head>
<body>
@php
    $statusLabel = $package->status?->label() ?? (string) $package->status;
    $pickup = $package->expected_pickup_date?->format('d/m/Y') ?? '—';
    $carrier = trim((string) ($package->carrier ?: ''));
    $tracking = trim((string) ($package->tracking_code ?: ''));
    $sender = trim((string) ($package->sender_name ?: ''));
    $phone = trim((string) ($package->recipient_phone ?: ''));
    $stationName = $package->station?->name;
@endphp
<div class="sheet">
    <div class="masthead">
        <div class="masthead-kicker">Servizio depositi · Custody hold</div>
        <div class="masthead-title">Fermo deposito</div>
        <div class="masthead-sub">Locker Point · etichetta adesiva A6</div>
    </div>

    <div class="band">Attaccare sul pacco · Non rimuovere fino al ritiro</div>

    <div class="hero">
        <div class="hero-label">Codice ritiro · Scan ID</div>
        <div class="hero-code">{{ $package->code }}</div>
    </div>

    <div class="block">
        <div class="block-label">Destinatario / Recipient</div>
        <div class="block-value">{{ $package->recipient_name }}</div>
        @if($phone !== '')
            <div class="block-meta">Tel. {{ $phone }}</div>
        @endif
    </div>

    <table class="grid" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="block-label">Ritiro previsto</div>
                <div class="block-value">{{ $pickup }}</div>
            </td>
            <td>
                <div class="block-label">Stato</div>
                <div class="block-value">{{ $statusLabel }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="block-label">Corriere</div>
                <div class="block-value">{{ $carrier !== '' ? $carrier : '—' }}</div>
            </td>
            <td>
                <div class="block-label">Tracking</div>
                <div class="block-value" style="font-size:7pt;">{{ $tracking !== '' ? $tracking : '—' }}</div>
            </td>
        </tr>
    </table>

    @if($sender !== '' || $stationName)
        <div class="block">
            @if($sender !== '')
                <div class="block-label">Mittente</div>
                <div class="block-value" style="font-size:8.5pt;">{{ $sender }}</div>
            @endif
            @if($stationName)
                <div class="block-meta"><strong>Postazione:</strong> {{ $stationName }}</div>
            @endif
        </div>
    @endif

    <div class="bc-wrap">
        <div class="bc-hint">Barcode accettazione / consegna</div>
        @include('Backend.LuggageDeposit.pdf._barcode', [
            'value' => $package->code,
            'caption' => $package->code,
            'height' => 34,
            'module' => 2,
        ])
    </div>

    <div class="notice">
        <strong>Istruzioni operatore</strong>
        Giacenza fino al ritiro. Consegna: scan codice + firma.
    </div>

    <div class="footer">
        Stampato {{ $printedAt ?? now()->format('d/m/Y H:i') }}
        · <span class="footer-code">{{ $package->code }}</span>
        · A6 105×148 mm
    </div>
</div>
</body>
</html>
