<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import OfflineBanner from './Partials/OfflineBanner.vue';
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import {
    LayoutDashboard,
    ShoppingCart,
    Package,
    Receipt,
    BarChart3,
    Users,
    Clock3,
    Settings,
    ClipboardList,
    LogOut,
    UtensilsCrossed,
    Menu as MenuIcon,
    X as CloseIcon,
    Bell,
    User as UserIcon,
    CheckCircle
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const isAdmin = computed(() => user.value.role === 'admin');
const currentUrl = computed(() => page.url);
const { isOnline } = useNetworkStatus();

const showMobileMenu = ref(false);

const navLinks = computed(() => {
    const links = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, adminOnly: false },
        { name: 'Kasir / POS', href: '/pos', icon: ShoppingCart, adminOnly: false },
        { name: 'Produk', href: '/products', icon: Package, adminOnly: true },
        { name: 'Transaksi', href: '/transactions', icon: Receipt, adminOnly: false },
        { name: 'Shift', href: '/shifts', icon: Clock3, adminOnly: false },
        { name: 'Laporan', href: '/reports', icon: BarChart3, adminOnly: true },
        { name: 'User Management', href: '/users', icon: Users, adminOnly: true },
        { name: 'Pengaturan', href: '/settings', icon: Settings, adminOnly: true },
        { name: 'Audit Log', href: '/audits', icon: ClipboardList, adminOnly: true },
    ];

    if (!isAdmin.value) {
        return links.filter(l => !l.adminOnly);
    }
    return links;
});

const isLinkActive = (href) => {
    if (href === '/dashboard') {
        return currentUrl.value === '/dashboard';
    }
    return currentUrl.value.startsWith(href);
};

