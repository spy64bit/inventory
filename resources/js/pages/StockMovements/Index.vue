<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Head } from '@inertiajs/vue3';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

defineOptions({
    layout: AppLayout,
});

type StockMovement = {
    id: number;
    product_id: number | null;
    product: { id: number; sku: string; name: string } | null;
    type: 'in' | 'out';
    reference_type: 'purchase_order' | 'sales_order' | 'adjustment' | null;
    quantity: number;
    user_id: number | null;
    user: { id: number; name: string } | null;
    remarks: string | null;
    created_at: string;
    updated_at: string;
};

const referenceBadgeClass: Record<string, string> = {
    purchase_order: 'badge-info badge-soft',
    sales_order: 'badge-warning badge-soft',
    adjustment: 'badge-accent badge-soft',
};

defineProps<{
    stockMovements: PaginatedData<StockMovement>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'product', label: 'Product' },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'reference_type', label: 'Reference' },
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

    <Head title="Stock Movements" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Stock Movements</h1>
        </div>

        <DataTable :columns="columns" :rows="stockMovements" :filters="filters" route-prefix="/stock-movement"
            :selectable="false">
            <template #cell-product="{ row }">
                <template v-if="row.product">
                    <div class="flex flex-col">
                        <span class="font-medium">{{ row.product.sku }}</span>
                        <span class="text-xs opacity-60">{{ row.product.name }}</span>
                    </div>
                </template>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-type="{ value }">
                <span class="badge badge-sm uppercase"
                    :class="value === 'in' ? 'badge-success badge-soft' : 'badge-error badge-soft'">
                    {{ value }}
                </span>
            </template>

            <template #cell-reference_type="{ row }">
                <span v-if="row.reference_type" class="badge badge-sm capitalize"
                    :class="referenceBadgeClass[row.reference_type]">
                    {{ row.reference_type.replace('_', ' ') }}
                </span>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-quantity="{ value }">
                <span class="tabular-nums">{{ value }}</span>
            </template>

            <template #cell-user="{ row }">
                <span v-if="row.user">{{ row.user.name }}</span>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-remarks="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-created_at="{ value }">
                <span class="tabular-nums">{{ formatDate(value as string) }}</span>
            </template>
        </DataTable>
    </div>
</template>