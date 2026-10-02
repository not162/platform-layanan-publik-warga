/**
 * Firebase Cloud Messaging Service Worker
 * Platform Layanan Publik Warga (Versi Firebase Spark Plan - 100% Gratis)
 *
 * Menerima notifikasi Web Push latar belakang saat warga menerima:
 * - Update status surat pengantar RT (Disetujui / Ditolak / Siap Diambil)
 * - Tanggapan atas pengaduan lingkungan
 * - Pengumuman darurat lingkungan
 */

importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "AIzaSyD9PUPdSdZRAKd6QXORru8kyYcGveC1yns",
    authDomain: "layanan-publik-warga.firebaseapp.com",
    projectId: "layanan-publik-warga",
    storageBucket: "layanan-publik-warga.firebasestorage.app",
    messagingSenderId: "544336210267",
    appId: "1:544336210267:web:052c69600e6c0d74a9d27e",
    measurementId: "G-LFB3EPECT5"
};

try {
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    messaging.onBackgroundMessage((payload) => {
        console.log('[firebase-messaging-sw.js] Menerima pesan latar belakang:', payload);

        const notificationTitle = payload.notification?.title || payload.data?.title || 'Portal Layanan Warga';
        const notificationOptions = {
            body: payload.notification?.body || payload.data?.body || 'Pembaruan baru dari pengurus RT.',
            icon: payload.notification?.icon || '/images/favicon.svg',
            badge: '/images/favicon.svg',
            data: payload.data || {},
            actions: [
                { action: 'open_portal', title: 'Buka Portal' }
            ]
        };

        return self.registration.showNotification(notificationTitle, notificationOptions);
    });
} catch (e) {
    console.warn('[firebase-messaging-sw.js] Firebase SDK init info:', e.message);
}

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.click_action || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
