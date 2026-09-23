<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Settings, 
    Store, 
    Receipt, 
    Percent, 
    CreditCard, 
    Check, 
    AlertCircle, 
    Info, 
    Clock, 
    MapPin, 
    Phone, 
    Image as ImageIcon,
    Banknote,
    QrCode
} from 'lucide-vue-next';

const props = defineProps({
    setting: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const form = useForm({
    store_name: props.setting.store_name || '',
    store_address: props.setting.store_address || '',
    store_phone: props.setting.store_phone || '',
    store_logo: props.setting.store_logo || '',
    invoice_prefix: props.setting.invoice_prefix || 'INV',
    timezone: props.setting.timezone || 'Asia/Jakarta',
    tax_enabled: Boolean(props.setting.tax_enabled),
    tax_percentage: Number(props.setting.tax_percentage) || 0,
    service_charge_enabled: Boolean(props.setting.service_charge_enabled),
    service_charge_percentage: Number(props.setting.service_charge_percentage) || 0,
    cash_enabled: Boolean(props.setting.cash_enabled),
    qris_enabled: Boolean(props.setting.qris_enabled),
});

const submit = () => {
    form.put(route('settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Pengaturan Toko - Foodislice POS" />

        <div class="p-4 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-text-primary tracking-tight">
                        Pengaturan Toko & POS
                    </h1>
                    <p class="text-xs text-text-muted mt-0.5">
                        Kelola profil identitas toko, format nomor nota transaksi, konfigurasi pajak PB1, dan metode pembayaran.
                    </p>
                </div>
            </div>

            <!-- Flash Success / Error Banner -->
            <div
                v-if="page.props.flash?.success"
                class="p-4 rounded-2xl bg-secondary-subtle border border-secondary/20 text-secondary text-xs font-bold flex items-center gap-2 animate-in fade-in"
            >
                <Check class="w-4 h-4 shrink-0" />
                <span>{{ page.props.flash.success }}</span>
            </div>

            <div
                v-if="form.errors.cash_enabled"
                class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-error-alert text-xs font-bold flex items-center gap-2"
            >
                <AlertCircle class="w-4 h-4 shrink-0" />
                <span>{{ form.errors.cash_enabled }}</span>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <!-- 1. Identitas Toko -->
                <div class="bg-surface rounded-3xl p-6 border border-border-subtle shadow-xs flex flex-col gap-5">
                    <div class="flex items-center gap-3 pb-4 border-b border-border-subtle">
                        <div class="w-9 h-9 rounded-2xl bg-primary-subtle text-primary flex items-center justify-center">
                            <Store class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-text-primary">Profil & Identitas UMKM</h2>
                            <p class="text-xs text-text-muted">Informasi ini dicetak pada nota/struk belanja pelanggan.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Toko -->
                        <div class="flex flex-col gap-1 sm:col-span-2">
                            <label class="text-xs font-bold text-text-primary">Nama Usaha / Toko *</label>
                            <input
                                v-model="form.store_name"
                                type="text"
                                required
                                class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-bold focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                            />
                            <p v-if="form.errors.store_name" class="text-xs text-error-alert">{{ form.errors.store_name }}</p>
                        </div>

                        <!-- Alamat Toko -->
                        <div class="flex flex-col gap-1 sm:col-span-2">
                            <label class="text-xs font-bold text-text-primary">Alamat Fisik Toko</label>
                            <div class="relative">
                                <input
                                    v-model="form.store_address"
                                    type="text"
                                    placeholder="Jl. Kuliner No. 12, Jakarta Selatan"
                                    class="w-full h-11 pl-10 pr-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                                />
                                <MapPin class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>

                        <!-- Telepon -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-text-primary">Nomor Kontak / WhatsApp</label>
                            <div class="relative">
                                <input
                                    v-model="form.store_phone"
                                    type="text"
                                    placeholder="0812-3456-7890"
                                    class="w-full h-11 pl-10 pr-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                                />
                                <Phone class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>

                        <!-- URL Logo -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-text-primary">URL Logo Toko (Opsional)</label>
                            <div class="relative">
                                <input
                                    v-model="form.store_logo"
                                    type="url"
                                    placeholder="https://domain.com/logo.png"
                                    class="w-full h-11 pl-10 pr-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                                />
                                <ImageIcon class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Format Transaksi & Lokalisasi -->
                <div class="bg-surface rounded-3xl p-6 border border-border-subtle shadow-xs flex flex-col gap-5">
                    <div class="flex items-center gap-3 pb-4 border-b border-border-subtle">
                        <div class="w-9 h-9 rounded-2xl bg-secondary-subtle text-secondary flex items-center justify-center">
                            <Receipt class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-text-primary">Format Nota & Penomoran</h2>
                            <p class="text-xs text-text-muted">Standar format penomoran invoice kasir dan zona waktu operasional.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Invoice Prefix -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-text-primary">Prefix Nota Transaksi *</label>
                            <input
                                v-model="form.invoice_prefix"
                                type="text"
                                required
                                maxlength="10"
                                class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs font-mono font-bold text-text-primary focus:ring-2 focus:ring-primary focus:bg-white outline-none uppercase"
                            />
                            <p class="text-[10px] text-text-muted mt-0.5">
                                Preview: <span class="font-bold text-primary font-mono">{{ form.invoice_prefix || 'INV' }}-20260922-0001</span>
                            </p>
                        </div>

                        <!-- Zona Waktu -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-text-primary">Zona Waktu Operasional *</label>
                            <div class="relative">
                                <select
                                    v-model="form.timezone"
                                    required
                                    class="w-full h-11 pl-10 pr-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs font-bold text-text-primary focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                                >
                                    <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                                    <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                                    <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                                </select>
                                <Clock class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Pajak & Service Charge -->
                <div class="bg-surface rounded-3xl p-6 border border-border-subtle shadow-xs flex flex-col gap-5">
                    <div class="flex items-center gap-3 pb-4 border-b border-border-subtle">
                        <div class="w-9 h-9 rounded-2xl bg-primary-subtle text-primary flex items-center justify-center">
                            <Percent class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-text-primary">Kalkulasi Pajak & Layanan (PB1)</h2>
                            <p class="text-xs text-text-muted">Kalkulasi otomatis saat kasir memproses pesanan di terminal POS.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Toggle Pajak -->
                        <div class="p-4 rounded-2xl bg-surface-container-low border border-border-subtle flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-text-primary">Pajak Restoran (PB1)</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.tax_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.tax_percentage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    :disabled="!form.tax_enabled"
                                    class="w-24 h-9 px-3 rounded-xl bg-white border border-border-subtle text-xs font-bold text-text-primary disabled:opacity-40"
                                />
                                <span class="text-xs font-bold text-text-muted">% dari Subtotal</span>
                            </div>
                        </div>

                        <!-- Toggle Service Charge -->
                        <div class="p-4 rounded-2xl bg-surface-container-low border border-border-subtle flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-text-primary">Biaya Layanan (Service)</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.service_charge_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.service_charge_percentage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    :disabled="!form.service_charge_enabled"
                                    class="w-24 h-9 px-3 rounded-xl bg-white border border-border-subtle text-xs font-bold text-text-primary disabled:opacity-40"
                                />
                                <span class="text-xs font-bold text-text-muted">% dari Subtotal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Metode Pembayaran Kasir -->
                <div class="bg-surface rounded-3xl p-6 border border-border-subtle shadow-xs flex flex-col gap-5">
                    <div class="flex items-center gap-3 pb-4 border-b border-border-subtle">
                        <div class="w-9 h-9 rounded-2xl bg-secondary-subtle text-secondary flex items-center justify-center">
                            <CreditCard class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-text-primary">Metode Pembayaran Kasir</h2>
                            <p class="text-xs text-text-muted">Aktifkan opsi pembayaran yang diterima di kasir terminal.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Cash Payment -->
                        <div class="p-4 rounded-2xl bg-surface-container-low border border-border-subtle flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-primary shadow-xs">
                                    <Banknote class="w-5 h-5" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-text-primary">Uang Tunai (Cash)</span>
                                    <span class="text-[10px] text-text-muted">Kalkulator nominal & kembalian uang pas</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.cash_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                            </label>
                        </div>

                        <!-- QRIS Fisik Payment -->
                        <div class="p-4 rounded-2xl bg-surface-container-low border border-border-subtle flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-secondary shadow-xs">
                                    <QrCode class="w-5 h-5" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-text-primary">QRIS Statis / Fisik</span>
                                    <span class="text-[10px] text-text-muted">Verifikasi bukti bayar manual kasir</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.qris_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle flex items-start gap-2.5">
                        <Info class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                        <p class="text-[11px] text-text-muted leading-relaxed">
                            Sesuai batasan PRD UMKM v0.3, sistem tidak memerlukan gateway pihak ketiga berbayar (Midtrans/Xendit). Kasir hanya perlu menekan tombol konfirmasi bayar setelah memverifikasi nominal tunai atau bukti struk QRIS pelanggan.
                        </p>
                    </div>
                </div>

                <!-- Save Action Button -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-primary hover:bg-primary-dark text-white text-xs font-bold shadow-lg shadow-primary/20 active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <Check class="w-4 h-4" />
                        <span>{{ form.processing ? 'Menyimpan Perubahan...' : 'Simpan Pengaturan Toko' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
