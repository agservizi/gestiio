@php
    $tone = $tone ?? 'info';
    $palette = [
        'info' => ['#0F766E', '#ECFDF5', '#134E4A'],
        'success' => ['#15803D', '#F0FDF4', '#14532D'],
        'warning' => ['#B45309', '#FFFBEB', '#78350F'],
        'critical' => ['#B91C1C', '#FEF2F2', '#7F1D1D'],
    ][$tone] ?? ['#0F766E', '#ECFDF5', '#134E4A'];
    [$accent, $soft, $ink] = $palette;
    $summary = $summary ?? [];
    $sections = $sections ?? [];
@endphp
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? 'Gestiio' }}</title>
</head>
<body style="margin:0;padding:0;background:#F5F7FA;color:#111827;font-family:Verdana,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;color:transparent;opacity:0;">
        {{ $preheader ?? 'Aggiornamento da Gestiio' }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F7FA;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #E5E7EB;">
                    <tr>
                        <td style="padding:0;background:{{ $accent }};height:7px;line-height:7px;font-size:1px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 30px 14px;">
                            <div style="font-size:12px;line-height:1.4;color:#6B7280;text-transform:uppercase;letter-spacing:.12em;font-weight:700;">
                                {{ $eyebrow ?? 'Gestiio' }}
                            </div>
                            <h1 style="margin:12px 0 0;font-size:25px;line-height:1.28;color:#111827;font-weight:700;">
                                {{ $title ?? $subject ?? 'Aggiornamento Gestiio' }}
                            </h1>
                            @if(!empty($intro))
                                <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:#374151;">
                                    {!! nl2br(e($intro)) !!}
                                </p>
                            @endif
                        </td>
                    </tr>

                    @if(!empty($summary))
                        <tr>
                            <td style="padding:4px 30px 20px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ $soft }};border:1px solid #D1D5DB;border-radius:12px;">
                                    @foreach($summary as $label => $value)
                                        <tr>
                                            <td style="padding:12px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #E5E7EB' }};font-size:13px;color:#6B7280;width:34%;vertical-align:top;">
                                                {{ $label }}
                                            </td>
                                            <td style="padding:12px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #E5E7EB' }};font-size:14px;color:{{ $ink }};font-weight:700;vertical-align:top;">
                                                {{ $value }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    @foreach($sections as $section)
                        <tr>
                            <td style="padding:{{ $loop->first ? '4px' : '10px' }} 30px 6px;">
                                <h2 style="font-size:16px;line-height:1.35;margin:0 0 10px;color:#111827;">
                                    {{ $section['title'] ?? 'Dettagli' }}
                                </h2>
                                @if(!empty($section['intro']))
                                    <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#4B5563;">
                                        {{ $section['intro'] }}
                                    </p>
                                @endif
                                @if(!empty($section['items']))
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        @foreach($section['items'] as $label => $value)
                                            <tr>
                                                <td style="padding:8px 0;border-top:1px solid #EEF2F7;font-size:13px;color:#6B7280;width:38%;vertical-align:top;">
                                                    {{ is_int($label) ? 'Info' : $label }}
                                                </td>
                                                <td style="padding:8px 0;border-top:1px solid #EEF2F7;font-size:13px;color:#111827;vertical-align:top;">
                                                    {{ $value }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if(!empty($cta_label) && !empty($cta_url))
                        <tr>
                            <td style="padding:18px 30px 8px;">
                                <a href="{{ $cta_url }}" style="display:inline-block;background:#111827;color:#FFFFFF;text-decoration:none;border-radius:999px;padding:13px 18px;font-size:14px;font-weight:700;">
                                    {{ $cta_label }}
                                </a>
                            </td>
                        </tr>
                    @endif

                    @if(!empty($note))
                        <tr>
                            <td style="padding:14px 30px 6px;">
                                <p style="margin:0;padding:13px 15px;background:#F9FAFB;border-left:4px solid {{ $accent }};font-size:13px;line-height:1.55;color:#374151;">
                                    {{ $note }}
                                </p>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 30px 30px;">
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#4B5563;">
                                Saluti,<br>
                                <strong style="color:#111827;">{{ $signature ?? config('mail.from.name', 'Gestiio') }}</strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:14px 0 0;font-size:11px;line-height:1.4;color:#9CA3AF;">
                    Email automatica generata da Gestiio. Se non ti aspettavi questo messaggio, verifica la pratica nel gestionale.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
