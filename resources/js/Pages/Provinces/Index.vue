<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    provinces: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const isActive = ref(props.filters.is_active === '' ? '' : (props.filters.is_active ?? ''));
const rows = ref(Number(props.filters.rows ?? props.provinces.per_page ?? 15));
const timeout = ref();

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: '' },
    { label: 'فعال', value: true },
    { label: 'غیرفعال', value: false },
];

const load = (extra = {}) => {
    router.get('/admin/provinces', {
        search: search.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
        rows: rows.value,
        ...extra,
    }, { preserveState: true, replace: true });
};

watch(isActive, () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const toggleStatus = (row) => router.patch(`/admin/provinces/${row.id}/toggle-status`, {}, { preserveScroll: true });

const destroyItem = (row) => {
    confirm.require({
        message: `استان «${row.name}» حذف شود؟`,
        header: 'حذف استان',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/provinces/${row.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="استان‌ها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="استان‌ها" :breadcrumb="[{ label: 'استان‌ها' }]">
            <template #pageAction>
                <Link href="/admin/provinces/create"><Button label="استان جدید" icon="pi pi-plus" /></Link>
            </template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="provinces.data" dataKey="id" lazy paginator :first="(provinces.current_page - 1) * provinces.per_page"
                :rows="provinces.per_page" :totalRecords="provinces.total" :rowsPerPageOptions="[10,15,30,50]" @page="onPage">
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو نام، اسلاگ، کد" />
                        </IconField>
                        <Dropdown v-model="isActive" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>
                <Column field="name" header="نام" />
                <Column field="slug" header="اسلاگ" />
                <Column field="code" header="کد" />
                <Column field="cities_count" header="تعداد شهر" />
                <Column field="sort_order" header="ترتیب" />
                <Column header="وضعیت">
                    <template #body="{ data }"><Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" /></template>
                </Column>
                <Column header="عملیات">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-power-off" rounded text :severity="data.is_active ? 'warning' : 'success'" @click="toggleStatus(data)" />
                            <Link :href="`/admin/provinces/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>

