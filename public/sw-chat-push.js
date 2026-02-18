self.addEventListener('push', function (event) {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = {
            title: 'Nuovo messaggio',
            body: event.data ? event.data.text() : 'Hai un nuovo messaggio in chat',
        };
    }

    const title = data.title || 'Nuovo messaggio';
    const options = {
        body: data.body || 'Hai un nuovo messaggio in chat',
        icon: data.icon || '/images/logo_small_icon_only.png',
        badge: data.badge || '/images/logo_small_icon_only.png',
        tag: data.tag || 'chat-notification',
        data: {
            url: data.url || '/backend/chat-interna',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = event.notification?.data?.url || '/backend/chat-interna';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (const client of windowClients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
