<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
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
    unit_of_measure: string;
}

interface PurchaseOrderItem {
    id: number;
    product_id: number;
    quantity_ordered: string | number;
    quantity_received: string | number;
    unit_cost: string;
    product: Product;
}

interface User {
    id: number;
    name: string;
}

type PurchaseOrderStatus =
    | 'pending'
    | 'approved'
    | 'submitted'
    | 'partially_received'
    | 'received'
    | 'closed'
    | 'cancelled';

interface PurchaseOrder {
    id: number;
    status: PurchaseOrderStatus;
    notes: string | null;
    approved_at: string | null;
    submitted_at: string | null;
    received_at: string | null;
    created_at: string;
    supplier: Supplier;
    created_by: User | null;
    approved_by: User | null;
    items: PurchaseOrderItem[];
}

const props = defineProps < {
    purchaseOrder: PurchaseOrder;
} > ();

const grandTotal = computed(() =>
    props.purchaseOrder.items.reduce((total, item) => {
        const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
        return total + (Number.isFinite(subtotal) ? subtotal : 0);
    }, 0),
);

const statusBadgeClass = computed(() => {
    switch (props.purchaseOrder.status) {
        case 'pending':
            return 'badge-ghost';
        case 'approved':
            return 'badge-info';
        case 'submitted':
            return 'badge-primary';
        case 'partially_received':
            return 'badge-warning';
        case 'received':
            return 'badge-success';
        case 'closed':
            return 'badge-neutral';
        case 'cancelled':
            return 'badge-error';
        default:
            return 'badge-ghost';
    }
});

const statusLabel = computed(() =>
    props.purchaseOrder.status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' '),
);

function formatSubtotal(item: PurchaseOrderItem): string {
    const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
    return Number.isFinite(subtotal) ? subtotal.toFixed(2) : '0.00';
}

function approve(): void {
    router.patch(PurchaseOrderController.approve(props.purchaseOrder.id).url);
}

function submit(): void {
    router.patch(PurchaseOrderController.submit(props.purchaseOrder.id).url);
}

function cancel(): void {
    if (!confirm('Are you sure you want to cancel this purchase order?')) {
        return;
    }

    router.patch(PurchaseOrderController.cancel(props.purchaseOrder.id).url);
}

function close(): void {
    if (!confirm('Are you sure you want to close this purchase order?')) {
        return;
    }

    router.patch(PurchaseOrderController.close(props.purchaseOrder.id).url);
}
</script>

<template>
    <div class="grid grid-cols-[70%_30%] gap-6">
        <!-- supplier details -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">
                        Purchase Order #{{ purchaseOrder.id }}
                    </h2>
                    <span class="badge" :class="statusBadgeClass">{{ statusLabel }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-2">
                    <label class="form-control col-span-2">
                        <div class="label">
                            <span class="label-text">Supplier</span>
                        </div>
                        <input type="text" class="input input-bordered w-full" :value="purchaseOrder.supplier.name"
                            disabled />
                    </label>

                    <label class="form-control">
                        <div class="label">
                            <span class="label-text">Email</span>
                        </div>
                        <input type="email" class="input input-bordered w-full" :value="purchaseOrder.supplier.email"
                            disabled />
                    </label>

                    <label class="form-control">
                        <div class="label">
                            <span class="label-text">Phone</span>
                        </div>
                        <input type="text" class="input input-bordered w-full" :value="purchaseOrder.supplier.phone"
                            disabled />
                    </label>

                    <label class="form-control col-span-2">
                        <div class="label">
                            <span class="label-text">Address</span>
                        </div>
                        <input type="text" class="input input-bordered w-full" :value="purchaseOrder.supplier.address"
                            disabled />
                    </label>
                </div>
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
                    <textarea class="textarea textarea-bordered w-full" rows="5" :value="purchaseOrder.notes ?? ''"
                        disabled></textarea>
                </label>

                <div class="text-sm text-base-content/70 mt-4 space-y-1">
                    <div class="flex justify-between">
                        <span>Created by</span>
                        <span>{{ purchaseOrder.created_by?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Approved by</span>
                        <span>{{ purchaseOrder.approved_by?.name ?? '—' }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4">
                    <span class="text-base-content/70">Grand Total</span>
                    <span class="text-2xl font-bold tabular-nums">RM {{ grandTotal.toFixed(2) }}</span>
                </div>

                <div class="card-actions mt-auto pt-4 flex-col gap-2">
                    <button v-if="purchaseOrder.status === 'pending'" type="button" class="btn btn-info w-full"
                        @click="approve">
                        Approve
                    </button>
                    <button v-if="purchaseOrder.status === 'approved'" type="button" class="btn btn-primary w-full"
                        @click="submit">
                        Submit to Supplier
                    </button>
                    <button v-if="purchaseOrder.status === 'received'" type="button" class="btn btn-neutral w-full"
                        @click="close">
                        Close
                    </button>
                    <button v-if="['pending', 'approved', 'submitted'].includes(purchaseOrder.status)" type="button"
                        class="btn btn-error btn-outline w-full" @click="cancel">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- item table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm mt-6">
        <div class="card-body">
            <h2 class="card-title">Purchase Order Items</h2>

            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Product</th>
                            <th class="w-32">Quantity Ordered</th>
                            <th class="w-32">Quantity Received</th>
                            <th class="w-40">Unit Cost (RM)</th>
                            <th class="w-32 text-right">Subtotal (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="purchaseOrder.items.length === 0">
                            <td colspan="6" class="text-center text-base-content/60 py-6">
                                No items.
                            </td>
                        </tr>
                        <tr v-for="(item, index) in purchaseOrder.items" :key="item.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <div class="font-medium">{{ item.product.name }}</div>
                                <div class="text-xs text-base-content/60">{{ item.product.sku }}</div>
                            </td>
                            <td class="tabular-nums">{{ item.quantity_ordered }}</td>
                            <td class="tabular-nums">{{ item.quantity_received }}</td>
                            <td class="tabular-nums">{{ Number(item.unit_cost).toFixed(2) }}</td>
                            <td class="text-right tabular-nums">{{ formatSubtotal(item) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>