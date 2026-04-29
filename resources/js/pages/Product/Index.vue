<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import AddProductModal from '@/components/product/AddProductModal.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { destroy, bulkDestroy, edit } from '@/actions/App/Http/Controllers/ProductController';
import { stockIn, stockOut } from '@/actions/App/Http/Controllers/StockMovementController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { toast } from '@/lib/toast';
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';

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

interface SelectedProduct {
    ids: number[];
    clearSelection: () => void;
}

const selectedProducts = ref<SelectedProduct | null>(null);

function bulkDelete(ids: number[], clearSelection: () => void) {
    if (confirm(`Are you sure you want to delete ${ids.length} products?`)) {
        router.delete(bulkDestroy.url(), {
            data: { ids },
            onSuccess: () => clearSelection(),
        });
    }
}

function openBulkDelete(ids: number[], clearSelection: () => void) {
    const title = 'Delete Products';
    const description = `Are you sure you want to delete these ${ids.length} products? This action cannot be undone.`;

    dialogState.value = {
        type: 'bulkDelete',
        title,
        description,
    };

    selectedProducts.value = {
        ids,
        clearSelection,
    };
}

function confirmDelete() {
    if (dialogState.value.type === 'delete' && stockProduct.value) {
        router.delete(destroy.url(stockProduct.value));
    }
    else if (dialogState.value.type === 'bulkDelete' && selectedProducts.value) {
        router.delete(bulkDestroy.url(), {
            data: { ids: selectedProducts.value.ids },
            onSuccess: () => selectedProducts.value?.clearSelection(),
        });
    }

    dialogState.value.type = null;
}

// modal state
type DialogType = 'stockIn' | 'stockOut' | 'delete' | 'bulkDelete';

type DialogState = {
    type: DialogType | null;
    title: string | null;
    description: string | null;
}

const stockProduct = ref<Product | null>(null);
const dialogState = ref<DialogState>({
    type: null,
    title: null,
    description: null,
});

const showNewProductModal = ref(false);

const form = useForm({
    quantity: 0,
    remarks: '',
});

function openDialog(type: DialogType, product: Product | null = null) {

    let title = null;
    let description = null;

    switch (type) {
        case 'stockIn':
            title = 'Stock In';
            description = `Update stock for ${product?.name}`;
            break;
        case 'stockOut':
            title = 'Stock Out';
            description = `Update stock for ${product?.name}`;
            break;
        case 'delete':
            title = 'Delete Product';
            description = `Are you sure you want to delete ${product?.name}? This action cannot be undone.`;
            break;
        case 'bulkDelete':
            title = 'Delete Products';
            description = `Are you sure you want to delete these products? This action cannot be undone.`;
            break;
    }


    dialogState.value = {
        type,
        title,
        description,
    };

    stockProduct.value = product;
}

function submitStock(productId: number) {

    let url = null;
    if (dialogState.value.type === 'stockIn') {
        url = stockIn.url(productId);
    } else {
        url = stockOut.url(productId);
    }

    form.post(url, {
        onFlash: (message) => {
            if (message.error) {
                toast.error(String(message.error));
            }
            else {
                toast.success(String(message.success));
            }
        },
    });


    form.reset();
    stockProduct.value = null;
    dialogState.value.type = null;
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
                    <button type="button" class="btn btn-sm btn-success btn-soft" @click="openDialog('stockIn', row)">
                        In
                    </button>
                    <button type="button" class="btn btn-sm btn-error btn-soft" @click="openDialog('stockOut', row)">
                        Out
                    </button>
                    <Link :href="edit.url(row.id)" class="btn btn-sm btn-square btn-warning btn-soft" aria-label="Edit"
                        title="Edit">
                        <Icon icon="heroicons:pencil-square" class="h-4 w-4" />
                    </Link>
                    <button type="button" class="btn btn-sm btn-square btn-error btn-soft" aria-label="Delete"
                        title="Delete" @click="openDialog('delete', row)">
                        <Icon icon="heroicons:trash" class="h-4 w-4" />
                    </button>
                </div>
            </template>

            <template #bulk-actions="{ selected, clearSelection }">
                <button type="button" class="btn btn-sm btn-error btn-soft"
                    @click="openBulkDelete(selected, clearSelection)">
                    Delete Selected
                </button>
            </template>
        </DataTable>
    </div>



    <template v-if="isMounted">
        <AddProductModal v-model:open="showNewProductModal" />


        <Teleport to="body">
            <!-- delete/ bulkdelete confirmation modal -->
            <div class="modal" :class="{ 'modal-open': ['delete', 'bulkDelete'].includes(dialogState.type ?? '') }"
                role="dialog" aria-modal="true">
                <div class="modal-box max-w-md">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="badge badge-soft badge-error">
                            <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4" />
                            Delete
                        </span>
                        <h2 class="text-base font-bold">{{ dialogState.title }}</h2>
                    </div>
                    <p class="text-sm opacity-80">
                        {{ dialogState.description }}
                    </p>
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="dialogState.type = null">Cancel</button>
                        <button type="button" class="btn btn-error" @click="confirmDelete">Delete</button>
                    </div>
                </div>
                <div class="modal-backdrop" @click="dialogState.type = null"></div>
            </div>

            <!-- Stock in/ Stock out  -->
            <div class="modal" :class="{ 'modal-open': ['stockIn', 'stockOut'].includes(dialogState.type ?? '') }"
                role="dialog" aria-modal="true">
                <div class="modal-box max-w-md">
                    <div class="mb-4 flex items-center gap-2">
                        <span
                            :class="['badge', 'badge-soft', dialogState.type === 'stockIn' ? 'badge-success' : 'badge-error']">{{
                                dialogState.title }}</span>
                        <h2 class="text-base font-bold">{{ stockProduct?.name }}</h2>
                    </div>
                    <form class="space-y-3" @submit.prevent="submitStock(stockProduct!.id)">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Product</legend>
                            <input id="name" :value="stockProduct?.name ?? ''" type="text" readonly
                                class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Quantity</legend>
                            <input id="quantity" v-model="form.quantity" type="number" min="1"
                                class="input input-bordered w-full" />
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Remarks <span class="opacity-60">(optional)</span></legend>
                            <input id="remarks" v-model="form.remarks" type="text" placeholder="e.g. Sales order #1043"
                                class="input input-bordered w-full" />
                        </fieldset>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost" @click="dialogState.type = null">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
                <div class="modal-backdrop" @click="dialogState.type = null"></div>
            </div>
        </Teleport>
    </template>

</template>
