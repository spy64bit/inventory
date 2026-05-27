<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { create, edit } from '@/actions/App/Http/Controllers/PurchaseOrderController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

defineOptions({
    layout: AppLayout,
});

type RelatedUser = {
    id: number;
    name: string;
} | null;

type PurchaseOrder = {
    id: number;
    status: string;
    notes: string | null;
    dispatched_at: string | null;
    received_at: string | null;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    supplier: { id: number; name: string } | null;
    created_by: RelatedUser;
    approved_by: RelatedUser;
};

defineProps<{
    purchaseOrders: PaginatedData<PurchaseOrder>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'id', label: 'PO #', sortable: true },
    { key: 'supplier', label: 'Supplier' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'created_by', label: 'Created By' },
    { key: 'created_at', label: 'Created At', sortable: true },
];

const statusBadgeClass: Record<string, string> = {
    draft: 'badge-ghost',
    approved: 'badge-info',
    dispatched: 'badge-primary',
    partially_received: 'badge-warning',
    received: 'badge-success',
    closed: 'badge-neutral',
    cancelled: 'badge-error',
};

function formatStatus(status: string): string {
    return status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value.replace(' ', 'T')).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>

    <Head title="Purchase Orders" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Purchase Orders</h1>
            <Link :href="create.url()" class="btn btn-primary">
                New Purchase Order
            </Link>
        </div>

        <DataTable :columns="columns" :rows="purchaseOrders" :filters="filters" route-prefix="/purchase-orders"
            :selectable="false">
            <template #cell-id="{ row }">
                <Link :href="edit.url(row.id)" class="font-mono">#{{ row.id }}</Link>
            </template>

            <template #cell-supplier="{ row }">
                {{ row.supplier?.name ?? '—' }}
            </template>

            <template #cell-status="{ row }">
                <span class="badge" :class="statusBadgeClass[row.status] ?? 'badge-ghost'">
                    {{ formatStatus(row.status) }}
                </span>
            </template>

            <template #cell-created_by="{ row }">
                {{ row.created_by?.name ?? '—' }}
            </template>

            <template #cell-created_at="{ value }">
                {{ formatDate(value as string) }}
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <Link :href="edit.url(row.id)" class="btn btn-sm btn-square btn-ghost" aria-label="Edit"
                        title="Edit">
                        <Icon icon="heroicons:pencil" class="h-4 w-4" />
                    </Link>
                </div>
            </template>
        </DataTable>
    </div>
</template>