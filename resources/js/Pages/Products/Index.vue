<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProductFormModal from '@/Modules/Product/Components/ProductFormModal.vue';
import CategoryManagerModal from '@/Modules/Product/Components/CategoryManagerModal.vue';
import ResponsivePagination from '@/Components/Layout/ResponsivePagination.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    Package, 
    Plus, 
    Tag, 
    Search, 
    Edit, 
    Trash2, 
    CheckCircle, 
    UtensilsCrossed, 
    FolderTree,
    RotateCcw
} from 'lucide-vue-next';

const { formatRupiah } = useCurrency();
const { formatDate, formatTime } = useDateTime();

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({
            total_products: 0,
            available_products: 0,
            unavailable_products: 0,
            total_categories: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || '');
const selectedStatus = ref(props.filters.status || '');

const showProductModal = ref(false);
const showCategoryModal = ref(false);
const editingProduct = ref(null);

const applyFilters = () => {
    router.get(
        route('products.index'),
        {
            search: searchQuery.value,
            category_id: selectedCategory.value,
            status: selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const filterCategory = (catId) => {
    selectedCategory.value = catId;
    applyFilters();
};

const filterStatus = (status) => {
    selectedStatus.value = status;
    applyFilters();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    selectedStatus.value = '';
    router.get(route('products.index'));
};

const openCreateProduct = () => {
    editingProduct.value = null;
    showProductModal.value = true;
};

const openEditProduct = (prod) => {
    editingProduct.value = prod;
    showProductModal.value = true;
};

const toggleProductStatus = (prod) => {
    router.patch(route('products.toggle-status', prod.id), {}, { preserveScroll: true });
};

const deleteProduct = (prod) => {
    if (confirm(`Apakah Anda yakin ingin menonaktifkan produk "${prod.name}"? (Histori transaksi lama tetap aman)`)) {
        router.delete(route('products.destroy', prod.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Produk &amp; Kategori - Foodislice POS" />

        <div class="p-4 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Header Section with Action Buttons matching Stitch -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-bold tracking-tight text-on-surface">Produk</h1>
                    <p class="text-xs text-text-muted mt-1">
                        Kelola daftar menu makanan, minuman, harga, dan ketersediaan stok gerai F&amp;B
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showCategoryModal = true"
                        class="flex items-center gap-2 px-4 h-10 rounded-xl bg-surface hover:bg-surface-container border border-border-subtle text-on-surface font-semibold text-xs shadow-xs transition-all cursor-pointer"
                    >
                        <Tag class="w-4 h-4 text-primary" />
                        <span>Kelola Kategori</span>
                    </button>
                    <button
                        type="button"
                        @click="openCreateProduct"
                        class="flex items-center gap-2 px-5 h-10 rounded-xl bg-primary text-white hover:bg-primary-dark font-bold text-xs shadow-md shadow-primary/20 active:scale-95 transition-all cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Produk</span>
                    </button>
                </div>
            </div>

            <!-- Metric Snapshot Bar (3 Cards) matching Stitch -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total Katalog Menu -->
                <div class="p-4 bg-surface rounded-xl shadow-xs border border-border-subtle flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-text-muted uppercase">Total Katalog Menu</span>
                        <span class="text-xl font-black text-on-surface mt-1">{{ metrics.total_products }} Item</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary">
                        <UtensilsCrossed class="w-5 h-5" />
                    </div>
                </div>

                <!-- Siap Dijual (Aktif) -->
                <div class="p-4 bg-surface rounded-xl shadow-xs border border-border-subtle flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-secondary uppercase">Siap Dijual (Aktif)</span>
                        <span class="text-xl font-black text-secondary mt-1">{{ metrics.available_products }} Item</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-secondary-fixed/40 flex items-center justify-center text-secondary">
                        <CheckCircle class="w-5 h-5" />
                    </div>
                </div>

                <!-- Kategori Aktif -->
                <div class="p-4 bg-surface rounded-xl shadow-xs border border-border-subtle flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-text-muted uppercase">Kategori Aktif</span>
                        <span class="text-xl font-black text-on-surface mt-1">{{ metrics.total_categories }} Kategori</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-tertiary">
                        <FolderTree class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Filters and Search Toolbar matching Stitch -->
            <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Search Box -->
                <div class="relative w-full lg:w-96">
                    <Search class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        @keyup.enter="applyFilters"
                        placeholder="Cari nama produk atau kategori..."
                        class="w-full pl-10 pr-4 py-2 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- Category Pills & Status Tabs -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Category Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                        <button
                            type="button"
                            @click="filterCategory('')"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition cursor-pointer"
                            :class="selectedCategory === '' ? 'bg-primary-subtle text-primary font-bold shadow-2xs' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            Semua
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            @click="filterCategory(cat.id)"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition cursor-pointer"
                            :class="selectedCategory == cat.id ? 'bg-primary-subtle text-primary font-bold shadow-2xs' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            {{ cat.icon }} {{ cat.name }}
                        </button>
                    </div>

                    <!-- Divider -->
                    <div class="hidden xl:block h-6 w-[1px] bg-border-subtle"></div>

                    <!-- Status Toggle Pills -->
                    <div class="flex items-center bg-background p-1 rounded-xl border border-border-subtle">
                        <button
                            type="button"
                            @click="filterStatus('')"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                            :class="selectedStatus === '' ? 'bg-surface text-on-surface font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            Semua ({{ metrics.total_products }})
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('tersedia')"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
                            :class="selectedStatus === 'tersedia' ? 'bg-surface text-secondary font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            <span class="w-2 h-2 rounded-full bg-secondary"></span>
                            <span>Tersedia ({{ metrics.available_products }})</span>
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('tidak_tersedia')"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
                            :class="selectedStatus === 'tidak_tersedia' ? 'bg-surface text-error font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            <span class="w-2 h-2 rounded-full bg-text-muted"></span>
                            <span>Habis ({{ metrics.unavailable_products }})</span>
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="p-2 rounded-xl border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition cursor-pointer"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Table Card matching Stitch -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-surface-container-low text-text-muted text-[11px] font-bold uppercase tracking-wider border-b border-border-subtle">
                            <tr>
                                <th class="py-3.5 px-6">Produk</th>
                                <th class="py-3.5 px-4">Kategori</th>
                                <th class="py-3.5 px-4">Harga Jual</th>
                                <th class="py-3.5 px-4">Ketersediaan</th>
                                <th class="py-3.5 px-4">Terakhir Diperbarui</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/60 text-on-surface">
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="hover:bg-surface-container-low/60 transition-colors"
                            >
                                <!-- Produk (Image + Name) -->
                                <td class="py-3 px-6">
                                    <div class="flex items-center gap-3 min-w-[200px]">
                                        <div class="w-12 h-12 rounded-lg bg-surface-container overflow-hidden shrink-0 border border-border-subtle/50">
                                            <img
                                                v-if="product.image_url"
                                                :src="product.image_url"
                                                :alt="product.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-primary font-bold text-xs">
                                                {{ product.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-sm text-on-surface truncate">{{ product.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kategori -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full bg-surface-container text-xs font-semibold text-on-surface-variant">
                                        {{ product.category?.name || 'Umum' }}
                                    </span>
                                </td>

                                <!-- Harga Jual -->
                                <td class="py-3 px-4 whitespace-nowrap font-bold text-sm text-primary">
                                    {{ formatRupiah(product.price) }}
                                </td>

                                <!-- Ketersediaan Switch Toggle -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :checked="product.status === 'tersedia'"
                                            @change="toggleProductStatus(product)"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary relative"></div>
                                        <span class="ml-2 text-xs font-semibold" :class="product.status === 'tersedia' ? 'text-secondary font-bold' : 'text-text-muted'">
                                            {{ product.status === 'tersedia' ? 'Tersedia' : 'Habis' }}
                                        </span>
                                    </label>
                                </td>

                                <!-- Terakhir Diperbarui -->
                                <td class="py-3 px-4 whitespace-nowrap text-text-muted">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-on-surface">{{ formatDate(product.updated_at) }}</span>
                                        <span class="text-[10px]">{{ formatTime(product.updated_at) }} WIB</span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openEditProduct(product)"
                                            class="p-2 rounded-lg text-text-muted hover:text-primary hover:bg-primary-subtle transition-colors cursor-pointer"
                                            title="Edit Produk"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteProduct(product)"
                                            class="p-2 rounded-lg text-text-muted hover:text-error hover:bg-error-container/40 transition-colors cursor-pointer"
                                            title="Nonaktifkan Produk"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-text-muted">
                                    <Package class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                                    <p class="font-bold text-sm">Tidak ada produk ditemukan</p>
                                    <p class="text-xs text-text-muted">Coba ubah kata kunci pencarian atau kategori filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Responsive Card View (< md) -->
                <div class="block md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="product in products.data"
                        :key="product.id"
                        class="p-4 flex flex-col gap-3"
                    >
                        <div class="flex items-start gap-3">
                            <div class="w-14 h-14 rounded-xl bg-surface-container overflow-hidden shrink-0 border border-border-subtle/50">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-primary font-bold text-xs">
                                    {{ product.name.substring(0, 2).toUpperCase() }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-1">
                                    <span class="font-bold text-sm text-on-surface truncate">{{ product.name }}</span>
                                    <span class="font-bold text-sm text-primary shrink-0">{{ formatRupiah(product.price) }}</span>
                                </div>
                                <span class="text-xs text-text-muted block mt-0.5">{{ product.category?.name || 'Umum' }}</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-border-subtle flex items-center justify-between">
                            <label class="inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="product.status === 'tersedia'"
                                    @change="toggleProductStatus(product)"
                                    class="sr-only peer"
                                />
                                <div class="w-10 h-5 bg-surface-container peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-secondary relative"></div>
                                <span class="ml-2 text-xs font-semibold" :class="product.status === 'tersedia' ? 'text-secondary' : 'text-text-muted'">
                                    {{ product.status === 'tersedia' ? 'Tersedia' : 'Habis' }}
                                </span>
                            </label>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openEditProduct(product)"
                                    class="p-2 rounded-lg bg-surface-container-low text-on-surface text-xs font-semibold flex items-center gap-1"
                                >
                                    <Edit class="w-3.5 h-3.5" />
                                    <span>Edit</span>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteProduct(product)"
                                    class="p-2 rounded-lg bg-error-container/40 text-error text-xs font-semibold flex items-center gap-1"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="products.data.length === 0" class="py-12 text-center text-text-muted px-4">
                        <Package class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                        <p class="font-bold text-sm">Tidak ada produk ditemukan</p>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-border-subtle bg-surface flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-text-muted">
                        Menampilkan <strong class="text-on-surface">{{ products.from || 0 }} - {{ products.to || 0 }}</strong> dari <strong class="text-on-surface">{{ products.total }}</strong> item
                    </span>
                    <ResponsivePagination :links="products.links" />
                </div>
            </div>
        </div>

        <!-- Product Form Modal -->
        <ProductFormModal
            :show="showProductModal"
            :product="editingProduct"
            :categories="categories"
            @close="showProductModal = false"
        />

        <!-- Category Manager Modal -->
        <CategoryManagerModal
            :show="showCategoryModal"
            :categories="categories"
            @close="showCategoryModal = false"
        />
    </AuthenticatedLayout>
</template>
