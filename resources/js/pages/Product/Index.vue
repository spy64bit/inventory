<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { router } from '@inertiajs/vue3';
import { destroy, bulkDestroy, edit } from '@/actions/App/Http/Controllers/ProductController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

defineOptions({
    layout: AppLayout,
});

type Product = {
    id: number;
    sku: string;
    name: string;
    description: string | null;
    cost_price: string;
    reorder_level: number;
    created_at: string;
    updated_at: string;
};

defineProps < {
    products: PaginatedData < Product >;
    filters: Filters;
} > ();

const columns: Column[] = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'description', label: 'Description' },
    { key: 'cost_price', label: 'Cost Price', sortable: true },
    { key: 'reorder_level', label: 'Reorder Level', sortable: true },
];

function deleteProduct(product: Product) {
    if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
        router.delete(destroy.url(product.id));
    }
}

function bulkDelete(ids: number[], clearSelection: () => void) {
    if (confirm(`Are you sure you want to delete ${ids.length} products?`)) {
        router.delete(bulkDestroy.url(), {
            data: { ids },
            onSuccess: () => clearSelection(),
        });
    }
}
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        </div>

        <DataTable :columns="columns" :rows="products" :filters="filters" route-prefix="/product">
            <template #cell-cost_price="{ value }">
                <span class="tabular-nums">${{ Number(value).toFixed(2) }}</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <a :href="edit.url(row.id)"
                        class="inline-flex items-center rounded-md px-2.5 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                        Edit
                    </a>
                    <button type="button"
                        class="inline-flex items-center rounded-md px-2.5 py-1.5 text-sm font-medium text-red-600 hover:text-red-900"
                        @click="deleteProduct(row)">
                        Delete
                    </button>
                </div>
            </template>

            <template #bulk-actions="{ selected, clearSelection }">
                <button type="button"
                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
                    @click="bulkDelete(selected, clearSelection)">
                    Delete Selected
                </button>
            </template>
        </DataTable>
    </div>
</template>