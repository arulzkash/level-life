self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};

    try {
        payload = event.data.json();
    } catch (error) {
        payload = {
            title: 'Level Life',
            body: event.data.text(),
        };
    }

    const title = payload.title || 'Level Life';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: payload.badge || '/icons/icon-192.png',
        tag: payload.tag || 'level-life',
        data: payload.data || {},
        actions: payload.actions || [],
        requireInteraction: payload.requireInteraction || false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/dashboard';

    event.waitUntil((async () => {
        const windowClients = await clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        });

        for (const client of windowClients) {
            const clientUrl = new URL(client.url);

            if (clientUrl.origin === self.location.origin && 'focus' in client) {
                await client.focus();

                if ('navigate' in client) {
                    return client.navigate(targetUrl);
                }

                return;
            }
        }

        if (clients.openWindow) {
            return clients.openWindow(targetUrl);
        }
    })());
});
