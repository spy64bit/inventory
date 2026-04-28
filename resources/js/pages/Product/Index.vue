<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import AddProductModal from '@/components/product/AddProductModal.vue';
import { router, useForm } from '@inertiajs/vue3';
import { destroy, bulkDestroy, edit } from '@/actions/App/Http/Controllers/ProductController';
import { stockIn, stockOut } from '@/actions/App/Http/Controllers/StockMovementController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { toast } from '@/lib/toast';
import { ref, onMounted } from 'vue';

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

const props = defineProps<{
    products: PaginatedData<Product>;
    filters: Filters;
    errors: Object;
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
const showNewProductModal = ref(false);
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

function openStockOutModal(product: Product) {
    stockOutProduct.value = product;
    showStockOutModal.value = true;
}

function submitStockOut(productId: number) {
    form.post(stockOut.url(productId), {
        onFlash: (message) => {
            if (message.error) {
                toast.error(String(message.error));
            }
        },
    });
    showStockOutModal.value = false;
}

function openNewProductModal() {
    showNewProductModal.value = true;
}

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Products</h1>
            <button type="button" class="btn btn-primary" @click="openNewProductModal">
                Create Product
            </button>
        </div>

        <DataTable :columns="columns" :rows="products" :filters="filters" route-prefix="/product">
            <template #cell-cost_price="{ value }">
                <span class="tabular-nums">RM {{ Number(value).toFixed(2) }}</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-sm btn-success btn-soft" @click="openStockInModal(row)">
                        In
                    </button>
                    <button type="button" class="btn btn-sm btn-error btn-soft" @click="openStockOutModal(row)">
                        Out
                    </button>
                </div>
            </template>

            <template #bulk-actions="{ selected, clearSelection }">
                <button type="button" class="btn btn-sm btn-error btn-soft"
                    @click="bulkDelete(selected, clearSelection)">
                    Delete Selected
                </button>
            </template>
        </DataTable>
    </div>



    <template v-if="isMounted">
        <AddProductModal v-model:open="showNewProductModal" />


        <!-- Stock in -->
        <Teleport to="body">
            <div class="modal" :class="{ 'modal-open': showStockInModal }" role="dialog" aria-modal="true">
                <div class="modal-box max-w-md">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="badge badge-success badge-soft">Stock In</span>
                        <h2 class="text-base font-bold">{{ stockInProduct?.name }}</h2>
                    </div>
                    <form class="space-y-3" @submit.prevent="submitStockIn(stockInProduct!.id)">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Product</legend>
                            <input :value="stockInProduct?.name ?? ''" type="text" readonly
                                class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Quantity</legend>
                            <input v-model="form.quantity" type="number" min="1" class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Remarks <span class="opacity-60">(optional)</span></legend>
                            <input v-model="form.remarks" type="text" placeholder="e.g. Sales order #1043"
                                class="input input-bordered w-full" />
                        </fieldset>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost"
                                @click="showStockInModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
                <div class="modal-backdrop" @click="showStockInModal = false"></div>
            </div>
        </Teleport>

        <!-- Stock out -->
        <Teleport to="body">
            <div class="modal" :class="{ 'modal-open': showStockOutModal }" role="dialog" aria-modal="true">
                <div class="modal-box max-w-md">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="badge badge-error badge-soft">Stock Out</span>
                        <h2 class="text-base font-bold">{{ stockOutProduct?.name }}</h2>
                    </div>
                    <form class="space-y-3" @submit.prevent="submitStockOut(stockOutProduct!.id)">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Product</legend>
                            <input :value="stockOutProduct?.name ?? ''" type="text" readonly
                                class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Quantity</legend>
                            <input v-model="form.quantity" type="number" min="1" class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Remarks <span class="opacity-60">(optional)</span></legend>
                            <input v-model="form.remarks" type="text" placeholder="e.g. Sales order #1043"
                                class="input input-bordered w-full" />
                        </fieldset>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost"
                                @click="showStockOutModal = false">Cancel</button>
                            <button type="submit" class="btn btn-error">Submit</button>
                        </div>
                    </form>
                </div>
                <div class="modal-backdrop" @click="showStockOutModal = false"></div>
            </div>
        </Teleport>
    </template>

</template>
