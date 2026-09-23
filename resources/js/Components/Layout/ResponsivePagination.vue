<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center justify-between py-4">
        <!-- Mobile Simple Navigation -->
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 text-xs font-semibold rounded-xl text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 active:scale-95 transition"
            >
                Sebelumnya
            </Link>
            <span
                v-else
                class="relative inline-flex items-center px-4 py-2 text-xs font-semibold rounded-xl text-gray-400 bg-gray-50 border border-gray-100 cursor-not-allowed"
            >
                Sebelumnya
            </span>

            <span class="text-xs text-gray-500 self-center">
                Hal {{ meta.current_page || 1 }} dari {{ meta.last_page || 1 }}
            </span>

            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="relative inline-flex items-center px-4 py-2 text-xs font-semibold rounded-xl text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 active:scale-95 transition"
            >
                Berikutnya
            </Link>
            <span
                v-else
                class="relative inline-flex items-center px-4 py-2 text-xs font-semibold rounded-xl text-gray-400 bg-gray-50 border border-gray-100 cursor-not-allowed"
            >
                Berikutnya
            </span>
        </div>

        <!-- Desktop Pagination Bar -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-gray-600">
                    Menampilkan
                    <span class="font-bold">{{ meta.from || 0 }}</span>
                    sampai
                    <span class="font-bold">{{ meta.to || 0 }}</span>
                    dari
                    <span class="font-bold">{{ meta.total || 0 }}</span>
                    hasil
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-xl shadow-xs -space-x-px" aria-label="Pagination">
                    <template v-for="(link, key) in links" :key="key">
                        <span
                            v-if="!link.url"
                            class="relative inline-flex items-center px-3 py-1.5 border border-gray-200 bg-white text-xs font-medium text-gray-400 cursor-not-allowed"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            class="relative inline-flex items-center px-3 py-1.5 border border-gray-200 text-xs font-semibold transition"
                            :class="[
                                link.active
                                    ? 'z-10 bg-indigo-600 border-indigo-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
