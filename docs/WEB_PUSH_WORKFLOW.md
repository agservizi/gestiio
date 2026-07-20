# Web Push Notification Workflow

Documentazione del sistema di notifiche push in-browser per la **chat interna**
(`/backend/chat-interna`). Questa pagina descrive l'implementazione reale
presente nel codice: modelli, job e service effettivamente usati.

## 1. Componenti reali

| Componente | File | Ruolo |
|---|---|---|
| Modello sottoscrizioni | `app/Models/ChatPushSubscription.php` | Una riga per (utente, endpoint) del browser |
| Job invio push | `app/Jobs/SendChatWebPushNotification.php` | `ShouldQueue`, riceve `userId` + `payload` array |
| Service invio | `app/Services/ChatWebPushService.php` | Usa `minishlink/web-push` per inviare a tutte le subscription dell'utente |
| Comando chiavi | `app/Console/Commands/GenerateChatVapidKeys.php` | `php artisan chat:generate-vapid-keys` |
| Service worker | `public/sw-chat-push.js` | Riceve l'evento `push` e mostra la notifica |
| Controller | `app/Http/Controllers/Backend/ChatController.php` | Endpoint subscribe/unsubscribe/vapid + dispatch del job |

> Nota: NON esistono un modello `ChatSubscription`, una Notification
> `ChatPushNotification`, né `ChatMessage::$dispatchesEvents`. Il push viene
> inviato **esplicitamente** dal controller tramite dispatch del job, non
> tramite un observer sul model.

## 2. Flusso effettivo

```
Utente invia messaggio (ChatController@sendMessage / forwardMessages)
    ↓
Per ogni destinatario del thread (escluso il mittente e i thread silenziati):
    SendChatWebPushNotification::dispatch($userId, $payload)
    ↓
Queue Worker (php artisan queue:work)  — connection redis/database/sync
    ↓
SendChatWebPushNotification::handle(ChatWebPushService $service)
    → $service->sendToUser($userId, $payload)
    ↓
ChatWebPushService: carica le ChatPushSubscription abilitate dell'utente
    e invia via minishlink/web-push (VAPID)
    ↓
Browser: public/sw-chat-push.js riceve `push` → showNotification()
```

Il `payload` inviato dal controller contiene: `title`, `body`, `url`,
`thread_id`, `message_id`, `tag`, `icon`, `badge`, `thread_name`.

## 3. Setup

### 3.1 Genera le chiavi VAPID

```bash
php artisan chat:generate-vapid-keys
```

### 3.2 Configura `.env`

```env
WEBPUSH_VAPID_PUBLIC_KEY=BC...
WEBPUSH_VAPID_PRIVATE_KEY=...
WEBPUSH_VAPID_SUBJECT=mailto:dev@example.com

# Coda: in produzione redis, database o sync (dev)
QUEUE_CONNECTION=redis
```

Le chiavi sono lette da `config/services.php` sotto `webpush.vapid.*`.

### 3.3 Migrazioni

La tabella `chat_push_subscriptions` è creata da
`database/migrations/2026_02_18_090000_create_chat_push_subscriptions_table.php`.

```bash
php artisan migrate
```

### 3.4 Queue worker

Il push è asincrono: serve un worker attivo (tranne con `QUEUE_CONNECTION=sync`).

```bash
php artisan queue:work --tries=3
```

## 4. Sottoscrizione lato client

Endpoint reali (gruppo `/backend`, middleware auth + ruolo + 2fa):

| Metodo | Route | Controller |
|---|---|---|
| GET | `/backend/chat-interna/push/vapid-public-key` | `pushVapidPublicKey` |
| POST | `/backend/chat-interna/push/subscribe` | `subscribePush` |
| POST | `/backend/chat-interna/push/unsubscribe` | `unsubscribePush` |

`subscribePush` valida `endpoint`, `keys.p256dh`, `keys.auth`,
`contentEncoding` e fa `updateOrCreate` su `ChatPushSubscription`
(`is_enabled = true`, `last_used_at = now()`).

`ChatWebPushService` rimuove automaticamente le subscription che restituiscono
HTTP 404/410 (endpoint non più validi).

## 5. Requisiti browser

- **HTTPS obbligatorio** in produzione (localhost funziona senza).
- Supporto: Chrome, Edge, Firefox desktop; Safari limitato; niente iOS web push classico.
- L'utente deve concedere `Notification.permission === 'granted'`.

## 6. Debug

```bash
# Coda
php artisan queue:work --verbose
php artisan queue:failed

# Tinker: subscription di un utente
php artisan tinker
>>> App\Models\ChatPushSubscription::where('user_id', 42)->get();

# Invio manuale del job
>>> App\Jobs\SendChatWebPushNotification::dispatch(42, ['title' => 'Test', 'body' => 'Ciao', 'url' => '/backend/chat-interna']);
```

Service Worker: DevTools → Application → Service Workers (verifica registrazione
di `/sw-chat-push.js` ed eventuali errori).

## 7. Relazione con il realtime SSE

Il push copre le notifiche quando la scheda **non** è attiva. Quando la chat è
aperta, l'aggiornamento in tempo reale avviene via **SSE** (endpoint
`GET /backend/chat-interna/{thread}/stream`) e via polling delta
(`GET /backend/chat-interna/poll?after_id=...&delta=1`). Il broadcasting Laravel
(`ChatMessageSent`, `ChatTypingUpdated`) è predisposto per un futuro client Echo,
ma l'UI attuale non ne dipende.
