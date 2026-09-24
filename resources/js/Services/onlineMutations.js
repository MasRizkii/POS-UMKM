export const OFFLINE_MUTATION_MESSAGE = 'Anda sedang offline. Checkout, Void, dan Buka/Tutup Shift memerlukan koneksi internet. Tidak ada transaksi yang dikirim atau dijadwalkan otomatis.';

export function assertOnline(online = globalThis.navigator?.onLine !== false) {
    if (!online) {
        const error = new Error(OFFLINE_MUTATION_MESSAGE);
        error.code = 'OFFLINE_MUTATION';
        throw error;
    }
}

export function installOnlineMutationGuard(router, axios, notify = (message) => window.alert(message)) {
    router.on('before', (event) => {
        if (['get', 'head', 'options'].includes(event.detail.visit.method.toLowerCase())) return;
        try {
            assertOnline();
        } catch (error) {
            notify(error.message);
            return false;
        }
    });
    axios.interceptors.request.use((config) => {
        if (!['get', 'head', 'options'].includes((config.method || 'get').toLowerCase())) assertOnline();
        return config;
    });
}
