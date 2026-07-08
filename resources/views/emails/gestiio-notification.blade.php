@php
    $tone = $tone ?? 'info';
    $palette = [
        'info' => ['#0090E7', '#EAF4FE', '#0B4A78', 'Aggiornamento'],
        'success' => ['#12875B', '#EAF8F1', '#0D5C3E', 'Completato'],
        'warning' => ['#B7791F', '#FEF6E7', '#7C5210', 'Attenzione'],
        'critical' => ['#C0362C', '#FDEEEC', '#7E241D', 'Urgente'],
    ][$tone] ?? ['#0090E7', '#EAF4FE', '#0B4A78', 'Aggiornamento'];
    [$accent, $soft, $ink, $kickerDefault] = $palette;
    $summary = $summary ?? [];
    $sections = $sections ?? [];
    $kicker = $kicker ?? ($eyebrow ?? $kickerDefault);
@endphp
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>{{ $subject ?? 'Gestiio' }}</title>
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!--<![endif]-->
</head>
<body style="margin:0;padding:0;background:#EEF2F8;color:#0B1220;font-family:'Poppins',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;color:transparent;opacity:0;mso-hide:all;">
        {{ $preheader ?? 'Aggiornamento da Gestiio' }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#EEF2F8;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <!-- Logo -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:0 0 24px;">
                            <a href="{{ url()->to('/') }}" style="text-decoration:none;">
                                <img src="{{ url()->to('/') }}/loghi/logo.png" alt="{{ config('mail.from.name', 'Gestiio') }}" height="28" style="height:28px;border:0;">
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- Card -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#FFFFFF;border-radius:16px;border:1px solid #E4E9F2;box-shadow:0 1px 2px rgba(11,18,32,.04),0 12px 28px rgba(11,18,32,.06);">
                    <tr>
                        <td style="padding:36px 40px 8px;">
                            <span style="display:inline-block;padding:6px 14px;border-radius:999px;background:{{ $soft }};color:{{ $ink }};font-size:12px;font-weight:700;letter-spacing:.02em;">
                                {{ $kicker }}
                            </span>
                            <h1 style="margin:20px 0 0;font-size:28px;line-height:1.25;color:#0B1220;font-weight:700;letter-spacing:-.02em;">
                                {{ $title ?? $subject ?? 'Aggiornamento Gestiio' }}
                            </h1>
                            @if(!empty($intro))
                                <p style="margin:14px 0 0;font-size:15px;line-height:1.65;color:#3F4757;">
                                    {!! nl2br(e($intro)) !!}
                                </p>
                            @endif
                        </td>
                    </tr>

                    @if(!empty($image))
                        <tr>
                            <td style="padding:20px 40px 0;">
                                <img src="{{ $image }}" alt="" width="520" style="width:100%;max-width:520px;border-radius:12px;display:block;">
                            </td>
                        </tr>
                    @endif

                    @if(!empty($bodyHtml))
                        <tr>
                            <td style="padding:20px 40px 4px;font-size:15px;line-height:1.7;color:#3F4757;">
                                {!! $bodyHtml !!}
                            </td>
                        </tr>
                    @endif

                    @if(!empty($summary))
                        <tr>
                            <td style="padding:20px 40px 8px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F7F9FC;border:1px solid #E4E9F2;border-radius:12px;">
                                    @foreach($summary as $label => $value)
                                        <tr>
                                            <td style="padding:13px 18px;border-bottom:{{ $loop->last ? '0' : '1px solid #E9EDF4' }};font-size:13px;color:#8A94A6;width:38%;vertical-align:top;">
                                                {{ $label }}
                                            </td>
                                            <td style="padding:13px 18px;border-bottom:{{ $loop->last ? '0' : '1px solid #E9EDF4' }};font-size:14px;color:#0B1220;font-weight:600;vertical-align:top;">
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
                            <td style="padding:{{ $loop->first ? '8px' : '4px' }} 40px 6px;">
                                <h2 style="font-size:15px;line-height:1.35;margin:0 0 10px;color:#0B1220;font-weight:700;">
                                    {{ $section['title'] ?? 'Dettagli' }}
                                </h2>
                                @if(!empty($section['intro']))
                                    <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#3F4757;">
                                        {{ $section['intro'] }}
                                    </p>
                                @endif
                                @if(!empty($section['items']))
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        @foreach($section['items'] as $label => $value)
                                            <tr>
                                                <td style="padding:9px 0;border-top:1px solid #EEF2F8;font-size:13px;color:#8A94A6;width:40%;vertical-align:top;">
                                                    {{ is_int($label) ? 'Info' : $label }}
                                                </td>
                                                <td style="padding:9px 0;border-top:1px solid #EEF2F8;font-size:13px;color:#0B1220;vertical-align:top;">
                                                    {{ $value }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if(!empty($code))
                        <tr>
                            <td style="padding:22px 40px 4px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px dashed #7CC1F2;border-radius:10px;background:#EAF4FE;">
                                    <tr>
                                        <td align="center" style="padding:18px 16px;">
                                            <span style="display:block;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#0B4A78;margin-bottom:8px;">Codice di verifica</span>
                                            <span style="display:inline-block;font-size:30px;line-height:1;font-weight:800;letter-spacing:.12em;color:#0B4A78;font-family:'Poppins',-apple-system,sans-serif;">{{ $code }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if(!empty($cta_label) && !empty($cta_url))
                        <tr>
                            <td style="padding:26px 40px 4px;">
                                <table role="presentation" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="border-radius:8px;background:#0090E7;">
                                            <a href="{{ $cta_url }}" style="display:inline-block;padding:15px 32px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:8px;">
                                                {{ $cta_label }} &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if(!empty($note))
                        <tr>
                            <td style="padding:18px 40px 4px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-left:3px solid {{ $accent }};">
                                    <tr>
                                        <td style="padding:12px 16px;background:{{ $soft }};font-size:13px;line-height:1.55;color:{{ $ink }};">
                                            {{ $note }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:8px 40px 0;">
                            <div style="border-top:1px solid #EEF2F8;margin-top:28px;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 40px 32px;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#8A94A6;">
                                A presto,<br>
                                <strong style="color:#0B1220;">{{ $signature ?? config('mail.from.name', 'Gestiio') }}</strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:20px 24px 0;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#9CA7BD;">
                                Email automatica generata da Gestiio &middot; se non ti aspettavi questo messaggio, verifica la pratica nel gestionale.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
