/**
 * Service Worker for Rooster Planner Push Notifications
 */

self.addEventListener('push', function(event) {
    const data = event.data.json();
    
    const options = {
        body: data.message,
        icon: '/wp-content/plugins/rooster-planner-pro/assets/images/icon-192x192.png',
        badge: '/wp-content/plugins/rooster-planner-pro/assets/images/badge-72x72.png',
        tag: data.tag || 'rooster-planner',
        requireInteraction: true,
        actions: [
            {
                action: 'open',
                title: 'Openen'
            },
            {
                action: 'dismiss',
                title: 'Sluiten'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.openWindow('/medewerker-dashboard/')
        );
    }
});

// Cache strategy
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open('rooster-planner-v1').then(function(cache) {
            return cache.addAll([
                '/',
                '/medewerker-dashboard/',
                '/medewerker-rooster/',
                '/medewerker-beschikbaarheid/'
            ]);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});
