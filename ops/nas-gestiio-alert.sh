#!/bin/sh
set -u

CONFIG=/Volume1/homes/Carmine/gestiio-alert.env
APP_ENV=/Volume1/homes/Carmine/gestiio-latest-deploy/.env
LOG=/home/Carmine/gestiio-alert.log

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

message=${1:-"Gestiio alert"}
level=${2:-"INFO"}
action_url=${3:-}

echo "$(stamp) [$level] $message" >> "$LOG"

if [ -f "$CONFIG" ]; then
    # shellcheck disable=SC1090
    . "$CONFIG"
fi

env_value() {
    key=$1
    [ -f "$APP_ENV" ] || return 0
    grep -E "^${key}=" "$APP_ENV" | tail -1 | cut -d= -f2- | tr -d '\r' | sed 's/^"//; s/"$//; s/^'\''//; s/'\''$//'
}

json_escape() {
    printf "%s" "$1" | tr '\r\n' '  ' | sed 's/\\/\\\\/g; s/"/\\"/g'
}

html_escape() {
    printf "%s" "$1" | sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g; s/"/\&quot;/g'
}

RESEND_API_KEY=${RESEND_API_KEY:-${RESEND_KEY:-}}
if [ "$RESEND_API_KEY" = "" ]; then
    RESEND_API_KEY=$(env_value RESEND_KEY)
fi

MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-}
if [ "$MAIL_FROM_ADDRESS" = "" ]; then
    MAIL_FROM_ADDRESS=$(env_value MAIL_FROM_ADDRESS)
fi

MAIL_FROM_NAME=${MAIL_FROM_NAME:-}
if [ "$MAIL_FROM_NAME" = "" ]; then
    MAIL_FROM_NAME=$(env_value MAIL_FROM_NAME)
fi

ALERT_EMAIL_TO=${ALERT_EMAIL_TO:-${ALERT_EMAIL:-}}
if [ "$ALERT_EMAIL_TO" = "" ]; then
    ALERT_EMAIL_TO="$MAIL_FROM_ADDRESS"
fi

