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
    brands: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ? Number(props.filters.category_id) : null);
const brandId = ref(props.filters.brand_id ? Number(props.filters.brand_id) : null);
const status = ref(props.filters.status ?? null);
const type = ref(props.filters.type ?? null);
const isFeatured = ref(props.filters.is_featured ?? null);
const rows = ref(Number(props.filters.rows ?? props.products.per_page ?? 10));
const sortField = ref(props.filters.sortField ?? 'id');
const sortOrder = ref(props.filters.sortOrder === 'asc' ? 1 : -1);
const timeout = ref();
const skipFilterWatch = ref(false);

const categoryOptions = computed(() => [{ id: null, name: 'همه دسته بندی ها' }, ...props.categories]);
const brandOptions = computed(() => [{ id: null, name: 'همه برندها' }, ...props.brands]);
const statusOptions = [
    { label: 'همه وضعیت ها', value: null },
    { label: 'فعال', value: 'active' },
    { label: 'غیرفعال', value: 'inactive' },
    { label: 'پیش نویس', value: 'draft' },
];
const typeOptions = [
    { label: 'همه نوع ها', value: null },
    { label: 'ساده', value: 'simple' },
    { label: 'متغیر', value: 'variable' },
];

const featuredOptions = [
    { label: 'همه محصولات', value: null },
    { label: 'محصولات منتخب', value: '1' },
    { label: 'محصولات غیرمنتخب', value: '0' },
];

const labels = {
    active: { value: 'فعال', severity: 'success' },
    inactive: { value: 'غیرفعال', severity: 'danger' },
    draft: { value: 'پیش نویس', severity: 'warn' },
    simple: { value: 'ساده', severity: 'info' },
    variable: { value: 'متغیر', severity: 'secondary' },
};

const selectedCategory = computed(() => props.categories.find((item) => item.id === categoryId.value) ?? null);
const selectedBrand = computed(() => props.brands.find((item) => item.id === brandId.value) ?? null);
const selectedStatus = computed(() => statusOptions.find((item) => item.value === status.value) ?? null);
const selectedType = computed(() => typeOptions.find((item) => item.value === type.value) ?? null);
const selectedFeatured = computed(() => featuredOptions.find((item) => item.value === isFeatured.value) ?? null);
const currentResultCount = computed(() => {
    if (!props.products.total) {
        return 0;
    }

    return ((props.products.to ?? 0) - (props.products.from ?? 0)) + 1;
});

const activeFilters = computed(() => [
    search.value ? {
        key: 'search',
        label: 'جستجو',
        value: search.value,
        clear: () => {
            search.value = '';
            load({ page: 1 });
        },
    } : null,
    status.value ? {
        key: 'status',
        label: 'وضعیت',
        value: selectedStatus.value?.label ?? status.value,
        clear: () => {
            status.value = null;
        },
    } : null,
    categoryId.value ? {
        key: 'category',
        label: 'دسته بندی',
        value: selectedCategory.value?.name ?? categoryId.value,
        clear: () => {
            categoryId.value = null;
        },
    } : null,
    brandId.value ? {
        key: 'brand',
        label: 'برند',
        value: selectedBrand.value?.name ?? brandId.value,
        clear: () => {
            brandId.value = null;
        },
    } : null,
    type.value ? {
        key: 'type',
        label: 'نوع',
        value: selectedType.value?.label ?? type.value,
        clear: () => {
            type.value = null;
        },
    } : null,
    isFeatured.value !== null ? {
        key: 'is_featured',
        label: 'محصول منتخب',
        value: selectedFeatured.value?.label ?? isFeatured.value,
        clear: () => {
            isFeatured.value = null;
        },
    } : null,
].filter(Boolean));

const hasActiveFilters = computed(() => activeFilters.value.length > 0);

