<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { ref, nextTick, computed } from 'vue';
import { useHttp } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    products: { type: Array, default: () => [] },
});

// ── HTTP clients ──────────────────────────────────────────────────────────────

const promptHttp = useHttp({
    prompt: '',
    conversation_id: null,
});

const confirmHttp = useHttp({
    action: '',
    confidence: null,
    supplier_id: null,
    supplier_matched: null,
    supplier_name: null,
    customer_id: null,
    customer_matched: null,
    customer_name: null,
    items: [],
    product: null,
    stock_check_ids: [],
    unresolved: [],
});

const loading = computed(() => promptHttp.processing || confirmHttp.processing);

// ── State ─────────────────────────────────────────────────────────────────────

const prompt = ref('');
const conversationId = ref(null);

/**
 * @typedef {{ role: 'user'|'assistant', text: string, parsed?: object, confirmed?: boolean, result?: object, error?: string }} Message
 * @type {import('vue').Ref<Message[]>}
 */
const messages = ref([]);
const chatContainer = ref(null);

// ── Actions ───────────────────────────────────────────────────────────────────

function sendPrompt() {
    const text = prompt.value.trim();
    if (!text || loading.value) return;

    messages.value.push({ role: 'user', text });
    prompt.value = '';
    scrollToBottom();

    promptHttp.prompt = text;
    promptHttp.conversation_id = conversationId.value;

    promptHttp.post('/ai-assistant/prompt', {
        onSuccess: (response) => {
            conversationId.value = response.conversation_id;
            messages.value.push({ role: 'assistant', text: '', commands: response.commands });
        },
        onHttpException: (response) => {
            const data = response.data ? JSON.parse(response.data) : null;
            const text = data?.error ?? 'Something went wrong. Please try again.';
            messages.value.push({ role: 'assistant', text, isError: true });
        },
        onError: () => {
            messages.value.push({ role: 'assistant', text: 'Something went wrong. Please try again.', isError: true });
        },
        onFinish: () => {
            scrollToBottom();
        },
    });
}

