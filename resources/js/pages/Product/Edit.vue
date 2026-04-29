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
                <Link :href="index.url()" class="link link-hover text-base-content/60 text-sm">
                    &larr; Back to Products
                </Link>
                <h1 class="mt-2 text-2xl font-bold">Edit Product</h1>
            </div>

            <form class="card bg-base-100 border-base-300 space-y-5 border p-6" @submit.prevent="submit">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">SKU</legend>
                    <input id="sku" v-model="form.sku" type="text" class="input input-bordered w-full" />
                    <p v-if="form.errors.sku" class="fieldset-label text-error">
                        {{ form.errors.sku }}
                    </p>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Name</legend>
                    <input id="name" v-model="form.name" type="text" class="input input-bordered w-full" />
                    <p v-if="form.errors.name" class="fieldset-label text-error">
                        {{ form.errors.name }}
                    </p>
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Description</legend>
                    <textarea id="description" v-model="form.description" rows="3"
                        class="textarea textarea-bordered w-full" />
                    <p v-if="form.errors.description" class="fieldset-label text-error">
                        {{ form.errors.description }}
                    </p>
                </fieldset>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Cost Price</legend>
                        <input id="cost_price" v-model="form.cost_price" type="number" step="0.01" min="0"
                            class="input input-bordered w-full" />
                        <p v-if="form.errors.cost_price" class="fieldset-label text-error">
                            {{ form.errors.cost_price }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Reorder Level</legend>
                        <input id="reorder_level" v-model="form.reorder_level" type="number" min="0"
                            class="input input-bordered w-full" />
                        <p v-if="form.errors.reorder_level" class="fieldset-label text-error">
                            {{ form.errors.reorder_level }}
                        </p>
                    </fieldset>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="index.url()" class="btn btn-ghost">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
