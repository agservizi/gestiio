<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Ricevuta consegna {{ $record->request_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #666; }
        .box { border: 1px solid #ccc; padding: 12px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 4px 0; vertical-align: top; }
        .label { width: 35%; color: #555; }
    </style>
</head>
<body>
    <h1>Ricevuta di consegna SEND</h1>
    <div class="muted">Documento interno di sportello — stampato il {{ $printedAt }}</div>

    <div class="box">
        <table>
            <tr>
                <td class="label">Codice pratica</td>
                <td><strong>{{ $record->request_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Stato</td>
                <td>{{ $record->status->label() }}</td>
            </tr>
            <tr>
                <td class="label">Avviso / IUN</td>
                <td>{{ $record->send_notice_identifier ?: '—' }} / {{ $record->iun ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Operatore</td>
                <td>{{ $record->creator?->nominativo() }}</td>
            </tr>
            <tr>
                <td class="label">Supervisore</td>
                <td>{{ $record->supervisor?->nominativo() ?: '—' }}</td>
            </tr>
            @if($record->delivery)
                <tr>
                    <td class="label">Consegnato a</td>
                    <td>{{ $record->delivery->recipient_name ?: '—' }} ({{ $record->delivery->recipient_type }})</td>
                </tr>
                <tr>
                    <td class="label">Metodo</td>
                    <td>{{ $record->delivery->delivery_method }}</td>
                </tr>
                <tr>
                    <td class="label">Identificazione</td>
                    <td>{{ $record->delivery->identification_type ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Data consegna</td>
                    <td>{{ optional($record->delivery->delivered_at)->format('d/m/Y H:i') ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Documenti</td>
                    <td>{{ $record->delivery->documents_summary ?: '—' }}</td>
                </tr>
            @endif
        </table>
    </div>

    <p class="muted" style="margin-top:24px">
        Questa ricevuta non ha valore legale esterno: attesta solo la registrazione della consegna in piattaforma.
    </p>
</body>
</html>
