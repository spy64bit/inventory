<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Toaster from '@/components/Toaster.vue';

const sidebarOpen = ref(false);
const page = usePage();

const navigation = [
    { name: 'Dashboard', href: '/', icon: "ic:twotone-space-dashboard" },
    { name: 'Products', href: '/product', icon: "mdi:package-variant" },
    { name: 'Categories', href: '/category', icon: "mdi:shape" },
    { name: 'Suppliers', href: '/supplier', icon: "mdi:truck" },
    { name: 'Stock Movements', href: '/stock-movement', icon: "mdi:swap-horizontal" },
];

function isActive(href) {
    return page.url === href;
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white border-r border-gray-200 transition-transform duration-200 ease-in-out lg:translate-x-0">
            <!-- Logo -->
            <div class="flex h-14 items-center gap-2 border-b border-gray-200 px-5">
                <span class="text-lg font-semibold text-gray-900">Inventory</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <ul class="space-y-1">
                    <li v-for="item in navigation" :key="item.name">
                        <Link :href="item.href" :class="[
                            isActive(item.href)
                                ? 'bg-gray-100 text-gray-900'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        ]">
                            <Icon :icon="item.icon" width="24" height="24" />
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- User / Logout -->
            <div class="border-t border-gray-200 px-3 py-3">
                <Link href="/logout" method="post" as="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                    <Icon icon="mdi:logout" width="24" height="24" />
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-gray-200 bg-white/80 backdrop-blur px-4 lg:px-6">
                <!-- Mobile menu button -->
                <button class="lg:hidden -ml-1 rounded-md p-1.5 text-gray-500 hover:text-gray-900 transition-colors"
                    @click="sidebarOpen = !sidebarOpen">
                    <Icon icon="mdi:menu" width="24" height="24" />
                </button>
            </header>

            <!-- Page content -->
            <main class="p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>