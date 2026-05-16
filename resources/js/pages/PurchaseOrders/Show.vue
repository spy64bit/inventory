<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { toast } from '@/lib/toast';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';
import supplier from '@/routes/supplier';
import purchaseOrders from '@/routes/purchase-orders';

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

interface PurchaseOrderItem {
    id: number;
    product_id: number;
    quantity_ordered: number;
    quantity_received: number;
    unit_cost: string;
    product: Product;
}

interface User {
    id: number;
    name: string;
}

type PurchaseOrderStatus =
    | 'draft'
    | 'approved'
    | 'dispatched'
    | 'partially_received'
    | 'received'
    | 'closed'
    | 'cancelled';

interface PurchaseOrder {
    id: number;
    status: PurchaseOrderStatus;
    notes: string | null;
    approved_at: string | null;
    dispatched_at: string | null;
    received_at: string | null;
    created_at: string;
    supplier: Supplier;
    created_by: User | null;
    approved_by: User | null;
    items: PurchaseOrderItem[];
}

const props = defineProps<{
    purchaseOrder: PurchaseOrder;
    suppliers: Supplier[];
    products: Product[];
    can: {
        approve: boolean;
        cancel: boolean;
        dispatch: boolean;
        receive: boolean;
        close: boolean;
        update: boolean;
    };
}>();

const grandTotal = computed(() => {
    const items = ['draft', 'approved'].includes(props.purchaseOrder.status)
        ? editForm.items
        : props.purchaseOrder.items;

    return items.reduce((total, item) => {
        const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
        return total + (Number.isFinite(subtotal) ? subtotal : 0);
    }, 0);
});

const PO_STATUS_BADGE = {
    draft: 'badge-ghost',
    approved: 'badge-info',
    dispatched: 'badge-primary',
    partially_received: 'badge-warning',
    received: 'badge-success',
    closed: 'badge-neutral',
    cancelled: 'badge-error',
}

const statusBadgeClass = computed(() => {
    return PO_STATUS_BADGE[props.purchaseOrder.status] || 'badge-ghost';
});

const statusLabel = computed(() =>
    props.purchaseOrder.status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' '),
);

function formatSubtotal(item: PurchaseOrderItem | PurchaseOrderItemForm): string {
    const subtotal = Number(item.unit_cost) * Number(item.quantity_ordered);
    return Number.isFinite(subtotal) ? subtotal.toFixed(2) : '0.00';
}

function patchPoRequest(
    url: string,
    successMessage: string,
    failureMessage: string,
    onFinish?: () => void
): void {
    router.patch(url, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success(successMessage),
        onError: (errors) => {
            const message = errors.status || Object.values(errors)[0] || failureMessage;
            toast.error(message);
        },
        onFinish
    });
}


function approve(): void {
    patchPoRequest(
        PurchaseOrderController.approve(props.purchaseOrder.id).url,
        'Purchase order approved successfully.',
        'Failed to approve purchase order.'
    );
}

function dispatch(): void {
    patchPoRequest(
        PurchaseOrderController.dispatch(props.purchaseOrder.id).url,
        'Purchase order dispatched to supplier.',
        'Failed to dispatch purchase order.'
    );
}

function received(): void {

    if (!form.items.some((item) => item.quantity_received > 0)) {
        return;
    }

    form.post(PurchaseOrderController.receive(props.purchaseOrder.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Stock received and updated successfully.');
            form.reset();
        },
        onError: (errors) => {
            const message =
                errors.status ||
                errors.items ||
                Object.values(errors)[0] ||
                'Failed to receive stock.';
            toast.error(message);
        },
    });
}

function cancel(): void {
    showCancelModal.value = true;
}

function confirmCancel(): void {
    patchPoRequest(
        PurchaseOrderController.cancel(props.purchaseOrder.id).url,
        'Purchase order cancelled successfully.',
        'Failed to cancel purchase order.',
        () => {
            showCancelModal.value = false;
        }
    );


}

function close(): void {
    showCloseModal.value = true;
}

function confirmClose(): void {
    patchPoRequest(
        PurchaseOrderController.close(props.purchaseOrder.id).url,
        'Purchase order closed successfully.',
        'Failed to close purchase order.',
        () => {
            showCloseModal.value = false;
        }
    )
}

