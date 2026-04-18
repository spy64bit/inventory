<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { router, useForm } from '@inertiajs/vue3';
import { store, update, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/CategoryController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { ref, nextTick } from 'vue';

defineOptions({
    layout: AppLayout,
});

type Category = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
};

const props = defineProps < {
    categories: PaginatedData < Category >;
    filters: Filters;
} > ();

const columns: Column[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'created_at', label: 'Created At', sortable: true },
];

// Modal state
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const editingCategory = ref < Category | null > (null);
const deletingCategory = ref < Category | null > (null);
const nameInput = ref < HTMLInputElement | null > (null);

const form = useForm({
    name: '',
});

function openCreateModal() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function openEditModal(category: Category) {
    editingCategory.value = category;
    form.name = category.name;
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function closeFormModal() {
    showFormModal.value = false;
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingCategory.value) {
        form.put(update.url(editingCategory.value.id), {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeFormModal(),
        });
    }
}

function openDeleteModal(category: Category) {
    deletingCategory.value = category;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deletingCategory.value = null;
}

function confirmDelete() {
    if (deletingCategory.value) {
        router.delete(destroy.url(deletingCategory.value.id), {
            onSuccess: () => closeDeleteModal(),
        });
    }
}

function bulkDelete(ids: number[], clearSelection: () => void) {
    if (confirm(`Are you sure you want to delete ${ids.length} categories?`)) {
        router.delete(bulkDestroy.url(), {
            data: { ids },
            onSuccess: () => clearSelection(),
        });
    }
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
            <button type="button"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 focus:outline-none"
                @click="openCreateModal">
                Add Category
            </button>
        </div>

        <DataTable :columns="columns" :rows="categories" :filters="filters" route-prefix="/category">
            <template #cell-created_at="{ value }">
                {{ formatDate(value as string) }}
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <button type="button"
                        class="inline-flex items-center rounded-md px-2.5 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900"
                        @click="openEditModal(row)">
                        Edit
                    </button>
                    <button type="button"
                        class="inline-flex items-center rounded-md px-2.5 py-1.5 text-sm font-medium text-red-600 hover:text-red-900"
                        @click="openDeleteModal(row)">
                        Delete
                    </button>
                </div>
            </template>

            <template #bulk-actions="{ selected, clearSelection }">
                <button type="button"
                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
                    @click="bulkDelete(selected, clearSelection)">
                    Delete Selected
                </button>
            </template>
        </DataTable>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
        <div v-if="showFormModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/40" @click="closeFormModal" />
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingCategory ? 'Edit Category' : 'Add Category' }}
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="submitForm">
                    <div>
                        <label for="modal-name" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input id="modal-name" ref="nameInput" v-model="form.name" type="text"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                            placeholder="Category name" />
                        <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button"
                            class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                            @click="closeFormModal">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : editingCategory ? 'Save Changes' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/40" @click="closeDeleteModal" />
            <div class="relative w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Delete Category</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete "<span class="font-medium">{{ deletingCategory?.name }}</span>"?
                    This
                    action cannot be undone.
                </p>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="button"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                        @click="closeDeleteModal">
                        Cancel
                    </button>
                    <button type="button"
                        class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:ring-2 focus:ring-red-500/50 focus:ring-offset-2 focus:outline-none"
                        @click="confirmDelete">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>