<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Luggage storage agreement {{ $deposit->code }}</title>
    @include('Backend.LuggageDeposit.pdf._styles')
    <style>
        @page { margin: 8mm 10mm; size: A4 portrait; }
        body {
            font-size: 10pt;
            line-height: 1.38;
            padding: 0;
            margin: 0;
        }
        .it-sub {
            display: block;
            font-size: 9pt;
            font-weight: normal;
            color: #666;
            margin-top: 2px;
            line-height: 1.35;
        }
        .it-sub.inline { display: inline; margin-top: 0; margin-left: 4px; }

        .header {
            border-bottom: 3px solid #111;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .subtitle {
            font-size: 10.5pt;
            color: #333;
            margin-top: 5px;
            line-height: 1.35;
        }

        .layout-2col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .layout-2col td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #ccc;
            background: #fafafa;
            padding: 8px 9px;
        }
        .layout-2col td:first-child { padding-right: 8px; border-right: none; }
        .layout-2col td:last-child { padding-left: 8px; }

        .box-title {
            font-size: 9.5pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #111;
            margin-bottom: 7px;
            font-weight: bold;
            line-height: 1.25;
        }
        .box-title .it-sub {
            font-size: 8.5pt;
            text-transform: none;
            letter-spacing: 0;
        }

        table.info { width: 100%; border-collapse: collapse; }
        table.info td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 10pt;
            line-height: 1.3;
        }
        table.info td:first-child {
            width: 42%;
            color: #111;
            padding-right: 6px;
        }
        table.info td:first-child .it-sub { font-size: 8.5pt; color: #666; }

        .badge-code {
            display: inline-block;
            background: #111;
            color: #fff;
            padding: 3px 10px;
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .tags-list {
            margin-top: 6px;
            font-weight: bold;
            font-size: 10.5pt;
            line-height: 1.3;
        }
        .tags-list .it-sub { font-weight: normal; margin-top: 2px; }

        .terms {
            margin: 0;
            border: 1px solid #ccc;
            padding: 8px 9px 6px;
            background: #fff;
            page-break-inside: avoid;
        }
        .terms ol {
            margin: 4px 0 0;
            padding-left: 16px;
        }
        .terms li {
            margin-bottom: 4px;
            line-height: 1.28;
            font-size: 9.5pt;
        }
        .terms li:last-child { margin-bottom: 0; }

        .signatures { margin-top: 8px; padding-top: 2px; }
        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td {
            width: 50%;
            padding-top: 14px;
            vertical-align: top;
        }
        .sign-line {
            border-top: 1px solid #111;
            padding-top: 5px;
            font-size: 10pt;
            line-height: 1.3;
        }
        .sign-line .it-sub { font-size: 8.5pt; }
        .muted { color: #666; font-size: 8.5pt; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Luggage Storage Acceptance Form</div>
        <div class="subtitle">
            Document to retain and obtain customer signature
            <span class="it-sub">Documento da conservare e far firmare al cliente</span>
        </div>
    </div>

    <table class="layout-2col" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="box-title">
                    DEPOSIT REFERENCE
                    <span class="it-sub">Riferimento deposito</span>
                </div>
                <span class="badge-code">{{ $deposit->code }}</span>
                <div class="tags-list">
                    Baggage tags: {{ implode(' · ', $tags) }}
                    <span class="it-sub">Tag bagagli: {{ implode(' · ', $tags) }}</span>
                </div>
            </td>
            <td>
                <div class="box-title">
                    CUSTOMER DETAILS
                    <span class="it-sub">Dati cliente</span>
                </div>
                <table class="info">
                    <tr>
                        <td>
                            Full name
                            <span class="it-sub">Nome e cognome</span>
                        </td>
                        <td><strong>{{ $deposit->customer_name }}</strong></td>
                    </tr>
                    @if($deposit->customer_email)
                    <tr>
                        <td>
                            Email
                            <span class="it-sub">Email</span>
                        </td>
                        <td>{{ $deposit->customer_email }}</td>
                    </tr>
                    @endif
                    @if($deposit->customer_phone)
                    <tr>
                        <td>
                            Phone
                            <span class="it-sub">Telefono</span>
                        </td>
                        <td>{{ $deposit->customer_phone }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>
                            Number of bags
                            <span class="it-sub">Numero borse</span>
                        </td>
                        <td><strong>{{ $deposit->bag_count }}</strong></td>
                    </tr>
                    <tr>
                        <td>
                            Deposit date
                            <span class="it-sub">Data deposito</span>
                        </td>
                        <td>{{ $deposit->booking_date?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>
                            Daily rate
                            <span class="it-sub">Tariffa giornaliera</span>
                        </td>
                        <td>€ {{ number_format($deposit->daily_rate, 2, ',', '.') }} / bag</td>
                    </tr>
                    <tr>
                        <td>
                            Booking source
                            <span class="it-sub">Fonte prenotazione</span>
                        </td>
                        <td>
                            {{ $deposit->source === 'PORTALE' ? 'Online' : 'Counter' }}
                            <span class="it-sub inline">({{ $deposit->source === 'PORTALE' ? 'Online' : 'Sportello' }})</span>
                        </td>
                    </tr>
                    @if($deposit->notes)
                    <tr>
                        <td>
                            Notes
                            <span class="it-sub">Note</span>
                        </td>
                        <td>{{ $deposit->notes }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="terms">
        <div class="box-title">
            STORAGE CONDITIONS
            <span class="it-sub">Condizioni di custodia</span>
        </div>
        <ol>
            <li>
                The customer declares to be the owner or lawful holder of the deposited luggage.
                <span class="it-sub">Il cliente dichiara di essere proprietario o legittimo detentore dei bagagli depositati.</span>
            </li>
            <li>
                Luggage is identified by the tags listed above and remains in custody until pickup with presentation of the deposit code.
                <span class="it-sub">I bagagli sono identificati dai tag sopra indicati e restano in custodia fino al ritiro con presentazione del codice deposito.</span>
            </li>
            <li>
                The applicable rate is that in force at acceptance, calculated per full or partial day.
                <span class="it-sub">La tariffa applicata è quella vigente al momento dell'accettazione, calcolata per giorno intero o frazione di giorno.</span>
            </li>
            <li>
                The deposit does not cover valuables, cash, original documents or perishable items, unless otherwise agreed in writing.
                <span class="it-sub">Il deposito non copre oggetti di valore, denaro, documenti originali o materiali deperibili, salvo diversa pattuizione scritta.</span>
            </li>
            <li>
                The customer authorizes the processing of personal data for luggage storage service management.
                <span class="it-sub">Il cliente autorizza il trattamento dei dati personali per la gestione del servizio deposito bagagli.</span>
            </li>
        </ol>

        <div class="signatures">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-right: 14px;">
                        <div class="sign-line">
                            Customer signature
                            <span class="it-sub">Firma del cliente</span>
                        </div>
                        <div class="muted">Date: ____ / ____ / ______</div>
                    </td>
                    <td style="padding-left: 14px;">
                        <div class="sign-line">
                            Operator / acceptance signature
                            <span class="it-sub">Firma operatore / accettazione</span>
                        </div>
                        <div class="muted">Date: {{ now()->format('d/m/Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
