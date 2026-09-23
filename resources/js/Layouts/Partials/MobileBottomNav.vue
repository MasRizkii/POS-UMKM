<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    LayoutDashboard, 
    ShoppingCart, 
    Receipt, 
    Package, 
    Menu as MenuIcon 
} from 'lucide-vue-next';

const page = usePage();
const currentRoute = computed(() => page.url);

const emit = defineEmits(['open-menu']);

const navItems = [
    { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { name: 'POS', href: '/pos', icon: ShoppingCart },
    { name: 'Transaksi', href: '/transactions', icon: Receipt },
    { name: 'Produk', href: '/products', icon: Package },
];

const isActive = (href) => {
    return currentRoute.value.startsWith(href);
};
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-100 lg:hidden shadow-lg safe-area-pb">
        <div class="grid grid-cols-5 h-16 max-w-md mx-auto">
            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                class="flex flex-col items-center justify-center gap-1 transition-colors touch-manipulation active:scale-95"
                :class="[
                    isActive(item.href)
                        ? 'text-indigo-600 font-semibold'
                        : 'text-gray-400 hover:text-gray-600',
                ]"
            >
                <component :is="item.icon" class="w-5 h-5 stroke-[2.2]" />
                <span class="text-[10px] tracking-tight">{{ item.name }}</span>
            </Link>

            <!-- Menu Button for Additional Items (Report, User, Settings, Logout) -->
            <button
                type="button"
                @click="$emit('open-menu')"
                class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-gray-600 transition-colors touch-manipulation active:scale-95"
            >
                <MenuIcon class="w-5 h-5 stroke-[2.2]" />
                <span class="text-[10px] tracking-tight">Menu</span>
            </button>
        </div>
    </nav>
</template>

<style scoped>
.safe-area-pb {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
