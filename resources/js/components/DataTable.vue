<script setup lang="ts" generic="T extends { id: number }">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

const props = withDefaults(
    defineProps<{
        columns: Column[];
        rows: PaginatedData<T>;
        filters: Filters;
        routePrefix: string;
        selectable?: boolean;
    }>(),
    {
        selectable: true,
    },
);

const emit = defineEmits<{
    (e: 'update:selected', value: number[]): void;
}>();

const search = ref(props.filters.search ?? '');
const selected = ref<number[]>([]);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
        router.get(
            props.routePrefix,
            {
                search: value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
                per_page: props.filters.per_page,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function sort(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        props.routePrefix,
        {
            search: props.filters.search,
            sort: column,
            direction,
            per_page: props.filters.per_page,
        },
        { preserveState: true, replace: true },
    );
}

function changePerPage(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    router.get(
        props.routePrefix,
        {
            search: props.filters.search,
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: value,
        },
        { preserveState: true, replace: true },
    );
}

const allSelected = computed({
    get: () =>
        props.rows.data.length > 0 &&
        props.rows.data.every((row) => selected.value.includes(row.id)),
    set: (value: boolean) => {
        selected.value = value ? props.rows.data.map((row) => row.id) : [];
        emit('update:selected', selected.value);
    },
});

function toggleRow(id: number) {
    const index = selected.value.indexOf(id);
    if (index === -1) {
        selected.value.push(id);
    } else {
        selected.value.splice(index, 1);
    }
    emit('update:selected', [...selected.value]);
}

function isSelected(id: number) {
    return selected.value.includes(id);
}

function clearSelection() {
    selected.value = [];
    emit('update:selected', []);
}

watch(
    () => props.rows.current_page,
    () => {
        clearSelection();
    },
);

defineExpose({ clearSelection });
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar: Search + Bulk Actions -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input v-model="search" type="text" placeholder="Search..."
                    class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
            </div>

            <div v-if="selected.length > 0" class="flex items-center gap-3">
                <span class="text-sm text-gray-600">
                    {{ selected.length }} selected
                </span>
                <slot name="bulk-actions" :selected="selected" :clear-selection="clearSelection" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th v-if="selectable" class="w-12 px-4 py-3">
                                <input v-model="allSelected" type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            </th>
                            <th v-for="column in columns" :key="column.key"
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                :class="{ 'cursor-pointer select-none hover:text-gray-700': column.sortable }"
                                @click="column.sortable && sort(column.key)">
                                <div class="flex items-center gap-1">
                                    {{ column.label }}
                                    <span v-if="column.sortable" class="inline-flex flex-col">
                                        <svg class="h-3 w-3" :class="filters.sort === column.key && filters.direction === 'asc'
                                                ? 'text-indigo-600'
                                                : 'text-gray-300'
                                            " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <svg class="-mt-1 h-3 w-3" :class="filters.sort === column.key && filters.direction === 'desc'
                                                ? 'text-indigo-600'
                                                : 'text-gray-300'
                                            " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 17a.75.75 0 01-.55-.24l-3.25-3.5a.75.75 0 111.1-1.02L10 15.148l2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5A.75.75 0 0110 17z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                            </th>
                            <th v-if="$slots.actions"
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="row in rows.data" :key="row.id" class="transition-colors hover:bg-gray-50"
                            :class="{ 'bg-indigo-50/50': isSelected(row.id) }">
                            <td v-if="selectable" class="w-12 px-4 py-3">
                                <input :checked="isSelected(row.id)" type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    @change="toggleRow(row.id)" />
                            </td>
                            <td v-for="column in columns" :key="column.key"
                                class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                <slot :name="`cell-${column.key}`" :row="row"
                                    :value="(row as Record<string, unknown>)[column.key]">
                                    {{ (row as Record<string, unknown>)[column.key] }}
                                </slot>
                            </td>
                            <td v-if="$slots.actions" class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <slot name="actions" :row="row" />
                            </td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td :colspan="columns.length + (selectable ? 1 : 0) + ($slots.actions ? 1 : 0)"
                                class="px-4 py-12 text-center text-sm text-gray-500">
                                No results found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer: Pagination + Per-page -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">
                <template v-if="rows.from && rows.to">
                    Showing <span class="font-medium">{{ rows.from }}</span> to
                    <span class="font-medium">{{ rows.to }}</span> of
                    <span class="font-medium">{{ rows.total }}</span> results
                </template>
                <template v-else>
                    No results
                </template>
            </p>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label for="per-page" class="text-sm text-gray-600">Per page</label>
                    <select id="per-page" :value="rows.per_page"
                        class="rounded-lg border border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm text-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                        @change="changePerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <nav v-if="rows.last_page > 1" class="flex items-center gap-1">
                    <template v-for="link in rows.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition-colors"
                            :class="link.active
                                    ? 'bg-indigo-600 font-semibold text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                                " v-html="link.label" />
                        <span v-else
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm text-gray-300"
                            v-html="link.label" />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
