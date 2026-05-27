<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, WhenVisible } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

defineOptions({
    layout: AppLayout
});

type Supplier = { id: number; name: string } | null;
type Category = { id: number; name: string } | null;

interface Stats {
    total_products: number;
    low_stock_count: number;
    inventory_value: number;
    open_orders_count: number;
}

interface LowStockProduct {
    id: number;
    sku: string;
    name: string;
    current_stock: number;
    reorder_level: number;
    supplier: Supplier;
    category: Category;
}

interface StockMovement {
    id: number;
    type: 'in' | 'out';
    quantity: number;
    reference_type: string | null;
    created_at: string;
    product: { id: number; sku: string; name: string } | null;
    user: { id: number; name: string } | null;
}

interface RecentOrder {
    id: number;
    status: string;
    created_at: string;
    items_count: number;
    supplier: Supplier;
    created_by: { id: number; name: string } | null;
}

defineProps<{
    stats: Stats;
    ordersByStatus: Record<string, number>;
    lowStockProducts?: LowStockProduct[] | null;
    recentMovements?: StockMovement[] | null;
    recentOrders?: RecentOrder[] | null;
}>();

const statusBadgeClass: Record<string, string> = {
    draft: 'badge-ghost',
    approved: 'badge-info',
    dispatched: 'badge-primary',
    partially_received: 'badge-warning',
    received: 'badge-success',
    closed: 'badge-neutral',
    cancelled: 'badge-error',
};

const statusOrder = ['draft', 'approved', 'dispatched', 'partially_received', 'received', 'closed', 'cancelled'];

function formatStatus(status: string): string {
    return status.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
}

