/**
 * Composable untuk memformat tanggal dan jam mengikuti timezone toko.
 */
export function useDateTime() {
    const formatDate = (dateString, options = {}) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            ...options,
        }).format(date);
    };

    const formatDateTime = (dateString, options = {}) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
            ...options,
        }).format(date);
    };

    const formatTime = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            timeStyle: 'short',
        }).format(date);
    };

    return {
        formatDate,
        formatDateTime,
        formatTime,
    };
}
