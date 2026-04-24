<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { router, useForm } from '@inertiajs/vue3';
import { destroy, bulkDestroy, edit } from '@/actions/App/Http/Controllers/ProductController';
import { stockIn, stockOut } from '@/actions/App/Http/Controllers/StockMovementController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { ref } from 'vue';

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
    stock_quantity: number;
    created_at: string;
    updated_at: string;
};

defineProps<{
    products: PaginatedData<Product>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'sku', label: 'SKU', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    // { key: 'description', label: 'Description' },
    { key: 'cost_price', label: 'Cost Price', sortable: true },
    { key: 'reorder_level', label: 'Reorder Level', sortable: true },
    { key: 'stock_quantity', label: 'Stock Quantity', sortable: true },
];

function bulkDelete(ids: number[], clearSelection: () => void) {
    if (confirm(`Are you sure you want to delete ${ids.length} products?`)) {
        router.delete(bulkDestroy.url(), {
            data: { ids },
            onSuccess: () => clearSelection(),
        });
    }
}

// modal state
const showStockInModal = ref(false);
const showStockOutModal = ref(false);
const stockInProduct = ref<Product | null>(null);
const stockOutProduct = ref<Product | null>(null);

const form = useForm({
    quantity: 0,
    remarks: '',
});

function openStockInModal(product: Product) {
    stockInProduct.value = product;
    showStockInModal.value = true;
}

function submitStockIn(productId: number) {
    form.post(stockIn.url(productId));
    showStockInModal.value = false;
}

</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        </div>

        <DataTable :columns="columns" :rows="products" :filters="filters" route-prefix="/product">
            <template #cell-cost_price="{ value }">
                <span class="tabular-nums">RM {{ Number(value).toFixed(2) }}</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <button type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="openStockInModal(row)">
                        In
                    </button>
                    <button type="button"
                        class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
                        @click="stockOut(row.id)">
                        Out
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

    <!-- Stock in -->
    <Teleport to="body">
        <div v-if="showStockInModal" class="fixed inset-0 size-full flex items-center justify-center">
            <div class="z-40 fixed inset-0 size-full bg-black/40" @click="showStockInModal = false"></div>
            <div class="z-50 py-4 px-6 bg-white rounded-lg">
                <h2 class="pb-2 font-extrabold">Stock In - {{ stockInProduct?.name }}</h2>
                <form class="space-y-3" @submit.prevent="submitStockIn(stockInProduct!.id)">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Product
                        </label>
                        <input id="name" :value="stockInProduct?.name ?? ''" type="text" readonly
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-700 focus:outline-none" />
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">
                            Quantity
                        </label>
                        <input id="quantity" v-model="form.quantity" type="number"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                    </div>
                    <div>
                        <label for="remarks" class="block text-sm font-medium text-gray-700">
                            Remarks (optional)
                        </label>
                        <input id="remarks" v-model="form.remarks" type="text" placeholder="e.g. Sales order #1043"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                    </div>
                    <div class="flex gap-x-3 justify-end">
                        <button type="submit"
                            class="inline-flex items-center rounded-lg border border-indigo-500 bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Submit
                        </button>
                        <button type="button"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            @click="showStockInModal = false">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Stock out -->
    <Teleport to="body">


    </Teleport>
</template>