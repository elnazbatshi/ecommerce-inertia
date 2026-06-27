<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    vehicleTypes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const rows = ref(Number(props.filters.rows ?? props.vehicleTypes.per_page ?? 15));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/vehicle-types', {
        search: search.value || undefined,
        rows: rows.value,
        ...extra,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const toggleStatus = (type) => router.patch(`/admin/vehicle-types/${type.id}/toggle-status`, {}, { preserveScroll: true });

const destroyType = (type) => {
    confirm.require({
        message: `نوع «${type.name}» حذف شود؟`,
        header: 'حذف نوع وسیله',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/vehicle-types/${type.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="نوع وسیله" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="نوع وسیله" :breadcrumb="[{ label: 'نوع وسیله' }]">
            <template #pageAction>
                <Link href="/admin/vehicle-types/create">
                    <Button label="نوع جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="vehicleTypes.data"
                dataKey="id"
                lazy
                paginator
                :first="(vehicleTypes.current_page - 1) * vehicleTypes.per_page"
                :rows="vehicleTypes.per_page"
                :totalRecords="vehicleTypes.total"
                :rowsPerPageOptions="[10, 15, 30]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <IconField class="max-w-md">
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="search" class="w-full" placeholder="جستجوی نوع وسیله" />
                    </IconField>
                </template>

                <template #empty>نوع وسیله‌ای یافت نشد.</template>

                <Column field="name" header="نام" sortable style="min-width: 12rem" />
                <Column field="slug" header="Slug" sortable style="min-width: 10rem" />
                <Column field="brands_count" header="برندها" style="width: 8rem" />
                <Column field="sort_order" header="ترتیب" sortable style="width: 7rem" />
                <Column field="is_active" header="وضعیت" style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 11rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Button :icon="data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" rounded text severity="secondary" @click="toggleStatus(data)" />
                            <Link :href="`/admin/vehicle-types/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyType(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
