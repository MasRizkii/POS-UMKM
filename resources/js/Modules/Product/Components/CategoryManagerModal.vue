<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { X, Plus, Trash2, Tag } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['close']);

const form = useForm({
    name: '',
    icon: '🥤',
});
const editingCategory = ref(null);

const submitCategory = () => {
    const options = { onSuccess: () => { form.reset(); form.icon = '🥤'; editingCategory.value = null; } };
    if (editingCategory.value) {
        form.put(route('categories.update', editingCategory.value.id), options);
    } else {
        form.post(route('categories.store'), options);
    }
};

const editCategory = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.icon = category.icon || '🥤';
};

const deleteCategory = (category) => {
    if (confirm(`Apakah Anda yakin ingin menonaktifkan kategori "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id));
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <!-- Card -->
        <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-border-subtle z-10 flex flex-col gap-4">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-primary-subtle text-primary flex items-center justify-center">
                        <Tag class="w-5 h-5" />
                    </div>
                    <h3 class="font-black text-base text-text-primary">Kelola Kategori Produk</h3>
                </div>
                <button @click="$emit('close')" class="p-1 rounded-full text-text-muted hover:text-text-primary">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Add Category Form -->
            <form @submit.prevent="submitCategory" class="flex gap-2">
                <input
                    v-model="form.icon"
                    type="text"
                    placeholder="Emoji"
                    class="w-16 h-11 text-center rounded-xl bg-surface-container-low border border-border-subtle text-sm focus:ring-2 focus:ring-primary outline-none"
                    maxlength="4"
                />
                <input
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="Nama Kategori Baru..."
                    class="flex-1 h-11 px-3 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary focus:ring-2 focus:ring-primary outline-none"
                />
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="h-11 px-4 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold flex items-center justify-center gap-1 shadow-sm cursor-pointer disabled:opacity-50"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah</span>
                </button>
            </form>
            <p v-if="form.errors.name" class="text-xs text-error-alert -mt-2">{{ form.errors.name }}</p>

            <!-- Category List -->
            <div class="flex flex-col gap-2 max-h-60 overflow-y-auto pr-1 mt-1">
                <span class="text-xs font-bold text-text-muted">Kategori Aktif:</span>
                <div
                    v-for="cat in categories"
                    :key="cat.id"
                    class="flex items-center justify-between p-2.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ cat.icon || '🥤' }}</span>
                        <button type="button" class="font-bold text-text-primary" @click="editCategory(cat)">{{ cat.name }}</button>
                    </div>
                    <button
                        type="button"
                        @click="deleteCategory(cat)"
                        class="p-1 text-text-muted hover:text-error-alert transition cursor-pointer"
                        title="Hapus Kategori"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
