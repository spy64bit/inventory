<script setup lang="ts">
import { Form, useHttp } from '@inertiajs/vue3';
import { store } from '@/routes/product';
import { onMounted, ref } from 'vue';

const open = defineModel<boolean>('open', { required: true });

const http = useHttp({
    q: '',
});

type SelectOption = {
    id: number;
    name: string;
};

const suppliers = ref<SelectOption[]>([]);
const categories = ref<SelectOption[]>([]);

onMounted(() => {
    http.get('/api/suppliers').then((response) => {
        suppliers.value = response as SelectOption[];
    });

    http.get('/api/categories').then((response) => {
        categories.value = response as SelectOption[];
    });
});



</script>

<template>
    <Teleport to="body">
        <div class="modal" :class="{ 'modal-open': open }" role="dialog" aria-modal="true">
            <div class="modal-box max-w-md p-0">
                <Form :action="store()" #default="{ errors, processing }" @success="open = false">
                    <div class="border-base-200 border-b px-6 py-4">
                        <h2 class="text-lg font-semibold">Create New Product</h2>
                    </div>

                    <div class="space-y-3 px-6 py-5">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">SKU</legend>
                            <input name="sku" type="text" required class="input input-bordered w-full" />
                            <p v-if="errors.sku" class="fieldset-label text-error">{{ errors.sku }}</p>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Name</legend>
                            <input name="name" type="text" required class="input input-bordered w-full" />
                            <p v-if="errors.name" class="fieldset-label text-error">{{ errors.name }}</p>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Description</legend>
                            <input name="description" type="text" class="input input-bordered w-full" />
                            <p v-if="errors.description" class="fieldset-label text-error">{{ errors.description }}</p>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Cost Price</legend>
                            <input name="cost_price" type="number" step="0.01" min="0" :value="0" required
                                class="input input-bordered w-full" />
                            <p v-if="errors.cost_price" class="fieldset-label text-error">{{ errors.cost_price }}</p>
                        </fieldset>
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Reorder Level</legend>
                            <input name="reorder_level" type="number" min="0" :value="0" required
                                class="input input-bordered w-full" />
                            <p v-if="errors.reorder_level" class="fieldset-label text-error">{{ errors.reorder_level }}
                            </p>
                        </fieldset>

                        <!-- supplier -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Supplier</legend>
                            <select name="supplier_id" class="select select-bordered w-full">
                                <option disabled value="">Select a supplier</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                            <p v-if="errors.supplier_id" class="fieldset-label text-error">{{ errors.supplier_id }}</p>
                        </fieldset>
                        <!-- category -->
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Category</legend>
                            <select name="category_id" class="select select-bordered w-full">
                                <option disabled value="">Select a category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="errors.category_id" class="fieldset-label text-error">{{ errors.category_id }}</p>
                        </fieldset>
                    </div>

                    <div class="border-base-200 modal-action mt-0 border-t px-6 py-4">
                        <button type="button" class="btn btn-ghost" :disabled="processing" @click="open = false">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="processing">
                            <span v-if="processing" class="loading loading-spinner loading-xs"></span>
                            {{ processing ? 'Creating...' : 'Submit' }}
                        </button>
                    </div>
                </Form>
            </div>
            <div class="modal-backdrop" @click="open = false"></div>
        </div>
    </Teleport>
</template>
