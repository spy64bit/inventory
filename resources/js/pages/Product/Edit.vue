<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { update, index } from '@/actions/App/Http/Controllers/ProductController';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    product: {
        id: number;
        sku: string;
        name: string;
        description: string | null;
        cost_price: string;
        reorder_level: number;
    };
}>();

const form = useForm({
    sku: props.product.sku,
    name: props.product.name,
    description: props.product.description ?? '',
    cost_price: props.product.cost_price,
    reorder_level: props.product.reorder_level,
});

function submit() {
    form.put(update.url(props.product.id));
}
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <div class="mb-6">
                <Link :href="index.url()" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Products
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit Product</h1>
            </div>

            <form class="space-y-5 rounded-lg border border-gray-200 bg-white p-6" @submit.prevent="submit">
                <div>
                    <label for="sku" class="mb-1.5 block text-sm font-medium text-gray-700">
                        SKU
                    </label>
                    <input id="sku" v-model="form.sku" type="text"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                    <p v-if="form.errors.sku" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.sku }}
                    </p>
                </div>

                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Name
                    </label>
                    <input id="name" v-model="form.name" type="text"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea id="description" v-model="form.description" rows="3"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                    <p v-if="form.errors.description" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="cost_price" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Cost Price
                        </label>
                        <input id="cost_price" v-model="form.cost_price" type="number" step="0.01" min="0"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                        <p v-if="form.errors.cost_price" class="mt-1.5 text-sm text-red-600">
                            {{ form.errors.cost_price }}
                        </p>
                    </div>

                    <div>
                        <label for="reorder_level" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Reorder Level
                        </label>
                        <input id="reorder_level" v-model="form.reorder_level" type="number" min="0"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none" />
                        <p v-if="form.errors.reorder_level" class="mt-1.5 text-sm text-red-600">
                            {{ form.errors.reorder_level }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="index.url()"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
