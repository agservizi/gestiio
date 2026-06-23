# Web Push Notification Workflow

Documentazione del sistema di notifiche push in-browser per la chat interna.

## 1. Architecture Overview

```
Chat Event (ChatMessage created)
    ↓
ChatMessageSent (Event dispatched)
    ↓
SendChatWebPushNotification (Queue Job)
    ↓
Queue Worker (php artisan queue:work)
    ↓
minishlink/web-push library (HTTP POST to push service)
    ↓
Browser Service Worker (public/sw-chat-push.js)
    ↓
Display notification in browser
```

## 2. Setup Requirements

### 1. Generate VAPID Keys

```bash
php artisan chat:generate-vapid-keys
```

Output:
```
WEBPUSH_VAPID_PUBLIC_KEY=BC...
WEBPUSH_VAPID_PRIVATE_KEY=...
```

### 2. Configure .env

```env
WEBPUSH_VAPID_PUBLIC_KEY=BC...
WEBPUSH_VAPID_PRIVATE_KEY=...
WEBPUSH_VAPID_SUBJECT=mailto:dev@example.com

# Queue configuration
QUEUE_CONNECTION=database  # or redis, sync (for dev)
QUEUE_DRIVER=database
```

### 3. Database Setup

```bash
php artisan migrate
# Creates: jobs, failed_jobs, job_batches tables
```

### 4. Register Service Worker

**File**: `resources/views/layouts/app.blade.php` or base layout

```html
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw-chat-push.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.log('SW registration failed', err));
    }
</script>
```

### 5. Service Worker File

**File**: `public/sw-chat-push.js`

```javascript
self.addEventListener('push', function(event) {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: '/images/icon.png',
        badge: '/images/badge.png',
        data: {
            url: data.url || '/area-personale'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({type: 'window'}).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === event.notification.data.url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(event.notification.data.url);
            }
        })
    );
});
```

## 3. Flow Details

### Step 1: Chat Message Created

**File**: `app/Models/ChatMessage.php`

```php
class ChatMessage extends Model
{
    protected $dispatchesEvents = [
        'created' => ChatMessageSent::class,
    ];
}
```

### Step 2: Event Dispatched

**File**: `app/Events/ChatMessageSent.php`

```php
class ChatMessageSent implements ShouldBroadcast
{
    public function __construct(public ChatMessage $message) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_thread_id),
        ];
    }
}
```

When the event fires, Laravel **automatically** queues the notification job.

### Step 3: Queue Job

**File**: `app/Jobs/SendChatWebPushNotification.php`

```php
class SendChatWebPushNotification implements ShouldQueue
{
    public function handle()
    {
        $subscribers = ChatSubscription::where('chat_thread_id', $this->message->chat_thread_id)
            ->get();
        
        foreach ($subscribers as $sub) {
            // Send via minishlink/web-push
            Notification::send($sub->user, new ChatPushNotification($this->message));
        }
    }
}
```

### Step 4: Notification Sent

**File**: `app/Notifications/ChatPushNotification.php`

```php
class ChatPushNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database', 'webpush'];
    }
    
    public function toWebPush($notifiable)
    {
        return (new WebPushMessage())
            ->title('New message from ' . $this->message->user->name)
            ->body($this->message->content)
            ->action('Open Chat', '/chat');
    }
}
```

### Step 5: Browser Receives Push

Service Worker receives the push event and displays notification.

## 4. Queue Worker

### Running the Worker

```bash
# Development (foreground)
php artisan queue:work

# Production (background daemon)
php artisan queue:work --daemon --sleep=3

# With retry limit
php artisan queue:work --tries=3
```

### Monitoring Queue

```bash
# List pending jobs
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed

# Retry a failed job
php artisan queue:retry {id}

# Flush all jobs
php artisan queue:flush
```

## 5. User Subscription

### Subscribe to Push

```php
// In JavaScript (browser)
Notification.requestPermission().then(permission => {
    if (permission === 'granted') {
        // Subscribe to push notifications
        navigator.serviceWorker.ready.then(reg => {
            reg.pushManager.getSubscription()
                .then(sub => {
                    if (!sub) {
                        return reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: publicKey
                        });
                    }
                    return sub;
                })
                .then(sub => {
                    // Send subscription to server
                    fetch('/api/push-subscribe', {
                        method: 'POST',
                        body: JSON.stringify(sub),
                        headers: {'Content-Type': 'application/json'}
                    });
                });
        });
    }
});
```

### Store Subscription

```php
// In Controller
Route::post('/api/push-subscribe', function (Request $request) {
    ChatSubscription::updateOrCreate(
        ['user_id' => auth()->id()],
        ['subscription' => json_encode($request->all())]
    );
});
```

## 6. Requirements for Web Push

### HTTPS Only
- ✅ Production: HTTPS required
- ✅ Development: localhost works without HTTPS
- ❌ HTTP (non-localhost): Web Push blocked by browsers

### Browser Support
- ✅ Chrome, Edge, Firefox (Desktop)
- ⚠️ Safari: Limited support (macOS 16+, iOS not supported)
- ❌ IE: Not supported

### Notification Permissions
- Browser must grant `Notification.permission === 'granted'`
- User grants permission via prompt

## 7. Debugging

### Check Queue Status

```php
php artisan tinker
>>> DB::table('jobs')->count();  // Pending jobs
>>> DB::table('failed_jobs')->count();  // Failed jobs
>>> DB::table('jobs')->first();  // View job details
```

### View Push Subscriptions

```php
php artisan tinker
>>> ChatSubscription::all();
>>> ChatSubscription::where('user_id', 42)->first();
```

### Test Push Manually

```php
php artisan tinker
>>> $user = User::find(42);
>>> Notification::send($user, new ChatPushNotification($message));
>>> # Check queue status
>>> DB::table('jobs')->get();
```

### Monitor Service Worker

**In Chrome DevTools:**
1. Go to `Application` → `Service Workers`
2. Check registration status
3. Check for errors in the "Errors" column

## 8. Troubleshooting

### Push notifications not arriving
1. Check service worker is registered
   - DevTools → Application → Service Workers
2. Check user gave Notification permission
   - DevTools → Application → Manifest → Notifications
3. Check queue worker is running
   - `php artisan queue:work --verbose`
4. Check HTTPS (production only)
   - Push needs HTTPS (or localhost)
5. Check VAPID keys
   - `.env` has `WEBPUSH_VAPID_PUBLIC_KEY`

### Service Worker errors
1. Check `public/sw-chat-push.js` syntax
2. Check browser console for errors
3. Restart queue worker after changes

### Queue stuck
```bash
php artisan queue:flush
php artisan queue:work --tries=1
```

## 9. Performance Considerations

- Queue jobs are async (non-blocking)
- Consider using Redis instead of database queue (faster)
- Monitor failed jobs in Sentry
- Log all push events with StructuredLogger
- Cache subscriber lists if large

## 10. Production Checklist

- [ ] VAPID keys generated and in `.env`
- [ ] `.env` has `HTTPS` URL
- [ ] Queue worker running as service (supervisor/systemd)
- [ ] Database/Redis configured for queue
- [ ] Service Worker deployed to `public/sw-chat-push.js`
- [ ] Sentry monitoring configured
- [ ] Failed jobs monitored (alerts on failures)
- [ ] Queue worker logs monitored
- [ ] VAPID key rotation policy in place

