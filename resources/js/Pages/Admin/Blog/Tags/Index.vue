<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

const props = defineProps({
    tags: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const rows = ref(Number(props.filters.rows ?? props.tags.per_page ?? 20));
const timeout = ref();

const load = (extra = {}) => {
    router.get('/admin/blog-tags', {
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

const destroyTag = (tag) => {
    confirm.require({
        message: `حذف "${tag.name}"?`,
        header: 'حذف تگ',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/blog-tags/${tag.slug}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="تگ‌های بلاگ" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="تگ‌های بلاگ" :breadcrumb="[{ label: 'بلاگ' }, { label: 'تگ‌ها' }]">
            <template #pageAction>
                <Link href="/admin/blog-tags/create">
                    <Button label="تگ جدید" icon="pi pi-plus" />
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
                :value="tags.data"
                dataKey="id"
                lazy
                paginator
                :first="(tags.current_page - 1) * tags.per_page"
                :rows="tags.per_page"
                :totalRecords="tags.total"
                :rowsPerPageOptions="[10, 20, 30, 50, 100]"
                showGridlines
                @page="onPage"
            >
                <template #empty>تگی پیدا نشد.</template>
                <Column field="name" header="نام" sortable />
                <Column field="slug" header="اسلاگ" />
                <Column field="posts_count" header="تعداد مقالات" style="width: 10rem">
                    <template #body="{ data }">{{ data.posts_count ?? data.posts_count === 0 ? data.posts_count : '-' }}</template>
                </Column>
                <Column header="عملیات" style="width: 8rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/blog-tags/${data.slug}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="destroyTag(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
