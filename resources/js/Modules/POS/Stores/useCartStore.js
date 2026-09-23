import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/**
 * Pinia Store untuk Keranjang Belanja POS.
 * Sesuai PRD v0.3:
 * - Menangani local frontend state (tambah/kurang quantity, hapus item, catatan kustom).
 * - 100% lokal, tidak ada query database per aksi.
 * - Nilai subtotal di sini hanya untuk UX preview; server tetap menjadi sumber kebenaran final.
 * - Jika checkout gagal, data keranjang tidak dihapus otomatis agar kasir bisa retry/koreksi.
 */
export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref([]); // Array of { product, quantity, note }
    const paymentMethod = ref('cash'); // 'cash' | 'qris'
    const cashReceived = ref(0);
    const isSubmitting = ref(false);

    // Getters
    const totalItems = computed(() => {
        return items.value.reduce((total, item) => total + item.quantity, 0);
    });

    const subtotal = computed(() => {
        return items.value.reduce((total, item) => {
            const price = Number(item.product.price) || 0;
            return total + price * item.quantity;
        }, 0);
    });

    const changeDue = computed(() => {
        if (paymentMethod.value !== 'cash') return 0;
        const received = Number(cashReceived.value) || 0;
        return Math.max(0, received - subtotal.value);
    });

    // Actions
    function addItem(product, quantity = 1, note = '') {
        // Abaikan jika produk tidak tersedia
        if (product.status === 'tidak_tersedia' || product.is_available === false) {
            return false;
        }

        const existingIndex = items.value.findIndex(
            (item) => item.product.id === product.id && (item.note || '') === (note || '')
        );

        if (existingIndex > -1) {
            items.value[existingIndex].quantity += quantity;
        } else {
            items.value.push({
                product,
                quantity,
                note: note || '',
            });
        }
        return true;
    }

    function updateQuantity(index, quantity) {
        if (items.value[index]) {
            if (quantity <= 0) {
                removeItem(index);
            } else {
                items.value[index].quantity = quantity;
            }
        }
    }

    function incrementQuantity(index) {
        if (items.value[index]) {
            items.value[index].quantity += 1;
        }
    }

    function decrementQuantity(index) {
        if (items.value[index]) {
            if (items.value[index].quantity > 1) {
                items.value[index].quantity -= 1;
            } else {
                removeItem(index);
            }
        }
    }

    function updateItemNote(index, note) {
        if (items.value[index]) {
            items.value[index].note = note;
        }
    }

    function removeItem(index) {
        items.value.splice(index, 1);
    }

    function clearCart() {
        items.value = [];
        cashReceived.value = 0;
        paymentMethod.value = 'cash';
    }

    return {
        items,
        paymentMethod,
        cashReceived,
        isSubmitting,
        totalItems,
        subtotal,
        changeDue,
        addItem,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        updateItemNote,
        removeItem,
        clearCart,
    };
});
