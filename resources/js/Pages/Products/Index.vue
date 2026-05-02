<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ? Number(props.filters.category_id) : null);
const brandId = ref(props.filters.brand_id ? Number(props.filters.brand_id) : null);
const status = ref(props.filters.status ?? null);
const type = ref(props.filters.type ?? null);
const rows = ref(Number(props.filters.rows ?? props.products.per_page ?? 10));
const sortField = ref(props.filters.sortField ?? 'id');
const sortOrder = ref(props.filters.sortOrder === 'asc' ? 1 : -1);
const timeout = ref();

const categoryOptions = computed(() => [{ id: null, name: 'همه دسته‌بندی‌ها' }, ...props.categories]);
const brandOptions = computed(() => [{ id: null, name: 'همه برندها' }, ...props.brands]);
const statusOptions = [
    { label: 'همه وضعیت‌ها', value: null },
    { label: 'فعال', value: 'active' },
    { label: 'غیرفعال', value: 'inactive' },
    { label: 'پیش‌نویس', value: 'draft' }
];
const typeOptions = [
    { label: 'همه نوع‌ها', value: null },
    { label: 'ساده', value: 'simple' },
    { label: 'متغیر', value: 'variable' }
];

const labels = {
    active: { value: 'فعال', severity: 'success' },
    inactive: { value: 'غیرفعال', severity: 'danger' },
    draft: { value: 'پیش‌نویس', severity: 'warn' },
    simple: { value: 'ساده', severity: 'info' },
    variable: { value: 'متغیر', severity: 'secondary' }
};

const load = (extra = {}) => {
    router.get('/products', {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        brand_id: brandId.value || undefined,
        status: status.value || undefined,
        type: type.value || undefined,
        rows: rows.value,
        sortField: sortField.value,
        sortOrder: sortOrder.value === 1 ? 'asc' : 'desc',
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([categoryId, brandId, status, type], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const onSort = (event) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    load({ page: 1 });
};

const destroyProduct = (product) => {
    confirm.require({
        message: `محصول «${product.name}» حذف شود؟`,
        header: 'حذف محصول',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/products/${product.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="محصولات" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت محصولات" :breadcrumb="[{ label: 'محصولات' }]">
            <template #pageAction>
                <Link href="/products/create">
                    <Button label="محصول جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="products.data"
                dataKey="id"
                lazy
                paginator
                :first="(products.current_page - 1) * products.per_page"
                :rows="products.per_page"
                :totalRecords="products.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
                @sort="onSort"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="نام، SKU یا بارکد" />
                        </IconField>
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="categoryId" :options="categoryOptions" optionLabel="name" optionValue="id" class="w-full" />
                        <Select v-model="brandId" :options="brandOptions" optionLabel="name" optionValue="id" class="w-full" />
                        <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>محصولی پیدا نشد.</template>

                <Column header="تصویر" style="width: 6rem">
                    <template #body="{ data }">
                        <img v-if="data.main_image_url" :src="data.main_image_url" class="h-14 w-14 rounded-md object-cover" :alt="data.name" />
                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-md bg-surface-100 text-surface-400">
                            <i class="pi pi-image" />
                        </div>
                    </template>
                </Column>
                <Column field="name" header="نام محصول" sortable style="min-width: 14rem" />
                <Column field="sku" header="SKU" sortable style="min-width: 9rem" />
                <Column header="کلمات کلیدی" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <Tag
                                v-for="keyword in (data.meta_keywords ?? [])"
                                :key="keyword"
                                :value="keyword"
                                severity="info"
                            />
                            <span v-if="!data.meta_keywords?.length" class="text-surface-500">-</span>
                        </div>
                    </template>
                </Column>
                <Column header="دسته‌بندی" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.category?.name ?? '-' }}</template>
                </Column>
                <Column header="برند" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.brand?.name ?? '-' }}</template>
                </Column>
                <Column field="price" header="قیمت" sortable style="min-width: 9rem">
                    <template #body="{ data }">{{ Number(data.price).toLocaleString('fa-IR') }} {{ data.currency }}</template>
                </Column>
                <Column header="تخفیف" style="min-width: 8rem">
                    <template #body="{ data }">
                        <span v-if="data.discount_price">{{ Number(data.discount_price).toLocaleString('fa-IR') }}</span>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column field="status" header="وضعیت" sortable style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="labels[data.status]?.value" :severity="labels[data.status]?.severity" />
                    </template>
                </Column>
                <Column field="type" header="نوع" sortable style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="labels[data.type]?.value" :severity="labels[data.type]?.severity" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/products/${data.slug}`">
                                <Button icon="pi pi-eye" rounded text severity="info" aria-label="مشاهده" />
                            </Link>
                            <Link :href="`/products/${data.slug}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="destroyProduct(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
