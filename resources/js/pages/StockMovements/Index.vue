<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

defineOptions({
    layout: AppLayout,
});

type StockMovement = {
    id: number;
    product_id: number | null;
    product: { id: number; sku: string; name: string } | null;
    type: 'in' | 'out';
    quantity: number;
    user_id: number | null;
    user: { id: number; name: string } | null;
    remarks: string | null;
    created_at: string;
    updated_at: string;
};

defineProps<{
    stockMovements: PaginatedData<StockMovement>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'product', label: 'Product' },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'quantity', label: 'Quantity', sortable: true },
    { key: 'user', label: 'User' },
    { key: 'remarks', label: 'Remarks' },
    { key: 'created_at', label: 'Date', sortable: true },
];

function formatDate(value: string): string {
    const d = new Date(value);
    return d.toLocaleString();
}
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Stock Movements</h1>
        </div>

        <DataTable :columns="columns" :rows="stockMovements" :filters="filters" route-prefix="/stock-movement"
            :selectable="false">
            <template #cell-product="{ row }">
                <template v-if="row.product">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">{{ row.product.sku }}</span>
                        <span class="text-xs text-gray-500">{{ row.product.name }}</span>
                    </div>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>

            <template #cell-type="{ value }">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase" :class="value === 'in'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'">
                    {{ value }}
                </span>
            </template>

            <template #cell-quantity="{ value }">
                <span class="tabular-nums">{{ value }}</span>
            </template>

            <template #cell-user="{ row }">
                <span v-if="row.user">{{ row.user.name }}</span>
                <span v-else class="text-gray-400">—</span>
            </template>

            <template #cell-remarks="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="text-gray-400">—</span>
            </template>

            <template #cell-created_at="{ value }">
                <span class="tabular-nums">{{ formatDate(value as string) }}</span>
            </template>
        </DataTable>
    </div>
</template>