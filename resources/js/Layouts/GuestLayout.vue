<script setup>
import { UtensilsCrossed, Shield, Wifi } from 'lucide-vue-next';
</script>

<template>
    <div class="min-h-screen bg-background text-text-primary flex flex-col justify-between selection:bg-primary-subtle selection:text-primary font-sans">
        <!-- Top App Bar -->
        <header class="w-full bg-surface/90 backdrop-blur-xl border-b border-border-subtle py-3.5 px-4 md:px-8 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <!-- Brand -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-md shadow-primary/20">
                        <UtensilsCrossed class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-base font-bold tracking-tight text-text-primary leading-tight">Foodislice</span>
                        <span class="text-[10px] font-bold text-primary tracking-wider uppercase">POS Terminal</span>
                    </div>
                </div>

                <!-- Server Online Status Pill -->
                <div class="flex items-center gap-2 bg-surface-container-low px-3 py-1.5 rounded-full border border-border-subtle">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-semibold text-secondary">POS Server Online</span>
                </div>
            </div>
        </header>

        <!-- Main Auth Content -->
        <main class="w-full flex-1 flex flex-col justify-center py-8 px-4 sm:px-6">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="w-full bg-surface/80 backdrop-blur-xl border-t border-border-subtle py-4 px-4 md:px-8">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left text-xs text-text-muted">
                <p>© 2026 Foodislice POS by PT Kuliner Digital Indonesia. All rights reserved.</p>
                <div class="flex items-center gap-4 font-medium">
                    <span class="flex items-center gap-1.5">
                        <Shield class="w-3.5 h-3.5 text-tertiary" />
                        Encrypted Register
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Wifi class="w-3.5 h-3.5 text-secondary" />
                        v2.4.0-umkm
                    </span>
                </div>
            </div>
        </footer>
    </div>
</template>
