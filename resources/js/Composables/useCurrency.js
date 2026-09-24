import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { createCurrencyFormatter, DEFAULT_CURRENCY } from '@/Services/currency';

export function useCurrency() {
    const page = usePage();
    const currency = computed(() => page.props.store?.currency || DEFAULT_CURRENCY);
    const formatter = computed(() => createCurrencyFormatter(currency.value));
    const formatCurrency = (value) => formatter.value.format(value);

    return {
        currency,
        currencySymbol: computed(() => formatter.value.symbol),
        formatCurrency,
        // Compatibility alias: every existing amount now uses the runtime setting.
        formatRupiah: formatCurrency,
    };
}
