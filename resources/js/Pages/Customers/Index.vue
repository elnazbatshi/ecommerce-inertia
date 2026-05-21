<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const visible = ref(false);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const rows = ref(Number(props.filters.rows ?? props.customers.per_page ?? 10));
const timeout = ref();

const form = useForm({
    name: '',
    phone: '',
    email: '',
    password: '',
    status: 'active'
});

const statuses = computed(() => [{ label: 'All statuses', value: null, severity: 'secondary' }, ...props.statusOptions]);
const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));

const load = (extra = {}) => {
    router.get('/customers', {
        search: search.value || undefined,
        status: status.value || undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch(status, () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.status = 'active';
    visible.value = true;
};

const save = () => {
    form.post('/customers', {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            form.reset();
        }
    });
};

const destroyCustomer = (customer) => {
    confirm.require({
        message: `Delete customer ${customer.phone}?`,
        header: 'Delete customer',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/customers/${customer.id}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="Customers">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="Customers" :breadcrumb="[{ label: 'Customers' }]">
            <template #pageAction>
                <Button label="New Customer" icon="pi pi-plus" @click="openCreate" />
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="customers.data"
                dataKey="id"
                lazy
                paginator
                :first="(customers.current_page - 1) * customers.per_page"
                :rows="customers.per_page"
                :totalRecords="customers.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="Search name, phone, email" />
                        </IconField>
                        <Select v-model="status" :options="statuses" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>No customers found.</template>

                <Column field="name" header="Name" style="min-width: 12rem">
                    <template #body="{ data }">{{ data.name || '-' }}</template>
                </Column>
                <Column field="phone" header="Mobile" style="min-width: 10rem" />
                <Column field="email" header="Email" style="min-width: 14rem">
                    <template #body="{ data }">{{ data.email || '-' }}</template>
                </Column>
                <Column field="status" header="Status" style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="statusMap[data.status]?.label ?? data.status" :severity="statusMap[data.status]?.severity ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="addresses_count" header="Addresses" style="width: 8rem" />
                <Column field="last_login_at" header="Last Login" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.last_login_at) }}</template>
                </Column>
                <Column field="created_at" header="Registered At" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.created_at) }}</template>
                </Column>
                <Column header="Actions" style="width: 10rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/customers/${data.id}`">
                                <Button icon="pi pi-eye" rounded text severity="info" aria-label="View" />
                            </Link>
                            <Link :href="`/customers/${data.id}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="Edit" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="Delete" @click="destroyCustomer(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="visible" modal header="New Customer" :style="{ width: '42rem', maxWidth: '95vw' }">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block font-medium">Name</label>
                    <InputText v-model="form.name" class="w-full" />
                    <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Mobile</label>
                    <InputText v-model="form.phone" class="w-full" />
                    <small v-if="form.errors.phone" class="text-red-600">{{ form.errors.phone }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Email</label>
                    <InputText v-model="form.email" class="w-full" />
                    <small v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Password</label>
                    <Password v-model="form.password" class="w-full" inputClass="w-full" toggleMask :feedback="false" />
                    <small v-if="form.errors.password" class="text-red-600">{{ form.errors.password }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Status</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    <small v-if="form.errors.status" class="text-red-600">{{ form.errors.status }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="visible = false" />
                <Button label="Save" icon="pi pi-check" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