function formatDate(value: string): string {
    return new Date(value).toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>

    <Head title="Dashboard" />
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">Dashboard</h1>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="bg-base-100 rounded-box border border-base-300 p-5 flex items-center gap-4">
                <div class="bg-primary/10 text-primary rounded-btn p-3">
                    <Icon icon="mdi:package-variant" width="24" height="24" />
                </div>
                <div>
                    <p class="text-sm text-base-content/60">Total Products</p>
                    <p class="text-2xl font-bold">{{ stats.total_products }}</p>
                </div>
            </div>

            <div class="bg-base-100 rounded-box border border-base-300 p-5 flex items-center gap-4">
                <div class="bg-warning/10 text-warning rounded-btn p-3">
                    <Icon icon="mdi:alert-circle-outline" width="24" height="24" />
                </div>
                <div>
                    <p class="text-sm text-base-content/60">Low Stock</p>
                    <p class="text-2xl font-bold">{{ stats.low_stock_count }}</p>
                </div>
            </div>

            <div class="bg-base-100 rounded-box border border-base-300 p-5 flex items-center gap-4">
                <div class="bg-success/10 text-success rounded-btn p-3">
                    <Icon icon="mdi:cash-multiple" width="24" height="24" />
                </div>
                <div>
                    <p class="text-sm text-base-content/60">Inventory Value</p>
                    <p class="text-2xl font-bold">{{ formatCurrency(stats.inventory_value) }}</p>
                </div>
            </div>

            <div class="bg-base-100 rounded-box border border-base-300 p-5 flex items-center gap-4">
                <div class="bg-info/10 text-info rounded-btn p-3">
                    <Icon icon="mdi:cart-outline" width="24" height="24" />
                </div>
                <div>
                    <p class="text-sm text-base-content/60">Open Orders</p>
                    <p class="text-2xl font-bold">{{ stats.open_orders_count }}</p>
                </div>
            </div>
        </div>

        <!-- PO Status Breakdown -->
        <div class="bg-base-100 rounded-box border border-base-300 p-5">
            <h2 class="font-semibold mb-3">Purchase Orders by Status</h2>
            <div class="flex flex-wrap gap-2">
                <template v-for="key in statusOrder" :key="key">
                    <div v-if="ordersByStatus[key]" class="flex items-center gap-1.5">
                        <span :class="['badge badge-sm', statusBadgeClass[key]]">{{ formatStatus(key) }}</span>
                        <span class="text-sm font-medium">{{ ordersByStatus[key] }}</span>
                    </div>
                </template>
                <p v-if="!Object.keys(ordersByStatus).length" class="text-sm text-base-content/50">No purchase orders
                    yet.</p>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-base-100 rounded-box border border-base-300 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                <h2 class="font-semibold flex items-center gap-2">
                    <Icon icon="mdi:alert-circle-outline" class="text-warning" width="20" />
                    Low Stock Alerts
                </h2>
                <Link href="/product" class="btn btn-ghost btn-xs">View all</Link>
            </div>

            <WhenVisible data="lowStockProducts">
                <!-- Skeleton -->
                <template #fallback>
                    <div class="p-4 space-y-2">
                        <div v-for="i in 4" :key="i" class="skeleton h-8 w-full" />
                    </div>
                </template>

                <template v-if="lowStockProducts && lowStockProducts.length > 0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th class="text-right">Stock</th>
                                <th class="text-right">Reorder Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in lowStockProducts" :key="product.id">
                                <td class="font-mono text-xs">{{ product.sku }}</td>
                                <td>
                                    <Link :href="`/product/${product.id}/edit`" class="link link-hover font-medium">
                                        {{ product.name }}
                                    </Link>
                                </td>
                                <td>{{ product.category?.name ?? '—' }}</td>
                                <td>{{ product.supplier?.name ?? '—' }}</td>
                                <td class="text-right">
                                    <span
                                        :class="product.current_stock === 0 ? 'badge badge-error badge-sm' : 'badge badge-warning badge-sm'">
                                        {{ product.current_stock }}
                                    </span>
                                </td>
                                <td class="text-right text-base-content/60">{{ product.reorder_level }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>
                <p v-else class="px-5 py-4 text-sm text-base-content/50">All products are sufficiently stocked.</p>
            </WhenVisible>
        </div>

        <!-- Bottom two-column -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            <!-- Recent Stock Movements -->
            <div class="bg-base-100 rounded-box border border-base-300 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                    <h2 class="font-semibold">Recent Stock Movements</h2>
                    <Link href="/stock-movement" class="btn btn-ghost btn-xs">View all</Link>
                </div>

                <WhenVisible data="recentMovements">
                    <template #fallback>
                        <div class="p-4 space-y-2">
                            <div v-for="i in 5" :key="i" class="skeleton h-7 w-full" />
                        </div>
                    </template>

                    <template v-if="recentMovements && recentMovements.length > 0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th class="text-right">Qty</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="movement in recentMovements" :key="movement.id">
                                    <td class="max-w-32 truncate">{{ movement.product?.name ?? '—' }}</td>
                                    <td>
                                        <span
                                            :class="movement.type === 'in' ? 'badge badge-success badge-soft badge-sm' : 'badge badge-error badge-soft badge-sm'">
                                            {{ movement.type === 'in' ? 'Stock In' : 'Stock Out' }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono">{{ movement.quantity }}</td>
                                    <td class="text-xs text-base-content/60">{{ formatDate(movement.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <p v-else class="px-5 py-4 text-sm text-base-content/50">No stock movements yet.</p>
                </WhenVisible>
            </div>

            <!-- Recent Purchase Orders -->
            <div class="bg-base-100 rounded-box border border-base-300 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-base-300">
                    <h2 class="font-semibold">Recent Purchase Orders</h2>
                    <Link href="/purchase-orders" class="btn btn-ghost btn-xs">View all</Link>
                </div>

                <WhenVisible data="recentOrders">
                    <template #fallback>
                        <div class="p-4 space-y-2">
                            <div v-for="i in 5" :key="i" class="skeleton h-7 w-full" />
                        </div>
                    </template>

                    <template v-if="recentOrders && recentOrders.length > 0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>PO #</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th class="text-right">Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in recentOrders" :key="order.id">
                                    <td>
                                        <Link :href="`/purchase-orders/${order.id}`" class="link link-hover font-mono">
                                            #{{ order.id }}
                                        </Link>
                                    </td>
                                    <td>{{ order.supplier?.name ?? '—' }}</td>
                                    <td>
                                        <span :class="['badge badge-sm', statusBadgeClass[order.status]]">
                                            {{ formatStatus(order.status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">{{ order.items_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <p v-else class="px-5 py-4 text-sm text-base-content/50">No purchase orders yet.</p>
                </WhenVisible>
            </div>
        </div>
    </div>
</template>
