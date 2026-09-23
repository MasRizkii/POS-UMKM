import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable untuk memantau status jaringan online/offline.
 * Sesuai PRD: Checkout membutuhkan koneksi aktif, tampilkan peringatan saat offline.
 */
export function useNetworkStatus() {
    const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
    };

    onMounted(() => {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    });

    return {
        isOnline,
    };
}
