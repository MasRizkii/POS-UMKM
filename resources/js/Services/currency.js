export const DEFAULT_CURRENCY = 'IDR';

export function createCurrencyFormatter(currency = DEFAULT_CURRENCY) {
    const options = { style: 'currency', currency };
    // IDR is displayed without fractional rupiah for this MVP.
    if (currency === DEFAULT_CURRENCY) {
        options.minimumFractionDigits = 0;
        options.maximumFractionDigits = 0;
    }
    const formatter = new Intl.NumberFormat('id-ID', options);
    return {
        format(value) {
            const amount = Number(value);
            return formatter.format(Number.isFinite(amount) ? amount : 0);
        },
        symbol: formatter.formatToParts(0).find((part) => part.type === 'currency')?.value ?? currency,
    };
}
