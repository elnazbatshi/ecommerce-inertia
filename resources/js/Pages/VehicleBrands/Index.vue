<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    typeOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? null);
const isActive = ref(props.filters.is_active === '' ? '' : (props.filters.is_active ?? ''));
const rows = ref(Number(props.filters.rows ?? props.brands.per_page ?? 15));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/vehicle-brands', {
        search: search.value || undefined,
        type: type.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
        rows: rows.value,
        ...extra
    }, { preserveState: true, replace: true });
};

watch(type, () => load({ page: 1 }));
watch(isActive, () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const destroyBrand = (brand) => {
    confirm.require({
        message: `برند «${brand.name}» حذف شود؟`,
        header: 'حذف برند خودرو',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/vehicle-brands/${brand.id}`, { preserveScroll: true })
    });
};

const toggleStatus = (brand) => {
    router.patch(`/admin/vehicle-brands/${brand.id}/toggle-status`, {}, { preserveScroll: true });
};

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: '' },
    { label: 'فعال', value: true },
    { label: 'غیرفعال', value: false }
];
</script>

<template>
    <Head title="برند خودرو" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="برند خودرو" :breadcrumb="[{ label: 'برند خودرو' }]">
            <template #pageAction>
                <Link href="/admin/vehicle-brands/create">
                    <Button label="برند جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="brands.data"
                dataKey="id"
                lazy
                paginator
                :first="(brands.current_page - 1) * brands.per_page"
                :rows="brands.per_page"
                :totalRecords="brands.total"
                :rowsPerPageOptions="[10, 15, 30, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو نام، نامک، کشور" />
                        </IconField>
                        <Dropdown v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="نوع برند" showClear />
                        <Dropdown v-model="isActive" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>موردی یافت نشد.</template>

                <Column header="لوگو" style="width: 6rem">
                    <template #body="{ data }">
                        <img v-if="data.logo_media?.url" :src="data.logo_media.url" class="h-12 w-12 rounded object-cover" :alt="data.name" />
                        <i v-else class="pi pi-image text-surface-400" />
                    </template>
                </Column>
                <Column field="name" header="نام" sortable />
                <Column field="type" header="نوع" sortable />
                <Column field="country" header="کشور" sortable />
                <Column field="vehicles_count" header="تعداد خودرو" sortable style="width: 8rem" />
                <Column header="وضعیت" style="width: 7rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-power-off" rounded text :severity="data.is_active ? 'warning' : 'success'" @click="toggleStatus(data)" />
                            <Link :href="`/admin/vehicle-brands/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyBrand(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

