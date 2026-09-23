/**
 * Composable untuk memformat nominal uang ke format Rupiah standar Indonesia.
 */
export function useCurrency() {
    const formatRupiah = (value) => {
        const numericValue = Number(value) || 0;
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(numericValue);
    };

    return {
        formatRupiah,
    };
}
