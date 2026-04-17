<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const sidebarOpen = ref(false);
const page = usePage();

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1' },
];

function isActive(href) {
    return page.url.startsWith(href);
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
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- User / Logout -->
            <div class="border-t border-gray-200 px-3 py-3">
                <Link href="/logout" method="post" as="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3h-9m9 0l-3-3m3 3l-3 3" />
                    </svg>
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
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </header>

            <!-- Page content -->
            <main class="p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>