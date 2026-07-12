<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const rows = ref(Number(props.filters.rows ?? props.categories.per_page ?? 20));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/blog-categories', {
        search: search.value || undefined,
        rows: rows.value,
        ...extra,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const clearFilters = () => {
    search.value = '';
    clearTimeout(timeout.value);
    load({ page: 1 });
};

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const destroyCategory = (category) => {
    confirm.require({
        message: `حذف "${category.name}"?`,
        header: 'حذف دسته‌بندی',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/blog-categories/${category.slug}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="دسته‌بندی‌های بلاگ" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="دسته‌بندی‌های بلاگ" :breadcrumb="[{ label: 'بلاگ' }, { label: 'دسته‌بندی‌ها' }]">
            <template #pageAction>
                <Link href="/admin/blog-categories/create">
                    <Button label="دسته‌بندی جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <Card class="mb-4">
            <template #content>
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="flex w-full flex-col gap-1.5 md:max-w-xl">
                        <label class="text-xs font-semibold text-surface-600">جستجو</label>
                        <IconField class="w-full">
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="نام یا اسلاگ" />
                        </IconField>
                    </div>
                    <Button v-if="search" label="پاک کردن فیلترها" icon="pi pi-filter-slash" severity="secondary" outlined @click="clearFilters" />
                </div>
            </template>
        </Card>

        <div class="card">
            <DataTable
                :value="categories.data"
                dataKey="id"
                lazy
                paginator
                :first="(categories.current_page - 1) * categories.per_page"
                :rows="categories.per_page"
                :totalRecords="categories.total"
                :rowsPerPageOptions="[10, 20, 30, 50, 100]"
                showGridlines
                @page="onPage"
            >
                <template #empty>دسته‌بندی‌ای پیدا نشد.</template>
                <Column field="name" header="نام" sortable />
                <Column field="slug" header="اسلاگ" />
                <Column header="والد">
                    <template #body="{ data }">{{ data.parent?.name ?? '-' }}</template>
                </Column>
                <Column field="is_active" header="وضعیت" style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column field="sort_order" header="ترتیب نمایش" style="width: 9rem" />
                <Column header="عملیات" style="width: 8rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/blog-categories/${data.slug}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="destroyCategory(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
