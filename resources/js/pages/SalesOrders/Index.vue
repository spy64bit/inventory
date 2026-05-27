<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { create, edit } from '@/actions/App/Http/Controllers/SalesOrderController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';

defineOptions({
    layout: AppLayout,
});

type RelatedUser = {
    id: number;
    name: string;
} | null;

type SalesOrder = {
    id: number;
    status: string;
    notes: string | null;
    confirmed_at: string | null;
    fulfilled_at: string | null;
    created_at: string;
    updated_at: string;
    customer: { id: number; name: string } | null;
    created_by: RelatedUser;
};

defineProps<{
    salesOrders: PaginatedData<SalesOrder>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'id', label: 'SO #', sortable: true },
    { key: 'customer', label: 'Customer' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'created_by', label: 'Created By' },
    { key: 'created_at', label: 'Created At', sortable: true },
];

const statusBadgeClass: Record<string, string> = {
    draft: 'badge-ghost',
    confirmed: 'badge-info',
    fulfilled: 'badge-success',
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

    <Head title="Sales Orders" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Sales Orders</h1>
            <Link :href="create.url()" class="btn btn-primary">
                New Sales Order
            </Link>
        </div>

        <DataTable :columns="columns" :rows="salesOrders" :filters="filters" route-prefix="/sales-orders"
            :selectable="false">
            <template #cell-id="{ row }">
                <Link :href="edit.url(row.id)" class="font-mono">#{{ row.id }}</Link>
            </template>

            <template #cell-customer="{ row }">
                {{ row.customer?.name ?? '—' }}
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