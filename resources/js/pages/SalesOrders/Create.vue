<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import SalesOrderController from '@/actions/App/Http/Controllers/SalesOrderController';

defineOptions({
    layout: AppLayout,
});

interface Customer {
    id: number;
    name: string;
    email: string | null;
    contact_no: string | null;
    address: string | null;
}

interface Product {
    id: number;
    sku: string;
    name: string;
    selling_price: string;
    current_stock: number;
    unit_of_measure: string;
}

interface SalesOrderItemForm {
    product_id: number;
    quantity: number;
    unit_price: string;
}

interface SalesOrderForm {
    customer_id: number | null;
    notes: string;
    items: SalesOrderItemForm[];
}

const props = defineProps<{
    customers: Customer[];
    products: Product[];
}>();

const form = useForm<SalesOrderForm>({
    customer_id: null,
    notes: '',
    items: [],
});

const selectedCustomer = computed(() =>
    props.customers.find((c) => c.id === form.customer_id) ?? null,
);

const grandTotal = computed(() =>
    form.items.reduce((total, item) => {
        const subtotal = Number(item.unit_price) * Number(item.quantity);
        return total + (Number.isFinite(subtotal) ? subtotal : 0);
    }, 0),
);

function availableProducts(currentIndex: number) {
    const selectedIds = form.items
        .filter((_, i) => i !== currentIndex)
        .map((item) => item.product_id);
    return props.products.filter((p) => !selectedIds.includes(p.id));
}

function addItem(): void {
    form.items.push({
        product_id: 0,
        quantity: 1,
        unit_price: '0.00',
    });
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function onProductSelected(item: SalesOrderItemForm): void {
    const product = props.products.find((p) => p.id === item.product_id);

    if (product) {
        item.unit_price = product.selling_price;
    }
}

function formatSubtotal(item: SalesOrderItemForm): string {
    const subtotal = Number(item.unit_price) * Number(item.quantity);
    return Number.isFinite(subtotal) ? subtotal.toFixed(2) : '0.00';
}
</script>

<template>

    <Head title="Create Sales Order" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Create Sales Order</h1>
            <Link href="/sales-orders" class="btn btn-ghost btn-sm">
                <Icon icon="heroicons:arrow-left" class="h-4 w-4" />
                Back
            </Link>
        </div>

        <div class="grid grid-cols-[70%_30%] gap-6">
            <!-- customer details -->
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Customer Details</h2>
                    <form class="grid grid-cols-2 gap-4">
                        <label class="form-control col-span-2">
                            <div class="label">
                                <span class="label-text">Customer</span>
                            </div>
                            <select v-model="form.customer_id" class="select select-bordered w-full"
                                :class="{ 'select-error': form.errors.customer_id }">
                                <option :value="null" disabled>Select a customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.customer_id" class="label">
                                <span class="label-text-alt text-error">{{ form.errors.customer_id }}</span>
                            </div>
                        </label>

                        <label class="form-control">
                            <div class="label">
                                <span class="label-text">Email</span>
                            </div>
                            <input type="email" class="input input-bordered w-full"
                                :value="selectedCustomer?.email ?? ''" disabled />
                        </label>

                        <label class="form-control">
                            <div class="label">
                                <span class="label-text">Contact No</span>
                            </div>
                            <input type="text" class="input input-bordered w-full"
                                :value="selectedCustomer?.contact_no ?? ''" disabled />
                        </label>

                        <label class="form-control col-span-2">
                            <div class="label">
                                <span class="label-text">Address</span>
                            </div>
                            <input type="text" class="input input-bordered w-full"
                                :value="selectedCustomer?.address ?? ''" disabled />
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
                            placeholder="Add any notes for this sales order..."></textarea>
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
                            @click="form.post(SalesOrderController.store().url)"
                            :disabled="!form.customer_id || form.items.length === 0 || form.processing">
                            Submit Sales Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- item table -->
        <div class="card bg-base-100 border border-base-300 shadow-sm mt-6">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Sales Order Items</h2>
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
                                <th class="w-40">Unit Price</th>
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
                                    <input v-model.number="item.quantity" type="number" min="1" step="0.01"
                                        class="input input-bordered input-sm w-full"
                                        :class="{ 'input-error': form.errors[`items.${index}.quantity`] }" />
                                    <p v-if="form.errors[`items.${index}.quantity`]" class="text-error text-xs mt-1">
                                        {{ form.errors[`items.${index}.quantity`] }}
                                    </p>
                                </td>
                                <td>
                                    <input v-model="item.unit_price" type="number" step="0.01" min="0"
                                        class="input input-bordered input-sm w-full"
                                        :class="{ 'input-error': form.errors[`items.${index}.unit_price`] }" />
                                    <p v-if="form.errors[`items.${index}.unit_price`]" class="text-error text-xs mt-1">
                                        {{ form.errors[`items.${index}.unit_price`] }}
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
    </div>
</template>
