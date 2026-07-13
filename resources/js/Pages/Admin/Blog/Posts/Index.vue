<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const categoryId = ref(props.filters.blog_category_id ? Number(props.filters.blog_category_id) : null);
const isFeatured = ref(props.filters.is_featured ?? null);
const rows = ref(Number(props.filters.rows ?? props.posts.per_page ?? 20));
const sortField = ref(props.filters.sort_field ?? 'published_at');
const sortOrder = ref(props.filters.sort_order === 'asc' ? 1 : -1);
const timeout = ref();
const skipFilterWatch = ref(false);

const statusOptions = [
    { label: 'همه وضعیت‌ها', value: null },
    { label: 'پیش‌نویس', value: 'draft' },
    { label: 'منتشرشده', value: 'published' },
    { label: 'آرشیوشده', value: 'archived' },
];
const featuredOptions = [
    { label: 'همه مقالات', value: null },
    { label: 'ویژه', value: '1' },
    { label: 'غیرویژه', value: '0' },
];
const categoryOptions = computed(() => [{ id: null, name: 'همه دسته‌بندی‌ها' }, ...props.categories]);
const labels = {
    draft: { value: 'پیش‌نویس', severity: 'secondary' },
    published: { value: 'منتشرشده', severity: 'success' },
    archived: { value: 'آرشیوشده', severity: 'warn' },
};
const currentResultCount = computed(() => props.posts.total ? ((props.posts.to ?? 0) - (props.posts.from ?? 0)) + 1 : 0);
const hasActiveFilters = computed(() => Boolean(search.value || status.value || categoryId.value || isFeatured.value !== null));

const load = (extra = {}) => {
    router.get('/admin/blog-posts', {
        search: search.value || undefined,
        status: status.value || undefined,
        blog_category_id: categoryId.value || undefined,
        is_featured: isFeatured.value ?? undefined,
        sort_field: sortField.value,
        sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
        rows: rows.value,
        ...extra,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([status, categoryId, isFeatured], () => {
    if (!skipFilterWatch.value) load({ page: 1 });
});

watch(search, () => {
    if (skipFilterWatch.value) return;
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const clearFilters = () => {
    skipFilterWatch.value = true;
    search.value = '';
    status.value = null;
    categoryId.value = null;
    isFeatured.value = null;
    skipFilterWatch.value = false;
    clearTimeout(timeout.value);
    load({ page: 1 });
};

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    load({ page: 1 });
};

const destroyPost = (post) => {
    confirm.require({
        message: `حذف "${post.title}"?`,
        header: 'حذف مقاله',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/blog-posts/${post.slug}`, { preserveScroll: true }),
    });
};

const toggleFeatured = (post, value) => {
    const previousValue = post.is_featured;
    post.is_featured = value;

    router.patch(`/admin/blog-posts/${post.slug}/featured`, { is_featured: value }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            post.is_featured = previousValue;
        },
        onSuccess: () => router.reload({ only: ['posts'], preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="مقالات بلاگ" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مقالات بلاگ" :breadcrumb="[{ label: 'بلاگ' }, { label: 'مقالات' }]">
            <template #pageAction>
                <Link href="/admin/blog-posts/create">
                    <Button label="مقاله جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <Card class="mb-4 w-full overflow-hidden rounded-2xl border border-surface-200 bg-surface-0 shadow-sm">
            <template #content>
                <Toolbar class="border-0 bg-transparent p-0">
                    <template #start>
                        <div class="flex flex-col gap-1 lg:flex-row lg:items-center lg:gap-3">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-filter text-primary"></i>
                                <h2 class="m-0 text-lg font-bold text-surface-900">فیلتر مقالات</h2>
                            </div>
                            <span class="text-sm text-surface-500">
                                نمایش {{ currentResultCount }} از {{ posts.total ?? 0 }} مقاله
                            </span>
                        </div>
                    </template>
                    <template #end>
                        <Button v-if="hasActiveFilters" label="پاک کردن فیلترها" icon="pi pi-filter-slash" severity="secondary" outlined @click="clearFilters" />
                    </template>
                </Toolbar>

                <Divider class="my-3" />

                <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                    <div class="flex w-full flex-col gap-1.5 md:basis-[28rem] md:grow">
                        <label class="text-xs font-semibold text-surface-600">جستجو</label>
                        <IconField class="w-full">
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="h-10 w-full" placeholder="عنوان، اسلاگ یا خلاصه" />
                        </IconField>
                    </div>
                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">وضعیت</label>
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="h-10 w-full" />
                    </div>
                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">دسته‌بندی</label>
                        <Select v-model="categoryId" :options="categoryOptions" optionLabel="name" optionValue="id" class="h-10 w-full" />
                    </div>
                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">ویژه</label>
                        <Select v-model="isFeatured" :options="featuredOptions" optionLabel="label" optionValue="value" class="h-10 w-full" />
                    </div>
                </div>
            </template>
        </Card>

        <div class="card">
            <DataTable
                :value="posts.data"
                dataKey="id"
                lazy
                paginator
                :first="(posts.current_page - 1) * posts.per_page"
                :rows="posts.per_page"
                :totalRecords="posts.total"
                :rowsPerPageOptions="[10, 20, 30, 50, 100]"
                showGridlines
                @page="onPage"
                @sort="onSort"
            >
                <template #empty>مقاله‌ای پیدا نشد.</template>
                <Column header="تصویر" style="width: 6rem">
                    <template #body="{ data }">
                        <img v-if="data.featured_image_url" :src="data.featured_image_url" class="h-14 w-14 rounded-md object-cover" :alt="data.featured_image_alt || data.title" />
                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-md bg-surface-100 text-surface-400">
                            <i class="pi pi-image" />
                        </div>
                    </template>
                </Column>
                <Column field="title" header="عنوان" sortable style="min-width: 16rem" />
                <Column header="دسته‌بندی" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.category?.name ?? '-' }}</template>
                </Column>
                <Column header="نویسنده" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.author?.name ?? '-' }}</template>
                </Column>
                <Column field="status" header="وضعیت" sortable style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="labels[data.status]?.value ?? data.status" :severity="labels[data.status]?.severity ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="is_featured" header="ویژه" sortable style="width: 8rem">
                    <template #body="{ data }">
                        <ToggleSwitch :model-value="Boolean(data.is_featured)" @update:model-value="toggleFeatured(data, $event)" />
                    </template>
                </Column>
                <Column field="views" header="بازدید" sortable style="width: 8rem" />
                <Column field="published_at" header="منتشرشده" sortable style="min-width: 11rem" />
                <Column field="created_at" header="ایجاد" sortable style="min-width: 11rem" />
                <Column header="عملیات" style="width: 8rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/blog-posts/${data.slug}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="destroyPost(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