function confirmAction(messageIndex, commandIndex) {
    const msg = messages.value[messageIndex];
    const cmd = msg?.commands?.[commandIndex];
    if (!cmd) return;

    Object.assign(confirmHttp, cmd);

    confirmHttp.post('/ai-assistant/confirm', {
        onSuccess: (response) => {
            cmd.confirmed = true;
            cmd.result = response.result;
        },
        onError: (errors) => {
            cmd.confirmed = false;
            cmd.error = errors?.message ?? 'Action failed. Please try again.';
        },
        onHttpException: (response) => {
            cmd.confirmed = false;
            const data = response.data ? JSON.parse(response.data) : null;
            cmd.error = data?.error ?? 'Action failed. Please try again.';
        },
        onFinish: () => {
            scrollToBottom();
        },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

function formatAction(action) {
    const map = {
        create_purchase_order: 'Create Purchase Order',
        create_sales_order: 'Create Sales Order',
        add_product: 'Add Product',
        edit_product: 'Edit Product',
        check_stock: 'Check Stock',
        unknown: 'Unknown Action',
    };
    return map[action] ?? action;
}

function confidenceLabel(confidence) {
    if (confidence >= 0.85) return { label: 'High confidence', cls: 'badge-success' };
    if (confidence >= 0.5) return { label: 'Medium confidence', cls: 'badge-warning' };
    return { label: 'Low confidence', cls: 'badge-error' };
}

function hasItems(parsed) {
    return Array.isArray(parsed?.items) && parsed.items.length > 0;
}

function hasUnresolved(parsed) {
    return Array.isArray(parsed?.unresolved) && parsed.unresolved.length > 0;
}

function formatKey(key) {
    return String(key).replace(/_/g, ' ');
}

const pendingIndex = computed(() =>
    messages.value.findLastIndex(
        (m) => m.role === 'assistant' && m.commands?.some((c) => c.confirmed === undefined),
    ),
);

function cancelAction(messageIndex, commandIndex) {
    const msg = messages.value[messageIndex];
    const cmd = msg?.commands?.[commandIndex];
    if (cmd) {
        cmd.confirmed = false;
    }
}

function handleKeydown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendPrompt();
    }
}

const productMap = computed(() =>
    Object.fromEntries(props.products.map((p) => [p.id, p.name])),
);

function resolvedProductName(item) {
    if (item.matched && item.product_id && productMap.value[item.product_id]) {
        return productMap.value[item.product_id];
    }
    return item.product_name;
}

const productFields = [
    { key: 'name', label: 'Name' },
    { key: 'sku', label: 'SKU' },
    { key: 'cost_price', label: 'Cost Price' },
    { key: 'selling_price', label: 'Selling Price' },
    { key: 'category_id', label: 'Category' },
    { key: 'unit', label: 'Unit' },
    { key: 'id', label: 'Product ID' },
];

</script>

<template>

    <Head title="AI Assistant" />

    <div class="flex h-[calc(100vh-10rem)] flex-col">
        <!-- Header -->
        <div class="bg-base-100 border-base-300 flex items-center gap-3 border-b px-6 py-4">
            <div class="bg-primary/10 flex size-10 items-center justify-center rounded-full">
                <Icon icon="mdi:robot-excited" class="text-primary" width="22" height="22" />
            </div>
            <div>
                <h1 class="text-base font-semibold leading-tight">AI Assistant</h1>
                <p class="text-base-content/60 text-xs">Describe what you want to do in plain language</p>
            </div>
        </div>

        <!-- Messages -->
        <div ref="chatContainer" class="flex-1 space-y-4 overflow-y-auto px-4 py-5 sm:px-6">
            <!-- Empty state -->
            <div v-if="messages.length === 0"
                class="flex h-full flex-col items-center justify-center gap-3 text-center">
                <Icon icon="mdi:robot-happy-outline" class="text-base-content/20" width="64" height="64" />
                <p class="text-base-content/50 text-sm">Try: "Create a purchase order for 10 units of mineral water"</p>
            </div>

            <template v-for="(msg, index) in messages" :key="index">
                <!-- User message -->
                <div v-if="msg.role === 'user'" class="flex justify-end">
                    <div
                        class="bg-primary text-primary-content max-w-lg rounded-2xl rounded-tr-sm px-4 py-2.5 text-sm shadow-sm">
                        {{ msg.text }}
                    </div>
                </div>

                <!-- AI message -->
                <div v-else class="flex justify-start">
                    <div class="max-w-2xl w-full space-y-3">
                        <!-- Error text -->
                        <div v-if="msg.isError || (!msg.commands && msg.text)"
                            class="bg-error/10 text-error border-error/20 inline-block rounded-2xl rounded-tl-sm border px-4 py-2.5 text-sm">
                            {{ msg.text }}
                        </div>

                        <!-- Command cards (one per detected intent) -->
                        <template v-else-if="msg.commands">
                            <div v-for="(cmd, cmdIndex) in msg.commands" :key="cmdIndex"
                                class="bg-base-100 border-base-300 rounded-2xl rounded-tl-sm border shadow-sm">
                                <!-- Card header -->
                                <div class="border-base-300 flex items-center gap-3 border-b px-4 py-3">
                                    <Icon icon="mdi:robot-outline" class="text-primary shrink-0" width="18"
                                        height="18" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">
                                            AI wants to: <span class="text-primary">{{ formatAction(cmd.action)
                                                }}</span>
                                        </p>
                                    </div>
                                    <span class="badge badge-sm" :class="confidenceLabel(cmd.confidence).cls">
                                        {{ confidenceLabel(cmd.confidence).label }}
                                    </span>
                                </div>

                                <!-- Items table -->
                                <div v-if="hasItems(cmd)" class="p-4">
                                    <p class="text-base-content/60 mb-2 text-xs font-medium uppercase tracking-wide">
                                        Items
                                    </p>
                                    <div class="border-base-300 overflow-hidden rounded-lg border">
                                        <table class="table table-sm w-full">
                                            <thead>
                                                <tr class="bg-base-200">
                                                    <th>Product</th>
                                                    <th class="text-right">Qty</th>
                                                    <th class="text-right">Unit Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, i) in cmd.items" :key="i"
                                                    :class="!item.matched ? 'bg-error/5' : ''">
                                                    <td>
                                                        <span :class="!item.matched ? 'text-error' : ''">
                                                            {{ resolvedProductName(item) }}
                                                        </span>
                                                        <span v-if="!item.matched"
                                                            class="badge badge-error badge-xs ml-1">unmatched</span>
                                                    </td>
                                                    <td class="text-right">{{ item.quantity }}</td>
                                                    <td class="text-right">
                                                        {{ item.unit_price != null ?
                                                            `$${Number(item.unit_price).toFixed(2)}` : '—' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Product details (add/edit) -->
                                <div v-if="cmd.product && ['add_product', 'edit_product'].includes(cmd.action)"
                                    class="p-4">
                                    <p class="text-base-content/60 mb-2 text-xs font-medium uppercase tracking-wide">
                                        Product Details
                                    </p>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                        <template v-for="field in productFields" :key="field.key">
                                            <template
                                                v-if="cmd.product[field.key] !== null && cmd.product[field.key] !== undefined && String(cmd.product[field.key]).length">
                                                <span class="text-base-content/60">{{ field.label }}</span>
                                                <span>{{ cmd.product[field.key] }}</span>
                                            </template>
                                        </template>
                                    </div>
                                </div>

                                <!-- Stock check ids -->
                                <div v-if="cmd.action === 'check_stock' && cmd.stock_check_ids?.length" class="p-4">
                                    <p class="text-base-content/60 mb-1 text-xs font-medium uppercase tracking-wide">
                                        Checking stock for {{ cmd.stock_check_ids.length }} product(s)
                                    </p>
                                </div>

                                <!-- Unresolved products warning -->
                                <div v-if="hasUnresolved(cmd)"
                                    class="bg-warning/10 border-warning/20 mx-4 mb-4 flex items-start gap-2 rounded-lg border px-3 py-2">
                                    <Icon icon="mdi:alert-outline" class="text-warning mt-0.5 shrink-0" width="16"
                                        height="16" />
                                    <div class="text-xs">
                                        <span class="font-medium">Could not match:</span>
                                        {{ cmd.unresolved.join(', ') }}. Please confirm or modify your request.
                                    </div>
                                </div>

                                <!-- Supplier not found warning (non-blocking) -->
                                <div v-if="cmd.action === 'create_purchase_order' && cmd.supplier_matched === false"
                                    class="bg-warning/10 border-warning/20 mx-4 mb-4 flex items-start gap-2 rounded-lg border px-3 py-2">
                                    <Icon icon="mdi:account-alert-outline" class="text-warning mt-0.5 shrink-0"
                                        width="16" height="16" />
                                    <div class="text-xs">
                                        Supplier <span class="font-medium">'{{ cmd.supplier_name ?? 'unknown' }}'</span>
                                        not found — the order will be created without a supplier assigned.
                                    </div>
                                </div>

                                <!-- Customer not found warning (non-blocking) -->
                                <div v-if="cmd.action === 'create_sales_order' && cmd.customer_matched === false"
                                    class="bg-warning/10 border-warning/20 mx-4 mb-4 flex items-start gap-2 rounded-lg border px-3 py-2">
                                    <Icon icon="mdi:account-alert-outline" class="text-warning mt-0.5 shrink-0"
                                        width="16" height="16" />
                                    <div class="text-xs">
                                        Customer <span class="font-medium">'{{ cmd.customer_name ?? 'unknown' }}'</span>
                                        not found — the order will be created without a customer assigned.
                                    </div>
                                </div>

                                <!-- Unknown action -->
                                <div v-if="cmd.action === 'unknown'"
                                    class="bg-base-200/60 mx-4 mb-4 flex items-center gap-2 rounded-lg px-3 py-2 text-sm">
                                    <Icon icon="mdi:help-circle-outline" class="text-base-content/40 shrink-0"
                                        width="16" height="16" />
                                    <p class="text-base-content/50">I couldn't understand that request. Please try
                                        rephrasing.</p>
                                </div>

                                <!-- Success result -->
                                <div v-if="cmd.confirmed === true && cmd.result"
                                    class="bg-success/10 border-success/20 mx-4 mb-4 flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                                    <Icon icon="mdi:check-circle-outline" class="text-success shrink-0" width="18"
                                        height="18" />
                                    <div>
                                        <p class="font-medium">{{ cmd.result.message }}</p>
                                        <!-- Stock table -->
                                        <div v-if="cmd.result.products?.length" class="mt-2">
                                            <div class="border-base-300 overflow-hidden rounded-lg border">
                                                <table class="table table-xs w-full">
                                                    <thead>
                                                        <tr class="bg-base-200">
                                                            <th>Product</th>
                                                            <th>SKU</th>
                                                            <th class="text-right">Stock</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="p in cmd.result.products" :key="p.id">
                                                            <td>{{ p.name }}</td>
                                                            <td class="text-base-content/60">{{ p.sku }}</td>
                                                            <td class="text-right font-mono">{{ p.current_stock }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error result -->
                                <div v-if="cmd.confirmed === false && cmd.error"
                                    class="bg-error/10 border-error/20 mx-4 mb-4 flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                                    <Icon icon="mdi:close-circle-outline" class="text-error shrink-0" width="18"
                                        height="18" />
                                    <p>{{ cmd.error }}</p>
                                </div>

                                <!-- Cancelled state -->
                                <div v-if="cmd.confirmed === false && !cmd.error"
                                    class="bg-base-200/60 mx-4 mb-4 flex items-center gap-2 rounded-lg px-3 py-2 text-sm">
                                    <Icon icon="mdi:cancel" class="text-base-content/40 shrink-0" width="16"
                                        height="16" />
                                    <p class="text-base-content/50">Action cancelled.</p>
                                </div>

                                <!-- Action buttons -->
                                <div v-if="cmd.confirmed === undefined && cmd.action !== 'unknown'"
                                    class="border-base-300 flex gap-2 border-t px-4 py-3">
                                    <button class="btn btn-primary btn-sm" :disabled="loading || hasUnresolved(cmd)"
                                        @click="confirmAction(index, cmdIndex)">
                                        <Icon icon="mdi:check" width="16" height="16" />
                                        Confirm
                                    </button>
                                    <button class="btn btn-ghost btn-sm" :disabled="loading"
                                        @click="cancelAction(index, cmdIndex)">
                                        <Icon icon="mdi:close" width="16" height="16" />
                                        Cancel
                                    </button>
                                    <span v-if="hasUnresolved(cmd)"
                                        class="text-base-content/50 ml-1 self-center text-xs">
                                        Resolve unmatched products before confirming
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Typing indicator -->
            <div v-if="loading" class="flex justify-start">
                <div
                    class="bg-base-100 border-base-300 flex items-center gap-1 rounded-2xl rounded-tl-sm border px-4 py-3 shadow-sm">
                    <span class="bg-base-content/30 size-2 animate-bounce rounded-full" style="animation-delay: 0ms" />
                    <span class="bg-base-content/30 size-2 animate-bounce rounded-full"
                        style="animation-delay: 150ms" />
                    <span class="bg-base-content/30 size-2 animate-bounce rounded-full"
                        style="animation-delay: 300ms" />
                </div>
            </div>
        </div>

        <!-- Input area -->
        <div class="bg-base-100 border-base-300 border-t px-4 py-3 sm:px-6">
            <div class="flex gap-2">
                <textarea v-model="prompt" rows="2"
                    placeholder='e.g. "Create a purchase order for 5 units of mineral water"'
                    class="textarea textarea-bordered flex-1 resize-none text-sm leading-relaxed focus:outline-none"
                    :disabled="loading" @keydown="handleKeydown" />
                <button class="btn btn-primary self-end" :disabled="loading || !prompt.trim()" @click="sendPrompt">
                    <Icon v-if="loading" icon="mdi:loading" class="animate-spin" width="18" height="18" />
                    <Icon v-else icon="mdi:send" width="18" height="18" />
                </button>
            </div>
            <p class="text-base-content/40 mt-1.5 text-xs">Press Enter to send &bull; Shift+Enter for new line</p>
        </div>
    </div>
</template>
