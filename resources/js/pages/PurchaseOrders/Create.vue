<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';

defineOptions({
    layout: AppLayout,
});

interface Supplier {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
}

interface Product {
    id: number;
    sku: string;
    name: string;
    cost_price: string;
    unit_of_measure: string;
}

interface PurchaseOrder {
    supplier_id: number | null;
    notes: string;
    items: PurchaseOrderItem[];
}

interface PurchaseOrderItem {
    product_id: number;
    quantity_ordered: number;
    unit_cost: string;
}


const props = defineProps<{
    suppliers: Supplier[];
    products: Product[];
}>();

const form = useForm<PurchaseOrder>({
    supplier_id: null,
    notes: '',
    items: [],
});

const selectedSupplier = computed(() =>
    props.suppliers.find((supplier) => supplier.id === form.supplier_id) ?? null,
);

const grandTotal = computed(() =>
    form.items.reduce((total, item) => {
        const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
        return total + (Number.isFinite(subtotal) ? subtotal : 0);
    }, 0),
);

function availableProducts(currentIndex: number) {
    const selectedIds = form.items
        .filter((_, i) => i !== currentIndex)
        .map(item => item.product_id)
    return props.products.filter(p => !selectedIds.includes(p.id))
}

function addItem(): void {
    form.items.push({
        product_id: 0,
        quantity_ordered: 1,
        unit_cost: '0.00',
    });
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function onProductSelected(item: PurchaseOrderItem): void {
    const product = props.products.find((p) => p.id === item.product_id);

    if (product) {
        item.unit_cost = product.cost_price;
    }
}

function formatSubtotal(item: PurchaseOrderItem): string {
    const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
    return Number.isFinite(subtotal) ? subtotal.toFixed(2) : '0.00';
}

</script>

<template>

    <div class="grid grid-cols-[70%_30%] gap-6">
        <!-- supplier details -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h2 class="card-title">Supplier Details</h2>
                <form class="grid grid-cols-2 gap-4">
                    <label class="form-control col-span-2">
                        <div class="label">
                            <span class="label-text">Supplier</span>
                        </div>
                        <select v-model="form.supplier_id" class="select select-bordered w-full"
                            :class="{ 'select-error': form.errors.supplier_id }">
                            <option :value="null" disabled>Select a supplier</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.supplier_id" class="label">
                            <span class="label-text-alt text-error">{{ form.errors.supplier_id }}</span>
                        </div>
                    </label>

                    <label class="form-control">
                        <div class="label">
                            <span class="label-text">Email</span>
                        </div>
                        <input type="email" class="input input-bordered w-full" :value="selectedSupplier?.email ?? ''"
                            disabled />
                    </label>

                    <label class="form-control">
                        <div class="label">
                            <span class="label-text">Phone</span>
                        </div>
                        <input type="text" class="input input-bordered w-full" :value="selectedSupplier?.phone ?? ''"
                            disabled />
                    </label>

                    <label class="form-control col-span-2">
                        <div class="label">
                            <span class="label-text">Address</span>
                        </div>
                        <input type="text" class="input input-bordered w-full" :value="selectedSupplier?.address ?? ''"
                            disabled />
                    </label>
                </form>
            </div>
        </div>
        <!-- summary -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body flex flex-col">
                <h2 class="card-title">Summary</h2>

                <label class="form-control">
                    <div class="label">
                        <span class="label-text">Notes</span>
                    </div>
                    <textarea v-model="form.notes" class="textarea textarea-bordered w-full" rows="5"
                        :class="{ 'textarea-error': form.errors.notes }"
                        placeholder="Add any notes for this purchase order..."></textarea>
                    <div v-if="form.errors.notes" class="label">
                        <span class="label-text-alt text-error">{{ form.errors.notes }}</span>
                    </div>
                </label>

                <div class="flex items-center justify-between mt-4">
                    <span class="text-base-content/70">Grand Total</span>
                    <span class="text-2xl font-bold tabular-nums">RM {{ grandTotal.toFixed(2) }}</span>
                </div>
                <div class="card-actions mt-auto pt-4">
                    <button type="button" class="btn btn-primary w-full"
                        @click="form.post(PurchaseOrderController.store().url)"
                        :disabled="!form.supplier_id || form.items.length === 0 || form.processing">
                        Submit Purchase Order
                    </button>
                </div>
            </div>
        </div>

    </div>
    <!-- item table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm mt-6">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Purchase Order Items</h2>
                <button type="button" class="btn btn-primary btn-sm" @click="addItem">
                    Add Item
                </button>
            </div>

            <div v-if="form.errors.items" role="alert" class="alert alert-error">
                <Icon icon="heroicons:exclamation-circle" class="h-5 w-5" />
                <span>{{ form.errors.items }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Product</th>
                            <th class="w-32">Quantity</th>
                            <th class="w-40">Cost Price</th>
                            <th class="w-32 text-right">Subtotal (RM)</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="form.items.length === 0">
                            <td colspan="6" class="text-center text-base-content/60 py-6">
                                No items added yet.
                            </td>
                        </tr>
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <select v-model="item.product_id" class="select select-bordered select-sm w-full"
                                    :class="{ 'select-error': form.errors[`items.${index}.product_id`] }"
                                    @change="onProductSelected(item)">
                                    <option :value="0" disabled>Select a product</option>
                                    <option v-for="product in availableProducts(index)" :key="product.id"
                                        :value="product.id">
                                        {{ product.sku }} — {{ product.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors[`items.${index}.product_id`]" class="text-error text-xs mt-1">
                                    {{ form.errors[`items.${index}.product_id`] }}
                                </p>
                            </td>
                            <td>
                                <input v-model.number="item.quantity_ordered" type="number" min="1"
                                    class="input input-bordered input-sm w-full"
                                    :class="{ 'input-error': form.errors[`items.${index}.quantity_ordered`] }" />
                                <p v-if="form.errors[`items.${index}.quantity_ordered`]"
                                    class="text-error text-xs mt-1">
                                    {{ form.errors[`items.${index}.quantity_ordered`] }}
                                </p>
                            </td>
                            <td>
                                <input v-model="item.unit_cost" type="number" step="0.01" min="0"
                                    class="input input-bordered input-sm w-full"
                                    :class="{ 'input-error': form.errors[`items.${index}.unit_cost`] }" />
                                <p v-if="form.errors[`items.${index}.unit_cost`]" class="text-error text-xs mt-1">
                                    {{ form.errors[`items.${index}.unit_cost`] }}
                                </p>
                            </td>
                            <td class="text-right tabular-nums">
                                {{ formatSubtotal(item) }}
                            </td>
                            <td>
                                <button type="button" class="btn btn-ghost btn-sm text-error"
                                    @click="removeItem(index)">
                                    <Icon icon="heroicons:x-mark" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</template>