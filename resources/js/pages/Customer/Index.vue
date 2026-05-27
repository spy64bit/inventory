<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { store, update, destroy } from '@/actions/App/Http/Controllers/CustomerController';
import type { Column, Filters, PaginatedData } from '@/types/data-table';
import { ref, nextTick } from 'vue';
import { Icon } from '@iconify/vue';

defineOptions({
    layout: AppLayout,
});

type Customer = {
    id: number;
    name: string;
    email: string;
    contact_no: string | null;
    address: string | null;
    created_at: string;
    updated_at: string;
};

defineProps<{
    customers: PaginatedData<Customer>;
    filters: Filters;
}>();

const columns: Column[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'contact_no', label: 'Contact No', sortable: true },
    { key: 'address', label: 'Address' },
    { key: 'created_at', label: 'Created At', sortable: true },
];

// Modal state
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const editingCustomer = ref<Customer | null>(null);
const deletingCustomer = ref<Customer | null>(null);
const nameInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: '',
    email: '',
    contact_no: '',
    address: '',
});

function openCreateModal() {
    editingCustomer.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function openEditModal(customer: Customer) {
    editingCustomer.value = customer;
    form.name = customer.name;
    form.email = customer.email;
    form.contact_no = customer.contact_no ?? '';
    form.address = customer.address ?? '';
    form.clearErrors();
    showFormModal.value = true;
    nextTick(() => nameInput.value?.focus());
}

function closeFormModal() {
    showFormModal.value = false;
    form.clearErrors();
}

function submitForm() {
    if (editingCustomer.value) {
        form.put(update.url(editingCustomer.value.id), {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeFormModal(),
        });
    }
}

function openDeleteModal(customer: Customer) {
    deletingCustomer.value = customer;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
}

function confirmDelete() {
    if (deletingCustomer.value) {
        router.delete(destroy.url(deletingCustomer.value.id), {
            onSuccess: () => closeDeleteModal(),
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

    <Head title="Customer" />

    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Customers</h1>
            <button type="button" class="btn btn-primary" @click="openCreateModal">
                New Customer
            </button>
        </div>

        <DataTable :columns="columns" :rows="customers" :filters="filters" route-prefix="/customer">
            <template #cell-email="{ value }">
                <a v-if="value" :href="`mailto:${value}`" class="link link-hover text-primary">
                    {{ value }}
                </a>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-contact_no="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="opacity-40">—</span>
            </template>

            <template #cell-address="{ value }">
                <span v-if="value">{{ value }}</span>
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
        </DataTable>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport v-if="showFormModal" to="body">
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box max-w-md">
                <h2 class="text-lg font-semibold">
                    {{ editingCustomer ? 'Edit Customer' : 'New Customer' }}
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="submitForm">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Name <span class="text-error">*</span></legend>
                        <input id="modal-name" ref="nameInput" v-model="form.name" type="text"
                            class="input input-bordered w-full" placeholder="Customer name" />
                        <p v-if="form.errors.name" class="text-error mt-1.5 text-sm">
                            {{ form.errors.name }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Email <span class="text-error">*</span></legend>
                        <input v-model="form.email" type="email" class="input input-bordered w-full"
                            placeholder="customer@example.com" />
                        <p v-if="form.errors.email" class="text-error mt-1.5 text-sm">
                            {{ form.errors.email }}
                        </p>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Contact No</legend>
                        <input v-model="form.contact_no" type="text" class="input input-bordered w-full"
                            placeholder="03-12345678" />
                        <p v-if="form.errors.contact_no" class="text-error mt-1.5 text-sm">
                            {{ form.errors.contact_no }}
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

                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost" @click="closeFormModal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : editingCustomer ? 'Save Changes' : 'Create' }}
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
                <h2 class="text-lg font-semibold">Delete Customer</h2>
                <p class="mt-2 text-sm opacity-70">
                    Are you sure you want to delete "<span class="font-medium">{{ deletingCustomer?.name }}</span>"?
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