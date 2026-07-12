<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Baggage tag {{ $deposit->code }}</title>
    @include('Backend.LuggageDeposit.pdf._styles')
    <style>
        @page { margin: 0; size: {{ $paperWidthMm ?? 80 }}mm {{ $paperHeightMm ?? 330 }}mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; margin: 0; padding: 0; background: #fff; }

        .en { font-weight: bold; }
        .it-sub {
            display: block;
            font-size: 85%;
            font-weight: normal;
            color: #555;
            letter-spacing: 0.2px;
            text-transform: none;
        }
        .it-sub.inline { display: inline; font-size: 90%; }

        .sheet {
            width: {{ $paperWidthMm ?? 80 }}mm;
            min-height: {{ $paperHeightMm ?? 330 }}mm;
            page-break-after: always;
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
        }
        .sheet:last-child { page-break-after: auto; }

        .masthead {
            background: #000;
            color: #fff;
            padding: 3mm 3.5mm 2.5mm;
        }
        .masthead-sub {
            font-size: 8pt;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
            line-height: 1.25;
        }
        .masthead-sub .it-sub {
            color: #ccc;
            font-size: 6.5pt;
            letter-spacing: 1px;
            margin-top: 0.5mm;
        }

        .stripe {
            height: 3.5mm;
            background: #000;
            border-bottom: 0.5mm solid #000;
        }

        .route-row {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 0.5mm solid #000;
        }
        .route-row td {
            width: 33.33%;
            text-align: center;
            padding: 2.5mm 1mm;
            border-right: 0.35mm solid #000;
            vertical-align: middle;
        }
        .route-row td:last-child { border-right: 0; }
        .route-lbl {
            font-size: 6pt;
            letter-spacing: 1.5px;
            color: #222;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .route-lbl .it-sub { font-size: 5pt; color: #666; letter-spacing: 0.5px; }
        .route-val { margin-top: 0.8mm; font-size: 13pt; font-weight: bold; letter-spacing: 1px; }

        .hero {
            padding: 3.5mm 3.5mm 2.5mm;
            border-bottom: 0.35mm solid #000;
        }
        .hero-label {
            font-size: 6.5pt;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #111;
            font-weight: bold;
            line-height: 1.25;
        }
        .hero-label .it-sub { font-size: 5.5pt; color: #555; letter-spacing: 0.5px; }
        .hero-code {
            margin-top: 1mm;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 0.8px;
            line-height: 1;
            white-space: nowrap;
        }
        .hero-customer {
            margin-top: 2mm;
            padding: 2mm 2.5mm;
            border: 0.45mm solid #000;
            background: #000;
            color: #fff;
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bc-block { width: 100%; }
        .bc-frame {
            padding: 2mm 2.5mm 1.5mm;
            border: 0.55mm solid #000;
            text-align: center;
            background: #fff;
        }
        .bc-img { width: 100%; max-width: 68mm; height: auto; display: block; margin: 0 auto; }
        .bc-hri {
            margin-top: 1.2mm;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 2.5px;
            font-family: DejaVu Sans Mono, monospace;
            white-space: nowrap;
        }
        .bc-type {
            margin-top: 0.6mm;
            font-size: 5.5pt;
            letter-spacing: 2px;
            color: #444;
            text-transform: uppercase;
        }

        .barcode-hero { margin-top: 3mm; }

        .receipt {
            padding: 2.5mm 3.5mm;
            border-bottom: 0.35mm dashed #666;
        }
        .receipt-title {
            font-size: 6.5pt;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 1.5mm;
            line-height: 1.25;
        }
        .receipt-title .it-sub { font-size: 5.5pt; color: #555; letter-spacing: 0.5px; }
        .receipt-table { width: 100%; border-collapse: collapse; }
        .receipt-table td {
            font-size: 7.5pt;
            padding: 1.1mm 0;
            vertical-align: top;
        }
        .receipt-table .k {
            width: 42%;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-size: 6pt;
            line-height: 1.2;
        }
        .receipt-table .k .it-sub { font-size: 5pt; color: #666; text-transform: none; }
        .receipt-table .v { font-weight: bold; text-align: right; font-size: 7pt; }
        .status-pill {
            display: inline-block;
            padding: 0.8mm 2mm;
            border: 0.4mm solid #000;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
            text-align: right;
        }
        .status-pill .it-sub { font-size: 5pt; color: #333; text-transform: none; }

        .perf {
            text-align: center;
            font-size: 5.8pt;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #111;
            padding: 2mm 2mm;
            border-top: 0.45mm dashed #000;
            border-bottom: 0.45mm dashed #000;
            background: #f2f2f2;
            line-height: 1.3;
        }
        .perf .it-sub { font-size: 5pt; color: #555; text-transform: none; letter-spacing: 0.3px; }

        .stem { width: 100%; border-collapse: collapse; }
        .stem td { vertical-align: top; padding: 0; }
        .stem-side {
            width: 10mm;
            background: #000;
            color: #fff;
            text-align: center;
            padding: 2.5mm 0;
        }
        .stem-char {
            display: block;
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.1;
            letter-spacing: 0.3px;
        }
        .stem-main { padding: 2.5mm 3mm 2mm; }
        .stem-band {
            margin-bottom: 3mm;
            padding-bottom: 2.5mm;
            border-bottom: 0.3mm solid #bbb;
        }
        .stem-band-title {
            font-size: 6pt;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #111;
            font-weight: bold;
            line-height: 1.25;
        }
        .stem-band-title .it-sub { font-size: 5pt; color: #666; letter-spacing: 0.4px; }
        .stem-band-code {
            margin-top: 0.8mm;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        .stem-mini { margin-top: 2mm; }
        .stem-mini .bc-img { max-width: 58mm; }
        .stem-mini .bc-hri { font-size: 7pt; letter-spacing: 1.5px; }
        .stem-mini .bc-type { display: none; }
        .stem-mini .bc-frame { padding: 1.5mm; }

        .stub {
            border-top: 0.7mm solid #000;
            padding: 3mm 3.5mm 4mm;
            background: #ececec;
        }
        .stub-title {
            font-size: 6.5pt;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.25;
        }
        .stub-title .it-sub { font-size: 5.5pt; color: #555; letter-spacing: 0.4px; }
        .stub-code { margin-top: 1mm; font-size: 15pt; font-weight: bold; white-space: nowrap; }
        .stub-note { margin-top: 1.5mm; font-size: 6.5pt; line-height: 1.35; }
        .stub-note .it-sub { font-size: 5.8pt; color: #555; margin-top: 0.8mm; }
        .footer-print {
            margin-top: 2mm;
            font-size: 5.5pt;
            color: #111;
            text-align: center;
            letter-spacing: 0.5px;
            line-height: 1.25;
        }
        .footer-print .it-sub { font-size: 5pt; color: #666; }
    </style>
</head>
<body>
@foreach($tags as $index => $tag)
    @php
        $bagNum = $index + 1;
        $bagTotal = $deposit->bag_count;
        $depositCode = $deposit->code;
        $bookingDate = $deposit->booking_date?->format('d/m/Y') ?? '—';
        $checkOut = $deposit->expected_check_out?->format('d/m/Y') ?? '—';
        $printedAt = $printedAt ?? now()->format('d/m/Y H:i');
        $depShort = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $depositCode), 0, 3));
        $bagShort = 'B'.str_pad((string) $bagNum, 2, '0', STR_PAD_LEFT);
        $statusEn = match ($deposit->status->value) {
            'PRENOTATO' => 'Booked',
            'CHECK_IN' => 'In storage',
            'COMPLETATO' => 'Completed',
            'ANNULLATO' => 'Cancelled',
            'NO_SHOW' => 'No show',
            default => $deposit->status->value,
        };
        $statusIt = $deposit->status->label();
    @endphp
    <div class="sheet">
        <div class="masthead">
            <div class="masthead-sub">
                LUGGAGE STORAGE · BAGGAGE TAG
                <span class="it-sub">Deposito bagagli · Tag bagaglio</span>
            </div>
        </div>
        <div class="stripe"></div>

        <table class="route-row">
            <tr>
                <td>
                    <div class="route-lbl">
                        DEPOSIT
                        <span class="it-sub">Deposito</span>
                    </div>
                    <div class="route-val">{{ $depShort }}</div>
                </td>
                <td>
                    <div class="route-lbl">
                        BAG
                        <span class="it-sub">Bagaglio</span>
                    </div>
                    <div class="route-val">{{ $bagShort }}</div>
                </td>
                <td>
                    <div class="route-lbl">
                        TOTAL
                        <span class="it-sub">Totale</span>
                    </div>
                    <div class="route-val">{{ str_pad((string) $bagTotal, 2, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
        </table>

        <div class="hero">
            <div class="hero-label">
                ID TAG · SCAN BARCODE
                <span class="it-sub">Tag identificativo · scan barcode</span>
            </div>
            <div class="hero-code"><nobr>{{ $tag }}</nobr></div>
            <div class="hero-customer">{{ $deposit->customer_name }}</div>

            <div class="barcode-hero">
                @include('Backend.LuggageDeposit.pdf._barcode', ['value' => $tag, 'caption' => $tag, 'height' => 58, 'module' => 2])
            </div>
        </div>

        <div class="receipt">
            <div class="receipt-title">
                DEPOSIT DETAILS
                <span class="it-sub">Dati deposito</span>
            </div>
            <table class="receipt-table">
                <tr>
                    <td class="k">
                        DEPOSIT CODE
                        <span class="it-sub">Codice deposito</span>
                    </td>
                    <td class="v">{{ $depositCode }}</td>
                </tr>
                <tr>
                    <td class="k">
                        BOOKING
                        <span class="it-sub">Prenotazione</span>
                    </td>
                    <td class="v">{{ $bookingDate }}</td>
                </tr>
                <tr>
                    <td class="k">
                        EXPECTED PICKUP
                        <span class="it-sub">Ritiro previsto</span>
                    </td>
                    <td class="v">{{ $checkOut }}</td>
                </tr>
                <tr>
                    <td class="k">
                        PIECE
                        <span class="it-sub">Pezzo</span>
                    </td>
                    <td class="v">{{ $bagNum }} / {{ $bagTotal }}</td>
                </tr>
                <tr>
                    <td class="k">
                        STATUS
                        <span class="it-sub">Stato</span>
                    </td>
                    <td class="v">
                        <span class="status-pill">
                            {{ $statusEn }}
                            <span class="it-sub">{{ $statusIt }}</span>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="k">
                        PRINTED
                        <span class="it-sub">Stampato</span>
                    </td>
                    <td class="v">{{ $printedAt }}</td>
                </tr>
            </table>
        </div>

        <div class="perf">
            ✂ TEAR · FOLD · WRAP ON HANDLE · DO NOT REMOVE
            <span class="it-sub">Perfora · piega · avvolgi al manico · non rimuovere</span>
        </div>

        <table class="stem">
            <tr>
                <td class="stem-side">
                    @foreach(str_split($tag) as $char)
                        <span class="stem-char">{{ $char }}</span>
                    @endforeach
                </td>
                <td class="stem-main">
                    <div class="stem-band">
                        <div class="stem-band-title">
                            SCAN AT PICKUP
                            <span class="it-sub">Scan al ritiro</span>
                        </div>
                        <div class="stem-band-code">{{ $tag }}</div>
                        <div class="stem-mini">
                            @include('Backend.LuggageDeposit.pdf._barcode', ['value' => $tag, 'caption' => $tag, 'height' => 42, 'module' => 2])
                        </div>
                    </div>

                    <div class="stem-band">
                        <div class="stem-band-title">
                            COUNTER REFERENCE
                            <span class="it-sub">Riferimento sportello</span>
                        </div>
                        <div class="stem-band-code" style="font-size:12pt;">{{ $depositCode }}</div>
                        <div class="stem-mini">
                            @include('Backend.LuggageDeposit.pdf._barcode', ['value' => $depositCode, 'caption' => $depositCode, 'height' => 38, 'module' => 2])
                        </div>
                    </div>

                    @for($repeat = 0; $repeat < 2; $repeat++)
                        <div class="stem-mini" style="margin-bottom:3mm;">
                            @include('Backend.LuggageDeposit.pdf._barcode', ['value' => $tag, 'caption' => $tag, 'height' => 36, 'module' => 2])
                        </div>
                    @endfor

                    <div class="footer-print">
                        AUTHORIZED LUGGAGE CUSTODY
                        <span class="it-sub">Custodia bagagli autorizzata</span>
                    </div>
                    <div style="height:28mm;"></div>
                </td>
            </tr>
        </table>

        <div class="stub">
            <div class="stub-title">
                OPERATOR STUB · RETAIN
                <span class="it-sub">Stub operatore · da trattenere</span>
            </div>
            <div class="stub-code">{{ $tag }}</div>
            <div class="stub-note">
                {{ $deposit->customer_name }} · {{ $depositCode }} · piece {{ $bagNum }}/{{ $bagTotal }}<br>
                Attach to deposit slip. Present barcode at pickup.
                <span class="it-sub">
                    Allegare alla distinta deposito. Presentare barcode al ritiro.
                </span>
            </div>
            <div style="margin-top:2mm;">
                @include('Backend.LuggageDeposit.pdf._barcode', ['value' => $tag, 'caption' => $tag, 'height' => 44, 'module' => 2])
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
