<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    sections: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const rows = ref(Number(props.filters.rows ?? props.sections.per_page ?? 15));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/banner-sections', {
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

const destroySection = (section) => {
    confirm.require({
        message: `بخش «${section.title}» حذف شود؟`,
        header: 'حذف بخش بنر',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/banner-sections/${section.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="مدیریت بنرها" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="بخش‌های بنر" :breadcrumb="[{ label: 'مدیریت سایت' }, { label: 'بخش‌های بنر' }]">
            <template #pageAction>
                <Link href="/admin/banner-sections/create">
                    <Button label="بخش جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="sections.data"
                dataKey="id"
                lazy
                paginator
                :first="(sections.current_page - 1) * sections.per_page"
                :rows="sections.per_page"
                :totalRecords="sections.total"
                :rowsPerPageOptions="[10, 15, 30]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <IconField class="max-w-md">
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="search" class="w-full" placeholder="جستجوی بخش بنر" />
                    </IconField>
                </template>
                <template #empty>بخش بنری پیدا نشد.</template>

                <Column field="title" header="عنوان" style="min-width: 13rem" />
                <Column field="key" header="Key" style="min-width: 12rem" />
                <Column field="placement" header="Placement" style="width: 10rem" />
                <Column field="layout" header="Layout" style="width: 11rem" />
                <Column field="sort_order" header="ترتیب" style="width: 7rem" />
                <Column field="banners_count" header="بنرها" style="width: 7rem" />
                <Column field="is_active" header="وضعیت" style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 14rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/banner-sections/${data.id}/banners`"><Button icon="pi pi-images" rounded text severity="info" /></Link>
                            <Link :href="`/admin/banner-sections/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroySection(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