const handleLogout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-background font-sans text-text-primary flex flex-col selection:bg-primary-subtle selection:text-primary">
        <!-- Offline Detector Banner -->
        <OfflineBanner />

        <!-- Desktop Sidebar (Hidden on Mobile) matching Stitch -->
        <aside class="hidden lg:flex fixed left-0 top-0 h-full w-64 bg-surface z-50 flex-col justify-between shadow-[0_1px_8px_rgba(0,0,0,0.04)] border-r border-border-subtle">
            <div class="flex flex-col flex-1">
                <!-- Brand Header -->
                <div class="h-16 px-6 flex items-center gap-3 border-b border-border-subtle/40">
                    <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <UtensilsCrossed class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div class="flex items-center">
                        <span class="text-lg font-black tracking-tight text-text-primary">foodi</span>
                        <span class="text-lg font-black tracking-tight text-primary">slice</span>
                    </div>
                </div>

                <!-- Menu Section Label -->
                <div class="px-5 py-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">
                        Menu Kasir &amp; Admin
                    </span>
                </div>

                <!-- Navigation Links List -->
                <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
                    <Link
                        v-for="link in navLinks"
                        :key="link.name"
                        :href="link.href"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all text-sm font-semibold"
                        :class="[
                            isLinkActive(link.href)
                                ? 'bg-primary-subtle text-primary font-bold shadow-2xs'
                                : 'text-text-muted hover:bg-surface-container-low hover:text-text-primary',
                        ]"
                    >
                        <component :is="link.icon" class="w-5 h-5 shrink-0" />
                        <span>{{ link.name }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Bottom Logout Area -->
            <div class="p-4 border-t border-border-subtle">
                <button
                    type="button"
                    @click="handleLogout"
                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-text-muted hover:bg-rose-50 hover:text-error-alert transition-colors text-sm font-bold cursor-pointer"
                >
                    <LogOut class="w-5 h-5" />
                    <span>Keluar Sesi</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area with Header -->
        <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
            <!-- Sticky Top App Bar (Responsive for Desktop & Mobile) -->
            <header class="sticky top-0 z-40 h-16 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] border-b border-border-subtle flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Mobile Brand (Visible only on < lg) -->
                <div class="flex items-center gap-2.5 lg:hidden">
                    <div class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-white shadow-sm">
                        <UtensilsCrossed class="w-4 h-4 stroke-[2.2]" />
                    </div>
                    <div class="flex items-center">
                        <span class="text-base font-black tracking-tight text-text-primary">foodi</span>
                        <span class="text-base font-black tracking-tight text-primary">slice</span>
                    </div>
                </div>

                <!-- Desktop Search / Slot Area -->
                <div class="hidden lg:flex items-center flex-1 max-w-md">
                    <slot name="top-left" />
                </div>

                <!-- Right Header Actions (Online indicator, user info, hamburger for mobile) -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Status Shift Online Pill -->
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" :class="isOnline ? 'bg-secondary-fixed/40 text-secondary' : 'bg-rose-100 text-error-alert'">
                        <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-secondary animate-pulse' : 'bg-error-alert'"></span>
                        <span>{{ isOnline ? 'Online' : 'Offline' }}</span>
                    </div>

                    <!-- User Profile Snapshot -->
                    <div class="flex items-center gap-2.5">
                        <div class="hidden md:flex flex-col text-right">
                            <span class="text-xs font-bold text-text-primary leading-tight">
                                {{ user.name || 'Kasir' }}
                            </span>
                            <span class="text-[10px] text-text-muted leading-tight">
                                {{ user.role === 'admin' ? 'Admin' : 'Kasir' }}
                            </span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xs shadow-xs">
                            {{ (user.name || 'K').substring(0, 2).toUpperCase() }}
                        </div>
                    </div>

                    <!-- Mobile Hamburger Button (< lg) -->
                    <button
                        type="button"
                        @click="showMobileMenu = !showMobileMenu"
                        class="lg:hidden p-2 rounded-xl text-text-muted hover:bg-surface-container-low hover:text-text-primary transition-colors cursor-pointer"
                        :aria-expanded="showMobileMenu"
                        aria-label="Toggle Menu"
                    >
                        <component :is="showMobileMenu ? CloseIcon : MenuIcon" class="w-5 h-5 stroke-[2.2]" />
                    </button>
                </div>
            </header>

            <!-- Mobile Sticky Dropdown Navigation (When Hamburger Clicked) -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="showMobileMenu"
                    class="lg:hidden sticky top-16 z-30 bg-surface/95 backdrop-blur-xl border-b border-border-subtle shadow-lg px-4 py-4 flex flex-col gap-3"
                >
                    <!-- Mobile User Greeting Strip -->
                    <div class="p-3 rounded-2xl bg-surface-container-low flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xs">
                                {{ (user.name || 'K').substring(0, 2).toUpperCase() }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-text-primary">{{ user.name || 'Kasir' }}</span>
                                <span class="text-[10px] text-text-muted">{{ user.email || '' }}</span>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700'"
                        >
                            {{ user.role === 'admin' ? 'Admin' : 'Kasir' }}
                        </span>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="flex flex-col gap-1">
                        <Link
                            v-for="link in navLinks"
                            :key="link.name"
                            :href="link.href"
                            @click="showMobileMenu = false"
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isLinkActive(link.href)
                                    ? 'bg-primary-subtle text-primary font-bold shadow-2xs'
                                    : 'text-text-muted hover:bg-surface-container-low hover:text-text-primary',
                            ]"
                        >
                            <component :is="link.icon" class="w-5 h-5 shrink-0" />
                            <span>{{ link.name }}</span>
                        </Link>
                    </nav>

                    <!-- Mobile Logout Button -->
                    <div class="pt-2 border-t border-border-subtle">
                        <button
                            type="button"
                            @click="handleLogout"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-rose-50 text-error-alert font-bold text-xs hover:bg-rose-100 transition-colors cursor-pointer"
                        >
                            <LogOut class="w-4 h-4" />
                            <span>Keluar Sesi</span>
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Page Body (pb-8 for clean spacing without bottom nav) -->
            <main class="flex-1 pb-8">
                <slot />
            </main>
        </div>
    </div>
</template>
