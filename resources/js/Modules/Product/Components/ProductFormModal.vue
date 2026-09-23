<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Package, Check, Image as ImageIcon, CheckCircle, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    product: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    category_id: '',
    name: '',
    price: '',
    image_url: '',
    description: '',
    status: 'tersedia',
});

watch(
    () => props.product,
    (prod) => {
        if (prod) {
            form.category_id = prod.category_id;
            form.name = prod.name;
            form.price = prod.price;
            form.image_url = prod.image_url || '';
            form.description = prod.description || '';
            form.status = prod.status || 'tersedia';
        } else {
            form.reset();
            form.category_id = props.categories[0]?.id || '';
            form.status = 'tersedia';
        }
    },
    { immediate: true }
);

const submit = () => {
    if (props.product) {
        form.put(route('products.update', props.product.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('products.store'), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs">
        <div class="bg-surface w-full max-w-lg max-h-[92vh] rounded-2xl shadow-2xl border border-border-subtle flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header matching Stitch -->
            <div class="p-4 bg-surface-container-low flex items-center justify-between border-b border-border-subtle shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-subtle text-primary flex items-center justify-center">
                        <Package class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-on-surface">
                            {{ product ? 'Edit Produk Menu' : 'Tambah Produk Menu' }}
                        </h3>
                        <p class="text-xs text-text-muted">
                            {{ product ? 'Perbarui informasi menu, harga, atau foto' : 'Lengkapi data katalog menu dan harga jual gerai' }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1.5 rounded-lg text-text-muted hover:bg-surface-container hover:text-on-surface transition-colors cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Form Content Scrollable -->
            <form @submit.prevent="submit" class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Image URL & Preview -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">URL Foto Produk (Opsional)</label>
                    <div class="flex gap-3 items-center">
                        <div class="w-14 h-14 rounded-xl bg-surface-container-low border border-border-subtle flex items-center justify-center shrink-0 overflow-hidden">
                            <img
                                v-if="form.image_url"
                                :src="form.image_url"
                                alt="Preview"
                                class="w-full h-full object-cover"
                                @error="form.image_url = ''"
                            />
                            <ImageIcon v-else class="w-6 h-6 text-text-muted/60" />
                        </div>
                        <input
                            v-model="form.image_url"
                            type="url"
                            placeholder="https://images.unsplash.com/..."
                            class="flex-1 px-3 py-2 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                    </div>
                    <p v-if="form.errors.image_url" class="text-xs text-error-alert">{{ form.errors.image_url }}</p>
                </div>

                <!-- Product Name -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">
                        Nama Produk <span class="text-primary">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Contoh: Es Kopi Susu Senja"
                        class="w-full px-3.5 py-2.5 bg-background rounded-xl text-xs text-on-surface border border-border-subtle font-medium focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <p v-if="form.errors.name" class="text-xs text-error-alert">{{ form.errors.name }}</p>
                </div>

                <!-- Category & Price Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Category -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-on-surface">
                            Kategori Menu <span class="text-primary">*</span>
                        </label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full px-3.5 py-2.5 bg-background rounded-xl text-xs font-semibold text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.icon }} {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="text-xs text-error-alert">{{ form.errors.category_id }}</p>
                    </div>

                    <!-- Price with Rp Prefix -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-on-surface">
                            Harga Jual (Rp) <span class="text-primary">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-text-muted">Rp</span>
                            <input
                                v-model="form.price"
                                type="number"
                                required
                                min="0"
                                placeholder="18000"
                                class="w-full pl-10 pr-3.5 py-2.5 bg-background rounded-xl text-xs font-bold text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>
                        <p v-if="form.errors.price" class="text-xs text-error-alert">{{ form.errors.price }}</p>
                    </div>
                </div>

                <!-- Short Description -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">Deskripsi Singkat</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Racikan kopi espresso dengan susu segar dan sirup aren alami..."
                        class="w-full px-3.5 py-2 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                    ></textarea>
                </div>

                <!-- Availability Status Cards matching Stitch -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">Status Ketersediaan</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                            :class="form.status === 'tersedia' ? 'bg-secondary-fixed/30 border-secondary' : 'bg-surface-container-low border-border-subtle'"
                        >
                            <input
                                type="radio"
                                v-model="form.status"
                                value="tersedia"
                                class="mt-0.5 text-secondary focus:ring-secondary"
                            />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-on-surface">Tersedia</span>
                                <span class="text-[10px] text-text-muted">Siap dipesan di POS kasir</span>
                            </div>
                        </label>

                        <label
                            class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                            :class="form.status === 'tidak_tersedia' ? 'bg-error-container/40 border-error' : 'bg-surface-container-low border-border-subtle'"
                        >
                            <input
                                type="radio"
                                v-model="form.status"
                                value="tidak_tersedia"
                                class="mt-0.5 text-error focus:ring-error"
                            />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-on-surface">Tidak Tersedia</span>
                                <span class="text-[10px] text-text-muted">Tandai bahan baku kosong</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-border-subtle flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2.5 rounded-xl bg-background hover:bg-surface-container-low text-on-surface text-xs font-semibold transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-primary text-white hover:bg-primary-dark text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Check class="w-4 h-4" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Produk' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
