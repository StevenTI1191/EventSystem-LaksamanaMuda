import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Inisialisasi Echo berdasarkan driver yang dikonfigurasi.
// Prioritas: Pusher (shared hosting / cloud) -> Reverb (dev lokal / VPS).
// Jika tidak ada key sama sekali, window.Echo = undefined dan semua guard
// "if (typeof window.Echo === 'undefined') return;" akan bekerja.
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    // Pusher Channels (cloud) — dipakai di shared hosting Rumahweb
    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'ap1',
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
    });
} else if (import.meta.env.VITE_REVERB_APP_KEY) {
    // Reverb (self-hosted WebSocket) — dev lokal / VPS
    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
