<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

const props = defineProps({
    customer: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const addressVisible = ref(false);
const editingAddress = ref(null);

const customerForm = useForm({
    _method: 'put',
    name: props.customer.name ?? '',
    phone: props.customer.phone ?? '',
    email: props.customer.email ?? '',
    password: '',
    status: props.customer.status ?? 'active'
});

const addressForm = useForm({
    _method: 'post',
    title: '',
    receiver_name: '',
    receiver_phone: '',
    province: '',
    city: '',
    postal_code: '',
    address: '',
    plaque: '',
    unit: '',
    latitude: null,
    longitude: null,
    is_default: false
});

const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));

const saveCustomer = () => {
    customerForm.post(`/customers/${props.customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            customerForm.password = '';
        }
    });
};

const emptyAddressForm = () => ({
    _method: 'post',
    title: '',
    receiver_name: props.customer.name ?? '',
    receiver_phone: props.customer.phone ?? '',
    province: '',
    city: '',
    postal_code: '',
    address: '',
    plaque: '',
    unit: '',
    latitude: null,
    longitude: null,
    is_default: props.customer.addresses.length === 0
});

const openCreateAddress = () => {
    editingAddress.value = null;
    Object.assign(addressForm, emptyAddressForm());
    addressForm.clearErrors();
    addressVisible.value = true;
};

const openEditAddress = (address) => {
    editingAddress.value = address;
    addressForm._method = 'put';
    addressForm.title = address.title ?? '';
    addressForm.receiver_name = address.receiver_name ?? '';
    addressForm.receiver_phone = address.receiver_phone ?? '';
    addressForm.province = address.province ?? '';
    addressForm.city = address.city ?? '';
    addressForm.postal_code = address.postal_code ?? '';
    addressForm.address = address.address ?? '';
    addressForm.plaque = address.plaque ?? '';
    addressForm.unit = address.unit ?? '';
    addressForm.latitude = address.latitude ? Number(address.latitude) : null;
    addressForm.longitude = address.longitude ? Number(address.longitude) : null;
    addressForm.is_default = Boolean(address.is_default);
    addressForm.clearErrors();
    addressVisible.value = true;
};

const saveAddress = () => {
    const url = editingAddress.value
        ? `/customers/${props.customer.id}/addresses/${editingAddress.value.id}`
        : `/customers/${props.customer.id}/addresses`;

    addressForm.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            addressVisible.value = false;
        }
    });
};

const deleteAddress = (address) => {
    confirm.require({
        message: `Delete address ${address.title || address.city}?`,
        header: 'Delete address',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/customers/${props.customer.id}/addresses/${address.id}`, { preserveScroll: true })
    });
};

