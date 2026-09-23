/**
 * Utilitas untuk menghasilkan token idempotensi unik untuk setiap transaksi checkout.
 * Mencegah submit ganda saat tombol ditekan berulang atau koneksi lambat.
 */
export function generateIdempotencyKey() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'pos-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9);
}
