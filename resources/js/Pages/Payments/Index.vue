<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    orders: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    methodOptions: { type: Array, default: () => [] },
    gatewayOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const visible = ref(false);
const editing = ref(null);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const method = ref(props.filters.method ?? null);
const gateway = ref(props.filters.gateway ?? null);
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const rows = ref(Number(props.filters.rows ?? props.payments.per_page ?? 10));
const timeout = ref();

const form = useForm({
    _method: 'post',
    order_id: null,
    amount: 0,
    method: 'online',
    gateway: '',
    transaction_id: '',
    reference_id: '',
    status: 'pending',
    raw_response_text: '',
    admin_note: ''
});

const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));
const methodMap = computed(() => Object.fromEntries(props.methodOptions.map((item) => [item.value, item])));
const statusFilters = computed(() => [{ label: 'All statuses', value: null }, ...props.statusOptions]);
const methodFilters = computed(() => [{ label: 'All methods', value: null }, ...props.methodOptions]);
const gatewayFilters = computed(() => [{ label: 'All gateways', value: null }, ...props.gatewayOptions]);
const money = (value) => Number(value ?? 0).toLocaleString('fa-IR');

const parseRawResponse = () => {
    if (!form.raw_response_text?.trim()) {
        return null;
    }

    try {
        return JSON.parse(form.raw_response_text);
    } catch {
        return { raw: form.raw_response_text };
    }
};

const load = (extra = {}) => {
    router.get('/admin/payments', {
        search: search.value || undefined,
        status: status.value || undefined,
        method: method.value || undefined,
        gateway: gateway.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([status, method, gateway, dateFrom, dateTo], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'post';
    form.method = 'online';
    form.status = 'pending';
    visible.value = true;
};

const openEdit = (payment) => {
    editing.value = payment;
    form.clearErrors();
    form._method = 'put';
    form.order_id = payment.order_id;
    form.amount = Number(payment.amount ?? 0);
    form.method = payment.method;
    form.gateway = payment.gateway ?? '';
    form.transaction_id = payment.transaction_id ?? '';
    form.reference_id = payment.reference_id ?? '';
    form.status = payment.status;
    form.raw_response_text = '';
    form.admin_note = payment.admin_note ?? '';
    visible.value = true;
};

const onOrderChange = () => {
    const order = props.orders.find((item) => item.id === form.order_id);
    if (order && !editing.value) {
        form.amount = Number(order.total ?? 0);
    }
};

const save = () => {
    const url = editing.value ? `/admin/payments/${editing.value.id}` : '/admin/payments';

    form
        .transform((data) => ({
            order_id: data.order_id,
            amount: data.amount,
            method: data.method,
            gateway: data.gateway || null,
            transaction_id: data.transaction_id || null,
            reference_id: data.reference_id || null,
            status: data.status,
            raw_response: parseRawResponse(),
            admin_note: data.admin_note || null,
            _method: data._method
        }))
        .post(url, {
            preserveScroll: true,
            onSuccess: () => {
                visible.value = false;
            }
        });
};

const refund = (payment) => {
    confirm.require({
        message: `Refund payment for ${payment.order?.order_number}?`,
        header: 'Refund payment',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Refund',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-warning',
        accept: () => router.patch(`/admin/payments/${payment.id}/refund`, {}, { preserveScroll: true })
    });
};

const destroyPayment = (payment) => {
    confirm.require({
        message: `Delete payment ${payment.transaction_id || payment.id}?`,
        header: 'Delete payment',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/payments/${payment.id}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="Payments">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="Payments" :breadcrumb="[{ label: 'Payments' }]">
            <template #pageAction>
                <Button label="New Payment" icon="pi pi-plus" @click="openCreate" />
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="payments.data"
                dataKey="id"
                lazy
                paginator
                :first="(payments.current_page - 1) * payments.per_page"
                :rows="payments.per_page"
                :totalRecords="payments.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="Order, customer, ref" />
                        </IconField>
                        <Select v-model="status" :options="statusFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="method" :options="methodFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="gateway" :options="gatewayFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <PersianDatePicker v-model="dateFrom" placeholder="از تاریخ" />
                        <PersianDatePicker v-model="dateTo" placeholder="تا تاریخ" />
                    </div>
                </template>

                <template #empty>No payments found.</template>

                <Column header="Order #" style="min-width: 11rem">
                    <template #body="{ data }">{{ data.order?.order_number || '-' }}</template>
                </Column>
                <Column header="Customer" style="min-width: 12rem">
                    <template #body="{ data }">{{ data.customer?.name || data.customer?.phone || '-' }}</template>
                </Column>
                <Column field="amount" header="Amount" style="min-width: 9rem">
                    <template #body="{ data }">{{ money(data.amount) }}</template>
                </Column>
                <Column field="method" header="Method" style="min-width: 9rem">
                    <template #body="{ data }">{{ methodMap[data.method]?.label ?? data.method }}</template>
                </Column>
                <Column field="gateway" header="Gateway" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.gateway || '-' }}</template>
                </Column>
                <Column field="transaction_id" header="Transaction" style="min-width: 11rem">
                    <template #body="{ data }">{{ data.transaction_id || '-' }}</template>
                </Column>
                <Column field="reference_id" header="Reference" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.reference_id || '-' }}</template>
                </Column>
                <Column field="status" header="Status" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="statusMap[data.status]?.label ?? data.status" :severity="statusMap[data.status]?.severity ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="paid_at" header="Paid At" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.paid_at) }}</template>
                </Column>
                <Column field="created_at" header="Created At" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.created_at) }}</template>
                </Column>
                <Column header="Actions" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/payments/${data.id}`">
                                <Button icon="pi pi-eye" rounded text severity="info" aria-label="View" />
                            </Link>
                            <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="Edit" @click="openEdit(data)" />
                            <Button icon="pi pi-refresh" rounded text severity="warn" aria-label="Refund" :disabled="data.status === 'refunded'" @click="refund(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="Delete" @click="destroyPayment(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="visible" modal :header="editing ? 'Edit Payment' : 'New Payment'" :style="{ width: '56rem', maxWidth: '95vw' }">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-2 block font-medium">Order</label>
                    <Select v-model="form.order_id" :options="orders" optionLabel="label" optionValue="id" filter class="w-full" @change="onOrderChange" />
                    <small v-if="form.errors.order_id" class="text-red-600">{{ form.errors.order_id }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Amount</label>
                    <InputNumber v-model="form.amount" inputClass="w-full" :min="0" />
                    <small v-if="form.errors.amount" class="text-red-600">{{ form.errors.amount }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Method</label>
                    <Select v-model="form.method" :options="methodOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Gateway</label>
                    <InputText v-model="form.gateway" class="w-full" placeholder="zarinpal, idpay, stripe" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Status</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Transaction ID</label>
                    <InputText v-model="form.transaction_id" class="w-full" />
                    <small v-if="form.errors.transaction_id" class="text-red-600">{{ form.errors.transaction_id }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Reference ID</label>
                    <InputText v-model="form.reference_id" class="w-full" />
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block font-medium">Raw Response JSON</label>
                    <Textarea v-model="form.raw_response_text" rows="4" class="w-full" />
                    <small v-if="form.errors.raw_response" class="text-red-600">{{ form.errors.raw_response }}</small>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block font-medium">Admin Note</label>
                    <Textarea v-model="form.admin_note" rows="3" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="visible = false" />
                <Button label="Save" icon="pi pi-check" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
