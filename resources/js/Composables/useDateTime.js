import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable untuk memformat tanggal dan jam mengikuti timezone toko.
 */
export function useDateTime() {
    const page = usePage();
    const timezone = computed(() => page.props.store?.timezone || 'Asia/Jakarta');
    const timeZoneLabel = computed(() => ({
        'Asia/Jakarta': 'WIB',
        'Asia/Makassar': 'WITA',
        'Asia/Jayapura': 'WIT',
    })[timezone.value] || timezone.value);

    const formatDate = (dateString, options = {}) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeZone: timezone.value,
            ...options,
        }).format(date);
    };

    const formatDateTime = (dateString, options = {}) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: timezone.value,
            ...options,
        }).format(date);
    };

    const formatTime = (dateString, options = {}) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            timeZone: timezone.value,
            ...(Object.keys(options).length === 0 ? { timeStyle: 'short' } : {}),
            ...options,
        }).format(date);
    };

    return {
        formatDate,
        formatDateTime,
        formatTime,
        timezone,
        timeZoneLabel,
    };
}
