<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    vehicles: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    typeOptions: { type: Array, default: () => [] },
    brandOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? null);
const vehicleBrandId = ref(props.filters.vehicle_brand_id ? Number(props.filters.vehicle_brand_id) : null);
const isActive = ref(props.filters.is_active === '' ? '' : (props.filters.is_active ?? ''));
const rows = ref(Number(props.filters.rows ?? props.vehicles.per_page ?? 15));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/vehicles', {
        search: search.value || undefined,
        type: type.value || undefined,
        vehicle_brand_id: vehicleBrandId.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
        rows: rows.value,
        ...extra
    }, { preserveState: true, replace: true });
};

watch([type, vehicleBrandId, isActive], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const toggleStatus = (vehicle) => {
    router.patch(`/admin/vehicles/${vehicle.id}/toggle-status`, {}, { preserveScroll: true });
};

const destroyVehicle = (vehicle) => {
    confirm.require({
        message: `خودرو «${vehicle.name}» حذف شود؟`,
        header: 'حذف خودرو',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/vehicles/${vehicle.id}`, { preserveScroll: true })
    });
};

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: '' },
    { label: 'فعال', value: true },
    { label: 'غیرفعال', value: false }
];
</script>

<template>
    <Head title="خودروها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت خودروها" :breadcrumb="[{ label: 'خودروها' }]">
            <template #pageAction>
                <Link href="/admin/vehicles/create">
                    <Button label="خودروی جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="vehicles.data"
                dataKey="id"
                lazy
                paginator
                :first="(vehicles.current_page - 1) * vehicles.per_page"
                :rows="vehicles.per_page"
                :totalRecords="vehicles.total"
                :rowsPerPageOptions="[10, 15, 30, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو در نام، برند، نامک" />
                        </IconField>
                        <Dropdown v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="نوع" showClear />
                        <Dropdown
                            v-model="vehicleBrandId"
                            :options="brandOptions"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            showClear
                            class="w-full"
                            placeholder="برند"
                        />
                        <Dropdown v-model="isActive" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>موردی یافت نشد.</template>

                <Column header="تصویر" style="width: 6rem">
                    <template #body="{ data }">
                        <img v-if="data.image_media?.url" :src="data.image_media.url" class="h-12 w-12 rounded object-cover" :alt="data.name" />
                        <i v-else class="pi pi-image text-surface-400" />
                    </template>
                </Column>
                <Column header="برند" sortable>
                    <template #body="{ data }">{{ data.brand?.name || '-' }}</template>
                </Column>
                <Column field="name" header="نام" sortable />
                <Column field="engine" header="موتور" sortable />
                <Column header="سال">
                    <template #body="{ data }">{{ data.year_from || '-' }} - {{ data.year_to || 'اکنون' }}</template>
                </Column>
                <Column field="products_count" header="تعداد محصول" sortable style="width: 8rem" />
                <Column header="وضعیت" style="width: 7rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-power-off" rounded text :severity="data.is_active ? 'warning' : 'success'" @click="toggleStatus(data)" />
                            <Link :href="`/admin/vehicles/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyVehicle(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