if [ "$RESEND_API_KEY" != "" ] && [ "$MAIL_FROM_ADDRESS" != "" ] && [ "$ALERT_EMAIL_TO" != "" ]; then
    from="$MAIL_FROM_ADDRESS"
    if [ "$MAIL_FROM_NAME" != "" ]; then
        from="$MAIL_FROM_NAME <$MAIL_FROM_ADDRESS>"
    fi

    subject="[$level] Gestiio NAS"
    sent_at="$(stamp)"
    body="$sent_at [$level] $message"
    host_name=$(hostname 2>/dev/null || echo "TNAS")
    public_url="$action_url"
    action_label="Apri Gestiio"
    if [ "$public_url" = "" ]; then
        if [ "$level" = "CRITICAL" ]; then
            public_url="https://gestiio.agenziaplinio.it/backend"
            action_label="Apri diagnostica"
        else
            public_url="https://gestiio.agenziaplinio.it/login"
        fi
    fi

    badge_bg="#2563eb"
    badge_fg="#ffffff"
    accent="#2563eb"
    if [ "$level" = "CRITICAL" ]; then
        badge_bg="#dc2626"
        accent="#dc2626"
    elif [ "$level" = "WARNING" ]; then
        badge_bg="#f59e0b"
        badge_fg="#111827"
        accent="#f59e0b"
    elif [ "$level" = "INFO" ]; then
        badge_bg="#16a34a"
        accent="#16a34a"
    fi

    escaped_message=$(html_escape "$message")
    escaped_level=$(html_escape "$level")
    escaped_time=$(html_escape "$sent_at")
    escaped_host=$(html_escape "$host_name")
    escaped_public_url=$(html_escape "$public_url")
    escaped_action_label=$(html_escape "$action_label")
    html_body=$(printf '%s' "<!doctype html><html><body style=\"margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;\"><div style=\"max-width:640px;margin:0 auto;padding:24px;\"><div style=\"background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;\"><div style=\"border-top:6px solid ${accent};padding:22px 24px 12px;\"><div style=\"font-size:13px;color:#6b7280;margin-bottom:10px;\">Gestiio NAS Alert</div><span style=\"display:inline-block;background:${badge_bg};color:${badge_fg};font-weight:700;font-size:12px;letter-spacing:.3px;border-radius:999px;padding:6px 10px;\">${escaped_level}</span><h1 style=\"font-size:22px;line-height:1.3;margin:16px 0 0;color:#111827;\">Monitoraggio infrastruttura</h1></div><div style=\"padding:8px 24px 24px;\"><p style=\"font-size:16px;line-height:1.55;margin:0 0 18px;\">${escaped_message}</p><table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;border-collapse:collapse;margin:0 0 20px;\"><tr><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;color:#6b7280;width:120px;font-size:13px;\">Quando</td><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;font-size:13px;\">${escaped_time}</td></tr><tr><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;\">Host</td><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;font-size:13px;\">${escaped_host}</td></tr><tr><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;\">Servizio</td><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;font-size:13px;\">Gestiio / Docker / Cloudflare Tunnel</td></tr><tr><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;\">Link</td><td style=\"padding:10px 0;border-top:1px solid #e5e7eb;font-size:13px;\">${escaped_public_url}</td></tr></table><a href=\"${escaped_public_url}\" style=\"display:inline-block;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;padding:11px 14px;font-size:14px;font-weight:700;\">${escaped_action_label}</a><p style=\"font-size:12px;line-height:1.45;color:#6b7280;margin:22px 0 0;\">Alert automatico generato dal watchdog NAS. Log: /home/Carmine/gestiio-alert.log</p></div></div></div></body></html>")

    payload_file=/tmp/gestiio-resend-alert.json
    printf '{"from":"%s","to":["%s"],"subject":"%s","text":"%s","html":"%s"}' \
        "$(json_escape "$from")" \
        "$(json_escape "$ALERT_EMAIL_TO")" \
        "$(json_escape "$subject")" \
        "$(json_escape "$body")" \
        "$(json_escape "$html_body")" > "$payload_file"

    resend_status=$(curl --http1.1 -sS -o /tmp/gestiio-resend-alert.out -w "%{http_code}" --max-time 15 \
        -X POST "https://api.resend.com/emails" \
        -H "Authorization: Bearer ${RESEND_API_KEY}" \
        -H "Content-Type: application/json" \
        --data-binary "@$payload_file" 2>/tmp/gestiio-resend-alert.err || true)
    rm -f "$payload_file"
    [ "$resend_status" != "" ] || resend_status="000"

    if [ "$resend_status" = "200" ] || [ "$resend_status" = "201" ] || [ "$resend_status" = "202" ]; then
        echo "$(stamp) [INFO] resend email sent status=$resend_status to=$ALERT_EMAIL_TO" >> "$LOG"
    else
        echo "$(stamp) [WARNING] resend email failed status=$resend_status" >> "$LOG"
        if [ -s /tmp/gestiio-resend-alert.err ]; then
            echo "$(stamp) [WARNING] resend curl error: $(head -1 /tmp/gestiio-resend-alert.err)" >> "$LOG"
        fi
    fi
fi

if [ "${TELEGRAM_BOT_TOKEN:-}" != "" ] && [ "${TELEGRAM_CHAT_ID:-}" != "" ]; then
    curl -fsS --max-time 10 \
        -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
        -d "chat_id=${TELEGRAM_CHAT_ID}" \
        --data-urlencode "text=[$level] $message" >/dev/null 2>&1 || true
fi

if [ "${ALERT_WEBHOOK_URL:-}" != "" ]; then
    payload=$(printf '{"level":"%s","message":"%s","host":"TNAS"}' "$level" "$(printf "%s" "$message" | sed 's/"/\\"/g')")
    curl -fsS --max-time 10 \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$ALERT_WEBHOOK_URL" >/dev/null 2>&1 || true
fi

if command -v mail >/dev/null 2>&1 && [ "${ALERT_EMAIL_TO:-}" != "" ]; then
    printf "%s\n" "$message" | mail -s "[$level] Gestiio NAS" "$ALERT_EMAIL_TO" >/dev/null 2>&1 || true
fi
