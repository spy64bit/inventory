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
    <div class="bg-base-200 min-h-screen">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="bg-base-100 border-base-300 fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r transition-transform duration-200 ease-in-out lg:translate-x-0">
            <!-- Logo -->
            <div class="border-base-300 flex h-14 items-center gap-2 border-b px-5">
                <span class="text-lg font-semibold">Inventory</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto p-2">
                <ul class="menu menu-md w-full gap-1">
                    <li v-for="item in navigation" :key="item.name">
                        <Link :href="item.href" :class="{ 'menu-active': isActive(item.href) }"
                            @click="sidebarOpen = false">
                            <Icon :icon="item.icon" width="20" height="20" />
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- User / Logout -->
            <div class="border-base-300 border-t p-2">
                <ul class="menu menu-md w-full">
                    <li>
                        <Link href="/logout" method="post" as="button">
                            <Icon icon="mdi:logout" width="20" height="20" />
                            Logout
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header
                class="navbar bg-base-100/80 border-base-300 sticky top-0 z-20 min-h-14 border-b backdrop-blur px-4 lg:px-6">
                <!-- Mobile menu button -->
                <button class="btn btn-ghost btn-square btn-sm lg:hidden" @click="sidebarOpen = !sidebarOpen">
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