const setDefault = (address) => {
    router.patch(`/customers/${props.customer.id}/addresses/${address.id}/default`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Customer ${customer.phone}`">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle :title="`Customer ${customer.phone}`" :breadcrumb="[{ label: 'Customers', href: '/customers' }, { label: 'Edit' }]">
            <template #pageAction>
                <Link href="/customers">
                    <Button label="Back" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-1">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Customer Info</h2>
                    <Tag :value="statusMap[customer.status]?.label ?? customer.status" :severity="statusMap[customer.status]?.severity ?? 'secondary'" />
                </div>
                <form class="space-y-4" @submit.prevent="saveCustomer">
                    <div>
                        <label class="mb-2 block font-medium">Name</label>
                        <InputText v-model="customerForm.name" class="w-full" />
                        <small v-if="customerForm.errors.name" class="text-red-600">{{ customerForm.errors.name }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">Mobile</label>
                        <InputText v-model="customerForm.phone" class="w-full" />
                        <small v-if="customerForm.errors.phone" class="text-red-600">{{ customerForm.errors.phone }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">Email</label>
                        <InputText v-model="customerForm.email" class="w-full" />
                        <small v-if="customerForm.errors.email" class="text-red-600">{{ customerForm.errors.email }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">New Password</label>
                        <Password v-model="customerForm.password" class="w-full" inputClass="w-full" toggleMask :feedback="false" />
                        <small v-if="customerForm.errors.password" class="text-red-600">{{ customerForm.errors.password }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">Status</label>
                        <Select v-model="customerForm.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <small v-if="customerForm.errors.status" class="text-red-600">{{ customerForm.errors.status }}</small>
                    </div>
                    <Button type="submit" label="Save Customer" icon="pi pi-check" :loading="customerForm.processing" class="w-full" />
                </form>
            </div>

            <div class="card xl:col-span-2">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Addresses</h2>
                    <Button label="Add Address" icon="pi pi-plus" severity="secondary" @click="openCreateAddress" />
                </div>

                <DataTable :value="customer.addresses" dataKey="id" showGridlines>
                    <template #empty>No addresses registered.</template>
                    <Column header="Title" style="min-width: 9rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span>{{ data.title || '-' }}</span>
                                <Tag v-if="data.is_default" value="Default" severity="success" />
                            </div>
                        </template>
                    </Column>
                    <Column field="receiver_name" header="Receiver" style="min-width: 10rem" />
                    <Column field="receiver_phone" header="Phone" style="min-width: 9rem" />
                    <Column header="City" style="min-width: 10rem">
                        <template #body="{ data }">{{ data.province }} / {{ data.city }}</template>
                    </Column>
                    <Column field="postal_code" header="Postal Code" style="min-width: 9rem">
                        <template #body="{ data }">{{ data.postal_code || '-' }}</template>
                    </Column>
                    <Column field="address" header="Address" style="min-width: 16rem" />
                    <Column header="Actions" style="width: 11rem">
                        <template #body="{ data }">
                            <div class="flex justify-center gap-1">
                                <Button v-if="!data.is_default" icon="pi pi-star" rounded text severity="success" aria-label="Set default" @click="setDefault(data)" />
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="Edit" @click="openEditAddress(data)" />
                                <Button icon="pi pi-trash" rounded text severity="danger" aria-label="Delete" @click="deleteAddress(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="addressVisible" modal :header="editingAddress ? 'Edit Address' : 'Add Address'" :style="{ width: '56rem', maxWidth: '95vw' }">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block font-medium">Title</label>
                    <InputText v-model="addressForm.title" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Receiver Name</label>
                    <InputText v-model="addressForm.receiver_name" class="w-full" />
                    <small v-if="addressForm.errors.receiver_name" class="text-red-600">{{ addressForm.errors.receiver_name }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Receiver Phone</label>
                    <InputText v-model="addressForm.receiver_phone" class="w-full" />
                    <small v-if="addressForm.errors.receiver_phone" class="text-red-600">{{ addressForm.errors.receiver_phone }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Province</label>
                    <InputText v-model="addressForm.province" class="w-full" />
                    <small v-if="addressForm.errors.province" class="text-red-600">{{ addressForm.errors.province }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">City</label>
                    <InputText v-model="addressForm.city" class="w-full" />
                    <small v-if="addressForm.errors.city" class="text-red-600">{{ addressForm.errors.city }}</small>
                </div>
                <div>
                    <label class="mb-2 block font-medium">Postal Code</label>
                    <InputText v-model="addressForm.postal_code" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Plaque</label>
                    <InputText v-model="addressForm.plaque" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block font-medium">Unit</label>
                    <InputText v-model="addressForm.unit" class="w-full" />
                </div>
                <div class="flex items-center gap-3 pt-8">
                    <Checkbox v-model="addressForm.is_default" binary />
                    <span>Default address</span>
                </div>
                <div class="md:col-span-3">
                    <label class="mb-2 block font-medium">Address</label>
                    <Textarea v-model="addressForm.address" rows="4" class="w-full" />
                    <small v-if="addressForm.errors.address" class="text-red-600">{{ addressForm.errors.address }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="addressVisible = false" />
                <Button label="Save Address" icon="pi pi-check" :loading="addressForm.processing" @click="saveAddress" />
            </template>
        </Dialog>
    </AppLayout>
</template>