const showCancelModal = ref(false);
const showCloseModal = ref(false);

const form = useForm({
    items: props.purchaseOrder.items.map((item) => ({
        product_id: item.product_id,
        quantity_received: 0,
    })),
});

interface PurchaseOrderForm {
    supplier_id: number | null;
    notes: string;
    items: PurchaseOrderItemForm[];
}

interface PurchaseOrderItemForm {
    id?: number;
    product_id: number;
    quantity_ordered: number;
    quantity_received?: number;
    unit_cost: string;
}

const editForm = useForm<PurchaseOrderForm>({
    supplier_id: props.purchaseOrder.supplier.id,
    notes: props.purchaseOrder.notes ?? '',
    items: props.purchaseOrder.items.map((item) => ({
        id: item.id,
        product_id: item.product_id,
        quantity_ordered: item.quantity_ordered ?? 1,
        unit_cost: item.unit_cost,
    })),
});

const selectedSupplier = computed(() =>
    props.suppliers.find((supplier) => supplier.id === editForm.supplier_id) ?? null,
);

function availableProducts(currentIndex: number) {
    const selectedIds = editForm.items
        .filter((_, i) => i !== currentIndex)
        .map(item => item.product_id)
    return props.products.filter(p => !selectedIds.includes(p.id))
}

function addItem(): void {
    editForm.items.push({
        id: undefined,
        product_id: 0,
        quantity_ordered: 1,
        unit_cost: '0.00',
    });
}

function removeItem(index: number): void {
    editForm.items.splice(index, 1);
}

function onProductSelected(item: PurchaseOrderItemForm): void {
    const product = props.products.find((p) => p.id === item.product_id);

    if (product) {
        item.unit_cost = product.cost_price;
    }
}

function submitEditForm(): void {
    editForm.put(purchaseOrders.update(props.purchaseOrder.id).url, {
        onSuccess: () => {
            toast.success('Purchase order updated successfully.');
        },
        onError: (errors) => {
            const message =
                errors.status ||
                errors.items ||
                Object.values(errors)[0] ||
                'Failed to update purchase order.';
            toast.error(message);
        },
    });
}

</script>

