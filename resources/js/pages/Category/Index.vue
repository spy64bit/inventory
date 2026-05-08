<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { router, useForm } from '@inertiajs/vue3';
import { store, update, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/CategoryController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { ref, nextTick } from 'vue';
import { Icon } from '@iconify/vue';

defineOptions({
    layout: AppLayout,
});

type Category = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
};

const props = defineProps<{
    categories: PaginatedData<Category>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'created_at', label: 'Created At', sortable: true },
];

// Modal state
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const editingCategory = ref<Category | null>(null);
const deletingCategory = ref<Category | null>(null);
const nameInput = ref<HTMLInputElement | null>(null);

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
            <h1 class="text-2xl font-bold">Categories</h1>
            <button type="button" class="btn btn-primary" @click="openCreateModal">
                Add Category
            </button>
        </div>

        <DataTable :columns="columns" :rows="categories" :filters="filters" route-prefix="/category">
            <template #cell-created_at="{ value }">
                {{ formatDate(value as string) }}
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button type="button" class="btn btn-sm btn-square btn-primary btn-soft"
                        @click="openEditModal(row)">
                        <Icon icon="heroicons:pencil" class="w-4 h-4" />
                    </button>
                    <button type="button" class="btn btn-sm btn-square btn-error btn-soft"
                        @click="openDeleteModal(row)">
                        <Icon icon="heroicons:trash" class="h-4 w-4" />
                    </button>
                </div>
            </template>

            <template #bulk-actions="{ selected, clearSelection }">
                <button type="button" class="btn btn-sm btn-error btn-soft"
                    @click="bulkDelete(selected, clearSelection)">
                    Delete Selected
                </button>
            </template>
        </DataTable>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
        <div class="modal" :class="{ 'modal-open': showFormModal }" role="dialog" aria-modal="true">
            <div class="modal-box max-w-md">
                <h2 class="text-lg font-semibold">
                    {{ editingCategory ? 'Edit Category' : 'Add Category' }}
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="submitForm">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Name</legend>
                        <input id="modal-name" ref="nameInput" v-model="form.name" type="text"
                            class="input input-bordered w-full" placeholder="Category name" />
                        <p v-if="form.errors.name" class="text-error mt-1.5 text-sm">
                            {{ form.errors.name }}
                        </p>
                    </fieldset>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="closeFormModal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : editingCategory ? 'Save Changes' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop" @click="closeFormModal"></div>
        </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div class="modal" :class="{ 'modal-open': showDeleteModal }" role="dialog" aria-modal="true">
            <div class="modal-box max-w-sm">
                <h2 class="text-lg font-semibold">Delete Category</h2>
                <p class="mt-2 text-sm opacity-70">
                    Are you sure you want to delete "<span class="font-medium">{{ deletingCategory?.name }}</span>"?
                    This action cannot be undone.
                </p>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" @click="closeDeleteModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-error" @click="confirmDelete">
                        Delete
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" @click="closeDeleteModal"></div>
        </div>
    </Teleport>
</template>