/**
 * Firebase Client-Side Initialization & FCM Push Token Helper
 * Platform Layanan Publik Warga
 * Memungkinkan permohonan izin notifikasi dan pendaftaran token FCM pada browser warga
 */

window.LayananPublikFirebase = (function () {
    let messaging = null;

    const defaultFirebaseConfig = {
        apiKey: "AIzaSyD9PUPdSdZRAKd6QXORru8kyYcGveC1yns",
        authDomain: "layanan-publik-warga.firebaseapp.com",
        projectId: "layanan-publik-warga",
        storageBucket: "layanan-publik-warga.firebasestorage.app",
        messagingSenderId: "544336210267",
        appId: "1:544336210267:web:052c69600e6c0d74a9d27e",
        measurementId: "G-LFB3EPECT5"
    };

    async function initFirebase(config, vapidKey) {
        const finalConfig = config || window.firebaseConfig || defaultFirebaseConfig;

        if (!finalConfig || !finalConfig.apiKey) {
            console.info('[Firebase] Config belum dikonfigurasi, FCM client dilewati.');
            return null;
        }

        if (typeof firebase === 'undefined') {
            console.warn('[Firebase] Firebase compat SDK belum dimuat di halaman.');
            return null;
        }

        try {
            if (!firebase.apps.length) {
                firebase.initializeApp(finalConfig);
            }
            if ('Notification' in window && firebase.messaging.isSupported()) {
                messaging = firebase.messaging();
                console.log('[Firebase] Inisialisasi Firebase Messaging sukses.');
            }
            return messaging;
        } catch (err) {
            console.error('[Firebase] Gagal menginisialisasi Firebase:', err);
            return null;
        }
    }

    async function requestPushPermission(vapidKey) {
        if (!messaging) {
            console.warn('[Firebase] Messaging belum terinisialisasi.');
            return null;
        }

        try {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                const token = await messaging.getToken({ vapidKey: vapidKey });
                console.log('[Firebase] Berhasil mendapatkan token FCM:', token);
                localStorage.setItem('fcm_token', token);

                // Kirim token ke backend jika user terotentikasi
                const authToken = localStorage.getItem('auth_token');
                if (authToken && token) {
                    await sendTokenToServer(token, authToken);
                }

                return token;
            } else {
                console.warn('[Firebase] Izin notifikasi ditolak oleh pengguna.');
                return null;
            }
        } catch (error) {
            console.error('[Firebase] Gagal mengambil FCM push token:', error);
            return null;
        }
    }

    async function sendTokenToServer(fcmToken, authToken) {
        try {
            await fetch('/api/v1/auth/fcm-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                },
                body: JSON.stringify({ fcm_token: fcmToken, device: navigator.userAgent })
            });
        } catch (e) {
            console.warn('[Firebase] Gagal mengirim token ke server backend:', e.message);
        }
    }

    return {
        init: initFirebase,
        requestPermission: requestPushPermission
    };
})();