<template>
    <div class="grid grid-cols-[70%_30%] gap-6">
        <!-- supplier details -->
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <template v-if="['draft', 'approved'].includes(purchaseOrder.status)">
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
                            <select v-model="editForm.supplier_id" class="select select-bordered w-full"
                                :class="{ 'select-error': editForm.errors.supplier_id }" :disabled="!props.can.update">
                                <option :value="null" disabled>Select a supplier</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                            <div v-if="editForm.errors.supplier_id" class="label">
                                <span class="label-text-alt text-error">{{ editForm.errors.supplier_id }}</span>
                            </div>
                        </label>

                        <label class="form-control">
                            <div class="label">
                                <span class="label-text">Email</span>
                            </div>
                            <input type="email" class="input input-bordered w-full"
                                :value="selectedSupplier?.email ?? ''" disabled />
                        </label>

                        <label class="form-control">
                            <div class="label">
                                <span class="label-text">Phone</span>
                            </div>
                            <input type="text" class="input input-bordered w-full"
                                :value="selectedSupplier?.phone ?? ''" disabled />
                        </label>

                        <label class="form-control col-span-2">
                            <div class="label">
                                <span class="label-text">Address</span>
                            </div>
                            <input type="text" class="input input-bordered w-full"
                                :value="selectedSupplier?.address ?? ''" disabled />
                        </label>
                    </div>
                </template>
                <template v-else>
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
                            <input type="email" class="input input-bordered w-full"
                                :value="purchaseOrder.supplier.email" disabled />
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
                            <input type="text" class="input input-bordered w-full"
                                :value="purchaseOrder.supplier.address" disabled />
                        </label>
                    </div>
                </template>
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
                    <template v-if="['draft', 'approved'].includes(purchaseOrder.status)">
                        <textarea v-model="editForm.notes" class="textarea textarea-bordered w-full" rows="5"
                            :class="{ 'textarea-error': editForm.errors.notes }" :disabled="!props.can.update"
                            placeholder="Add any notes for this purchase order..."></textarea>
                        <div v-if="editForm.errors.notes" class="label">
                            <span class="label-text-alt text-error">{{ editForm.errors.notes }}</span>
                        </div>
                    </template>
                    <template v-else>
                        <textarea class="textarea textarea-bordered w-full" rows="5" :value="purchaseOrder.notes ?? ''"
                            disabled></textarea>
                    </template>
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
                    <button v-if="['draft', 'approved'].includes(purchaseOrder.status)" type="button"
                        class="btn btn-success w-full" @click="submitEditForm"
                        :disabled="!editForm.isDirty || editForm.processing">
                        Save Changes
                    </button>
                    <button v-if="purchaseOrder.status === 'draft'" type="button" class="btn btn-info w-full"
                        @click="approve" :disabled="editForm.isDirty || editForm.processing || !props.can.approve">
                        Approve
                    </button>
                    <button v-if="purchaseOrder.status === 'approved'" type="button" class="btn btn-primary w-full"
                        @click="dispatch" :disabled="editForm.isDirty || editForm.processing || !props.can.dispatch">
                        Mark as Dispatched
                    </button>
                    <button v-if="['dispatched', 'partially_received'].includes(purchaseOrder.status)" type="button"
                        class="btn btn-warning w-full" @click="received"
                        :disabled="form.processing || !props.can.receive">
                        Confirm Received
                    </button>
                    <button v-if="purchaseOrder.status === 'partially_received'" type="button"
                        class="btn btn-neutral w-full" @click="close" :disabled="!props.can.close">
                        Close PO
                    </button>
                    <button
                        v-if="['draft', 'approved', 'dispatched', 'partially_received'].includes(purchaseOrder.status)"
                        type="button" class="btn btn-error btn-outline w-full" @click="cancel"
                        :disabled="editForm.isDirty || editForm.processing || !props.can.cancel">
                        Cancel
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
                <template v-if="['draft', 'approved'].includes(purchaseOrder.status)">
                    <button type="button" class="btn btn-primary btn-sm" @click="addItem" :disabled="!props.can.update">
                        Add Item
                    </button>
                </template>

            </div>

            <div class="overflow-x-auto">
                <template v-if="['draft', 'approved'].includes(purchaseOrder.status)">
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
                            <tr v-if="editForm.items.length === 0">
                                <td colspan="6" class="text-center text-base-content/60 py-6">
                                    No items added yet.
                                </td>
                            </tr>
                            <tr v-for="(item, index) in editForm.items" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <select v-model="item.product_id" class="select select-bordered select-sm w-full"
                                        :class="{ 'select-error': editForm.errors[`items.${index}.product_id`] }"
                                        :disabled="!props.can.update" @change="onProductSelected(item)">
                                        <option :value="0" disabled>Select a product</option>
                                        <option v-for="product in availableProducts(index)" :key="product.id"
                                            :value="product.id">
                                            {{ product.sku }} — {{ product.name }}
                                        </option>
                                    </select>
                                    <p v-if="editForm.errors[`items.${index}.product_id`]"
                                        class="text-error text-xs mt-1">
                                        {{ editForm.errors[`items.${index}.product_id`] }}
                                    </p>
                                </td>
                                <td>
                                    <input v-model.number="item.quantity_ordered" type="number" min="1"
                                        class="input input-bordered input-sm w-full" :disabled="!props.can.update"
                                        :class="{ 'input-error': editForm.errors[`items.${index}.quantity_ordered`] }" />
                                    <p v-if="editForm.errors[`items.${index}.quantity_ordered`]"
                                        class="text-error text-xs mt-1">
                                        {{ editForm.errors[`items.${index}.quantity_ordered`] }}
                                    </p>
                                </td>
                                <td>
                                    <input v-model="item.unit_cost" type="number" step="0.01" min="0"
                                        class="input input-bordered input-sm w-full" :disabled="!props.can.update"
                                        :class="{ 'input-error': editForm.errors[`items.${index}.unit_cost`] }" />
                                    <p v-if="editForm.errors[`items.${index}.unit_cost`]"
                                        class="text-error text-xs mt-1">
                                        {{ editForm.errors[`items.${index}.unit_cost`] }}
                                    </p>
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ formatSubtotal(item) }}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-ghost btn-sm text-error"
                                        :disabled="!props.can.update" @click="removeItem(index)">
                                        <Icon icon="heroicons:x-mark" class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <template v-else-if="['dispatched', 'partially_received'].includes(purchaseOrder.status)">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>Product</th>
                                <th class="w-32">Ordered</th>
                                <th class="w-32">Received</th>
                                <th class="w-32">Receiving</th>
                                <th class="w-40">Unit Cost (RM)</th>
                                <th class="w-32 text-right">Subtotal (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrder.items.length === 0">
                                <td colspan="7" class="text-center text-base-content/60 py-6">
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
                                <td class="tabular-nums">{{ item.quantity_received }}
                                    <span class="text-base-content/50 text-xs">({{ item.quantity_ordered -
                                        item.quantity_received }} left)</span>
                                </td>
                                <td class="tabular-nums"
                                    v-if="['dispatched', 'partially_received'].includes(purchaseOrder.status)">
                                    <div class="join w-full">
                                        <input v-model.number="form.items[index].quantity_received" type="number"
                                            min="0" :max="(item.quantity_ordered - item.quantity_received)" step="1"
                                            class="input input-bordered input-sm join-item w-full"
                                            :class="{ 'input-error': form.errors[`items.${index}.quantity_received`] }" />
                                        <button type="button"
                                            class="btn btn-sm btn-square btn-success btn-soft join-item"
                                            :title="`Fill remaining (${item.quantity_ordered - item.quantity_received})`"
                                            @click="form.items[index].quantity_received = item.quantity_ordered - item.quantity_received">
                                            <Icon icon="heroicons:check" class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p v-if="form.errors[`items.${index}.quantity_received`]"
                                        class="text-error text-xs mt-1">
                                        {{ form.errors[`items.${index}.quantity_received`] }}
                                    </p>
                                </td>
                                <td v-else class="tabular-nums">{{ item.quantity_received }}</td>
                                <td class="tabular-nums">{{ Number(item.unit_cost).toFixed(2) }}</td>
                                <td class="text-right tabular-nums">{{ formatSubtotal(item) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <template v-else>
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>Product</th>
                                <th class="w-32">Ordered</th>
                                <th class="w-32">Received</th>
                                <th class="w-40">Unit Cost (RM)</th>
                                <th class="w-32 text-right">Subtotal (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrder.items.length === 0">
                                <td colspan="7" class="text-center text-base-content/60 py-6">
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
                                <td class="tabular-nums">{{ item.quantity_received }}
                                    <span class="text-base-content/50 text-xs">({{ item.quantity_ordered -
                                        item.quantity_received }} left)</span>
                                </td>
                                <td class="tabular-nums">{{ Number(item.unit_cost).toFixed(2) }}</td>
                                <td class="text-right tabular-nums">{{ formatSubtotal(item) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>


            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Teleport to="body">
        <div class="modal" :class="{ 'modal-open': showCancelModal }" role="dialog" aria-modal="true">
            <div class="modal-box max-w-sm">
                <h2 class="text-lg font-semibold">Cancel Purchase Order</h2>
                <p class="mt-2 text-sm opacity-70">
                    Are you sure you want to cancel purchase order
                    <span class="font-medium">#{{ purchaseOrder.id }}</span>?
                    This action cannot be undone.
                </p>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="showCancelModal = false">
                        Keep
                    </button>
                    <button type="button" class="btn btn-error" @click="confirmCancel">
                        Cancel Order
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" @click="showCancelModal = false"></div>
        </div>
    </Teleport>

    <!-- Close Confirmation Modal -->
    <Teleport to="body">
        <div class="modal" :class="{ 'modal-open': showCloseModal }" role="dialog" aria-modal="true">
            <div class="modal-box max-w-sm">
                <h2 class="text-lg font-semibold">Close Purchase Order</h2>
                <p class="mt-2 text-sm opacity-70">
                    Are you sure you want to close purchase order
                    <span class="font-medium">#{{ purchaseOrder.id }}</span>?
                </p>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="showCloseModal = false">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-neutral" @click="confirmClose">
                        Close Order
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" @click="showCloseModal = false"></div>
        </div>
    </Teleport>
</template>