const load = (extra = {}) => {
    router.get('/admin/products', {
        search: search.value || undefined,
        category_id: categoryId.value || undefined,
        brand_id: brandId.value || undefined,
        status: status.value || undefined,
        type: type.value || undefined,
        is_featured: isFeatured.value ?? undefined,
        rows: rows.value,
        sortField: sortField.value,
        sortOrder: sortOrder.value === 1 ? 'asc' : 'desc',
        ...extra,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([categoryId, brandId, status, type, isFeatured], () => {
    if (!skipFilterWatch.value) {
        load({ page: 1 });
    }
});

watch(search, () => {
    if (skipFilterWatch.value) {
        return;
    }

    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const clearFilters = () => {
    skipFilterWatch.value = true;
    search.value = '';
    categoryId.value = null;
    brandId.value = null;
    status.value = null;
    type.value = null;
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

const destroyProduct = (product) => {
    confirm.require({
        message: `محصول «${product.name}» حذف شود؟`,
        header: 'حذف محصول',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/products/${product.slug}`, { preserveScroll: true }),
    });
};

const toggleFeatured = (product, value) => {
    const previousValue = product.is_featured;
    product.is_featured = value;

    router.patch(`/admin/products/${product.slug}/featured`, {
        is_featured: value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            product.is_featured = previousValue;
        },
    });
};
</script>

<template>
    <Head title="محصولات" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت محصولات" :breadcrumb="[{ label: 'محصولات' }]">
            <template #pageAction>
                <Link href="/admin/products/create">
                    <Button label="محصول جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <Card class="mb-4 w-full overflow-hidden rounded-2xl border border-surface-200 bg-surface-0 shadow-sm">
            <template #content>
                <Toolbar class="border-0 bg-transparent p-0 [&_.p-toolbar-group-end]:w-full [&_.p-toolbar-group-start]:w-full lg:[&_.p-toolbar-group-end]:w-auto lg:[&_.p-toolbar-group-start]:w-auto">
                    <template #start>
                        <div class="flex w-full flex-col gap-1 lg:flex-row lg:items-center lg:gap-3">
                            <div class="flex items-center gap-2 leading-none">
                                <i class="pi pi-filter text-primary"></i>
                                <h2 class="m-0 text-lg font-bold text-surface-900">فیلتر محصولات</h2>
                            </div>
                            <span class="text-sm text-surface-500 lg:border-r lg:border-surface-200 lg:pr-3">
                                نمایش {{ Number(currentResultCount).toLocaleString('fa-IR') }} محصول از {{ Number(products.total ?? 0).toLocaleString('fa-IR') }} محصول
                            </span>
                        </div>
                    </template>

                    <template #end>
                        <Button
                            v-if="hasActiveFilters"
                            label="پاک کردن فیلترها"
                            icon="pi pi-filter-slash"
                            severity="secondary"
                            outlined
                            class="mt-2 h-10 w-full justify-center lg:mt-0 lg:w-auto"
                            @click="clearFilters"
                        />
                    </template>
                </Toolbar>

                <Divider class="my-3" />

                <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                    <div class="flex w-full flex-col gap-1.5 md:basis-[28rem] md:grow">
                        <label class="text-xs font-semibold text-surface-600">جستجو</label>
                        <IconField class="w-full">
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText
                                v-model="search"
                                class="h-10 w-full transition-colors hover:border-primary-300 focus:border-primary"
                                placeholder="نام، SKU یا بارکد"
                            />
                        </IconField>
                    </div>

                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">وضعیت</label>
                        <Select
                            v-model="status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="h-10 w-full transition-colors hover:border-primary-300 focus-within:border-primary"
                        />
                    </div>

                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">دسته بندی</label>
                        <Select
                            v-model="categoryId"
                            :options="categoryOptions"
                            optionLabel="name"
                            optionValue="id"
                            class="h-10 w-full transition-colors hover:border-primary-300 focus-within:border-primary"
                        />
                    </div>

                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">برند</label>
                        <Select
                            v-model="brandId"
                            :options="brandOptions"
                            optionLabel="name"
                            optionValue="id"
                            class="h-10 w-full transition-colors hover:border-primary-300 focus-within:border-primary"
                        />
                    </div>

                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">نوع محصول</label>
                        <Select
                            v-model="type"
                            :options="typeOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="h-10 w-full transition-colors hover:border-primary-300 focus-within:border-primary"
                        />
                    </div>

                    <div class="flex w-full flex-col gap-1.5 sm:w-[calc(50%-0.375rem)] md:w-56">
                        <label class="text-xs font-semibold text-surface-600">محصول منتخب</label>
                        <Select
                            v-model="isFeatured"
                            :options="featuredOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="h-10 w-full transition-colors hover:border-primary-300 focus-within:border-primary"
                        />
                    </div>
                </div>

                <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap gap-2 border-t border-surface-100 pt-3">
                    <Chip
                        v-for="filter in activeFilters"
                        :key="filter.key"
                        removable
                        class="bg-primary-50 text-primary-700"
                        @remove="filter.clear"
                    >
                        <span class="font-semibold">{{ filter.label }}:</span>
                        <span>{{ filter.value }}</span>
                    </Chip>
                </div>
            </template>
        </Card>

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
                <Column header="دسته بندی" style="min-width: 10rem">
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
                <Column field="is_featured" header="منتخب" sortable style="width: 8rem">
                    <template #body="{ data }">
                        <ToggleSwitch
                            :model-value="Boolean(data.is_featured)"
                            @update:model-value="toggleFeatured(data, $event)"
                        />
                    </template>
                </Column>
                <Column field="stock" header="موجودی" sortable style="width: 8rem">
                    <template #body="{ data }">
                        <span v-if="data.type === 'simple'" class="font-bold">
                            {{ Number(data.stock ?? 0).toLocaleString('fa-IR') }}
                        </span>
                        <Tag v-else value="تنوع ها" severity="secondary" />
                    </template>
                </Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/products/${data.slug}`">
                                <Button icon="pi pi-eye" rounded text severity="info" aria-label="مشاهده" />
                            </Link>
                            <Link :href="`/admin/products/${data.slug}/edit`">
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
