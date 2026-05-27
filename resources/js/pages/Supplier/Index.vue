<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { store, update, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/SupplierController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { ref, nextTick } from 'vue';
import { Icon } from '@iconify/vue';

defineOptions({
    layout: AppLayout,
});

type Supplier = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    lead_time_days: number | null;
    created_at: string;
    updated_at: string;
};

const props = defineProps<{
    suppliers: PaginatedData<Supplier>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'phone', label: 'Phone' },
    { key: 'lead_time_days', label: 'Lead Time', sortable: true },
    { key: 'created_at', label: 'Created At', sortable: true },
];

// Modal state
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const editingSupplier = ref<Supplier | null>(null);
const deletingSupplier = ref<Supplier | null>(null);
const nameInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    lead_time_days: '' as number | string,
});

function openCreateModal() {
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function openEditModal(supplier: Supplier) {
    editingSupplier.value = supplier;
    form.name = supplier.name;
    form.email = supplier.email ?? '';
    form.phone = supplier.phone ?? '';
    form.address = supplier.address ?? '';
    form.lead_time_days = supplier.lead_time_days ?? '';
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function closeFormModal() {
    showFormModal.value = false;
    form.clearErrors();
}

function submitForm() {
    if (editingSupplier.value) {
        form.put(update.url(editingSupplier.value.id), {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeFormModal(),
        });
    }
}

function openDeleteModal(supplier: Supplier) {
    deletingSupplier.value = supplier;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
}

function confirmDelete() {
    if (deletingSupplier.value) {
        router.delete(destroy.url(deletingSupplier.value.id), {
            onSuccess: () => closeDeleteModal(),
        });
    }
}

function bulkDelete(ids: number[], clearSelection: () => void) {
    if (confirm(`Are you sure you want to delete ${ids.length} suppliers?`)) {
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

    <Head title="Suppliers" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Suppliers</h1>
            <button type="button" class="btn btn-primary" @click="openCreateModal">
                New Supplier
            </button>
        </div>

        <DataTable :columns="columns" :rows="suppliers" :filters="filters" route-prefix="/supplier">
            <template #cell-email="{ value }">
                <a v-if="value" :href="`mailto:${value}`" class="link link-hover text-primary">
                    {{ value }}
                </a>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-phone="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-lead_time_days="{ value }">
                <span v-if="value !== null && value !== ''">{{ value }}d</span>
                <span v-else class="opacity-40">—</span>
            </template>

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
    <Teleport v-if="showFormModal" to="body">
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box max-w-md">
                <h2 class="text-lg font-semibold">
                    {{ editingSupplier ? 'Edit Supplier' : 'New Supplier' }}
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="submitForm">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Name <span class="text-error">*</span></legend>
                        <input id="modal-name" ref="nameInput" v-model="form.name" type="text"
                            class="input input-bordered w-full" placeholder="Supplier name" />
                        <p v-if="form.errors.name" class="text-error mt-1.5 text-sm">
                            {{ form.errors.name }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Email</legend>
                        <input v-model="form.email" type="email" class="input input-bordered w-full"
                            placeholder="orders@supplier.com" />
                        <p v-if="form.errors.email" class="text-error mt-1.5 text-sm">
                            {{ form.errors.email }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Phone</legend>
                        <input v-model="form.phone" type="text" class="input input-bordered w-full"
                            placeholder="03-12345678" />
                        <p v-if="form.errors.phone" class="text-error mt-1.5 text-sm">
                            {{ form.errors.phone }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Address</legend>
                        <textarea v-model="form.address" class="textarea textarea-bordered w-full"
                            placeholder="Street, City, State" rows="2" />
                        <p v-if="form.errors.address" class="text-error mt-1.5 text-sm">
                            {{ form.errors.address }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Lead Time (days)</legend>
                        <input v-model="form.lead_time_days" type="number" min="0" max="365"
                            class="input input-bordered w-full" placeholder="7" />
                        <p v-if="form.errors.lead_time_days" class="text-error mt-1.5 text-sm">
                            {{ form.errors.lead_time_days }}
                        </p>
                    </fieldset>

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="closeFormModal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : editingSupplier ? 'Save Changes' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop" @click="closeFormModal"></div>
        </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport v-if="showDeleteModal" to="body">
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box max-w-sm">
                <h2 class="text-lg font-semibold">Delete Supplier</h2>
                <p class="mt-2 text-sm opacity-70">
                    Are you sure you want to delete "<span class="font-medium">{{ deletingSupplier?.name }}</span>"?
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