<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    transactionRows: { type: Object, default: null },
    productSummaryRows: { type: Object, default: null },
    chartData: { type: Object, default: null },
    statistics: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    activeView: { type: String, default: 'transactions' },
    productOptions: { type: Array, default: () => [] },
    orderStatusOptions: { type: Array, default: () => [] },
    paymentMethodOptions: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const tabs = [
    { key: 'transactions', label: 'تراکنش‌ها', icon: 'pi pi-list' },
    { key: 'products', label: 'خلاصه محصولات', icon: 'pi pi-box' },
    { key: 'chart', label: 'نمودار فروش', icon: 'pi pi-chart-line' },
];

const activeView = ref(props.activeView || 'transactions');
const search = ref(props.filters.search ?? '');
const orderNumber = ref(props.filters.order_number ?? '');
const transactionId = ref(props.filters.transaction_id ?? '');
const productQuery = ref(props.filters.product_query ?? '');
const productId = ref(props.filters.product_id ?? null);
const customerQuery = ref(props.filters.customer_query ?? '');
const paymentMethod = ref(props.filters.payment_method ?? null);
const orderStatus = ref(props.filters.order_status ?? null);
const quickRange = ref(props.filters.quick_range ?? null);
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const perPage = ref(Number(props.filters.per_page ?? 25));
const sortField = ref(props.filters.sort_field ?? null);
const productSortField = ref(props.filters.product_sort_field ?? 'net_sales');
const sortOrder = ref(props.filters.sort_order ?? 'desc');
const chartGrouping = ref(props.filters.chart_grouping ?? 'day');
const timeout = ref(null);
const suppressFilterWatch = ref(false);
const loading = ref(false);
const copied = ref(null);
const isFilterDialogOpen = ref(false);
const draftFilters = reactive({
    search: search.value,
    orderNumber: orderNumber.value,
    transactionId: transactionId.value,
    productQuery: productQuery.value,
    productId: productId.value,
    customerQuery: customerQuery.value,
    paymentMethod: paymentMethod.value,
    orderStatus: orderStatus.value,
    quickRange: quickRange.value,
    dateFrom: dateFrom.value,
    dateTo: dateTo.value,
    perPage: perPage.value,
});

const activeRows = computed(() => activeView.value === 'products' ? props.productSummaryRows : props.transactionRows);
const firstRow = computed(() => activeRows.value ? ((activeRows.value.current_page - 1) * activeRows.value.per_page) : 0);
const lastUpdated = computed(() => formatJalaliDateTime(new Date().toISOString()));
const hasActiveFilters = computed(() => Boolean(
    search.value || orderNumber.value || transactionId.value || productQuery.value || productId.value ||
    customerQuery.value || paymentMethod.value || orderStatus.value || quickRange.value || dateFrom.value || dateTo.value
));
const activeFiltersCount = computed(() => [
    search.value,
    orderNumber.value,
    transactionId.value,
    productQuery.value,
    productId.value,
    customerQuery.value,
    paymentMethod.value,
    orderStatus.value,
    quickRange.value,
    dateFrom.value,
    dateTo.value,
].filter((value) => value !== null && value !== undefined && value !== '').length);
const resultText = computed(() => {
    if (!activeRows.value) return 'داده‌های این تب بر اساس فیلترهای فعلی آماده است.';

    const from = activeRows.value.total ? firstRow.value + 1 : 0;
    const to = Math.min(firstRow.value + activeRows.value.per_page, activeRows.value.total);

    return `${from.toLocaleString('fa-IR')} تا ${to.toLocaleString('fa-IR')} از ${Number(activeRows.value.total || 0).toLocaleString('fa-IR')}`;
});

const stats = computed(() => [
    { label: 'فروش کل', value: money(props.statistics.total_sales), icon: 'pi pi-wallet', tone: 'bg-emerald-100 text-emerald-600' },
    { label: 'سفارش‌های قطعی', value: number(props.statistics.orders_count), icon: 'pi pi-shopping-bag', tone: 'bg-blue-100 text-blue-600' },
    { label: 'اقلام فروخته‌شده', value: number(props.statistics.items_sold), icon: 'pi pi-box', tone: 'bg-amber-100 text-amber-600' },
    { label: 'مجموع تخفیف', value: money(props.statistics.discount_total), icon: 'pi pi-percentage', tone: 'bg-rose-100 text-rose-600' },
    { label: 'میانگین سفارش', value: money(props.statistics.average_order_value), icon: 'pi pi-chart-bar', tone: 'bg-violet-100 text-violet-600' },
    { label: 'محصول متفاوت', value: number(props.statistics.products_count), icon: 'pi pi-tags', tone: 'bg-cyan-100 text-cyan-600' },
]);

const orderStatusFilters = computed(() => [
    { label: 'همه وضعیت‌های معتبر', value: null },
    ...props.orderStatusOptions.map((item) => ({ label: statusLabel(item.value), value: item.value })),
]);
const paymentMethodFilters = computed(() => [
    { label: 'همه روش‌های پرداخت', value: null },
    ...props.paymentMethodOptions.map((item) => ({ label: paymentMethodLabel(item.value), value: item.value })),
]);
const productFilters = computed(() => [{ id: null, label: 'همه محصولات' }, ...props.productOptions]);
const quickRangeOptions = [
    { label: 'همه زمان‌ها', value: 'all' },
    { label: 'امروز', value: 'today' },
    { label: '۷ روز اخیر', value: 'last_7_days' },
    { label: '۳۰ روز اخیر', value: 'last_30_days' },
    { label: 'ماه جاری', value: 'current_month' },
    { label: 'ماه قبل', value: 'previous_month' },
];
const chartGroupingOptions = [
    { label: 'روزانه', value: 'day' },
    { label: 'هفتگی', value: 'week' },
    { label: 'ماهانه', value: 'month' },
];

const timelineChart = computed(() => ({
    labels: props.chartData?.timeline?.map((item) => item.label) || [],
    datasets: [
        {
            label: 'فروش',
            data: props.chartData?.timeline?.map((item) => item.total_sales) || [],
            borderColor: '#d6a84f',
            backgroundColor: 'rgba(214, 168, 79, 0.2)',
            tension: 0.35,
            fill: true,
        },
    ],
}));
const topProductsChart = computed(() => ({
    labels: props.chartData?.top_products?.map((item) => item.label) || [],
    datasets: [
        {
            label: 'فروش خالص',
            data: props.chartData?.top_products?.map((item) => item.net_sales) || [],
            backgroundColor: '#111827',
        },
    ],
}));
const paymentChart = computed(() => ({
    labels: props.chartData?.payment_methods?.map((item) => paymentMethodLabel(item.method)) || [],
    datasets: [
        {
            label: 'فروش',
            data: props.chartData?.payment_methods?.map((item) => item.total_sales) || [],
            backgroundColor: ['#d6a84f', '#111827', '#2563eb', '#16a34a', '#dc2626'],
        },
    ],
}));
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'IRANSansWebFaNum' } } },
        tooltip: {
            callbacks: {
                label: (context) => `${context.dataset.label}: ${money(context.parsed.y ?? context.parsed)}`,
            },
        },
    },
    scales: {
        y: { ticks: { callback: (value) => Number(value).toLocaleString('fa-IR') } },
    },
};

function money(value) {
    return `${Number(value ?? 0).toLocaleString('fa-IR')} تومان`;
}

function number(value) {
    return Number(value ?? 0).toLocaleString('fa-IR');
}

function statusLabel(status) {
    return { pending: 'در انتظار بررسی', processing: 'در حال پردازش', shipped: 'ارسال شده', delivered: 'تحویل داده شده' }[status] ?? status;
}

function statusSeverity(status) {
    return { pending: 'warn', processing: 'info', shipped: 'help', delivered: 'success' }[status] ?? 'secondary';
}

function paymentMethodLabel(method) {
    return { online: 'آنلاین', card_to_card: 'کارت به کارت', cash: 'نقدی', wallet: 'کیف پول', unknown: 'نامشخص' }[method] ?? method;
}

function viewOnly(key = activeView.value) {
    if (key === 'products') return ['productSummaryRows', 'statistics', 'filters', 'activeView'];
    if (key === 'chart') return ['chartData', 'statistics', 'filters', 'activeView'];
    return ['transactionRows', 'statistics', 'filters', 'activeView'];
}

const requestPayload = (extra = {}) => ({
    active_view: activeView.value,
    search: search.value || undefined,
    order_number: orderNumber.value || undefined,
    transaction_id: transactionId.value || undefined,
    product_query: productQuery.value || undefined,
    product_id: productId.value || undefined,
    customer_query: customerQuery.value || undefined,
    payment_method: paymentMethod.value || undefined,
    order_status: orderStatus.value || undefined,
    payment_status: 'paid',
    quick_range: quickRange.value || undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    per_page: perPage.value,
    sort_field: sortField.value || undefined,
    product_sort_field: productSortField.value || undefined,
    sort_order: sortOrder.value || undefined,
    chart_grouping: chartGrouping.value,
    ...extra,
});

const load = (extra = {}, only = viewOnly()) => {
    loading.value = true;
    router.get('/admin/product-sales-ledger', requestPayload(extra), {
        only,
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const debouncedLoad = () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 400);
};

watch([paymentMethod, orderStatus, productId, perPage], () => {
    if (!suppressFilterWatch.value) load({ page: 1 });
});
watch([search, orderNumber, transactionId, productQuery, customerQuery], () => {
    if (!suppressFilterWatch.value) debouncedLoad();
});
watch([chartGrouping], () => {
    if (!suppressFilterWatch.value && activeView.value === 'chart') load({ page: 1 });
});

function changeQuickRange(value) {
    suppressFilterWatch.value = true;
    quickRange.value = value;
    if (value) {
        dateFrom.value = '';
        dateTo.value = '';
    }
    load({ page: 1, quick_range: value || undefined, date_from: undefined, date_to: undefined });
    setTimeout(() => (suppressFilterWatch.value = false), 0);
}

function syncDraftFilters() {
    draftFilters.search = search.value;
    draftFilters.orderNumber = orderNumber.value;
    draftFilters.transactionId = transactionId.value;
    draftFilters.productQuery = productQuery.value;
    draftFilters.productId = productId.value;
    draftFilters.customerQuery = customerQuery.value;
    draftFilters.paymentMethod = paymentMethod.value;
    draftFilters.orderStatus = orderStatus.value;
    draftFilters.quickRange = quickRange.value;
    draftFilters.dateFrom = dateFrom.value;
    draftFilters.dateTo = dateTo.value;
    draftFilters.perPage = perPage.value;
}

function openFilterDialog() {
    syncDraftFilters();
    isFilterDialogOpen.value = true;
}

function changeDraftQuickRange(value) {
    draftFilters.quickRange = value;
    if (value) {
        draftFilters.dateFrom = '';
        draftFilters.dateTo = '';
    }
}

function applyFilters() {
    suppressFilterWatch.value = true;
    search.value = draftFilters.search;
    orderNumber.value = draftFilters.orderNumber;
    transactionId.value = draftFilters.transactionId;
    productQuery.value = draftFilters.productQuery;
    productId.value = draftFilters.productId;
    customerQuery.value = draftFilters.customerQuery;
    paymentMethod.value = draftFilters.paymentMethod;
    orderStatus.value = draftFilters.orderStatus;
    quickRange.value = draftFilters.quickRange;
    dateFrom.value = draftFilters.dateFrom;
    dateTo.value = draftFilters.dateTo;
    perPage.value = draftFilters.perPage;
    isFilterDialogOpen.value = false;
    load({ page: 1 });
    setTimeout(() => (suppressFilterWatch.value = false), 0);
}

function changeTab(key) {
    activeView.value = key;
    load({ active_view: key, page: 1 }, viewOnly(key));
}

function onPage(event) {
    perPage.value = event.rows;
    load({ page: event.page + 1, per_page: event.rows });
}

function onTransactionSort(event) {
    sortField.value = event.sortField || null;
    sortOrder.value = event.sortOrder === 1 ? 'asc' : 'desc';
    load({ page: 1 });
}

function onProductSort(event) {
    productSortField.value = event.sortField || 'net_sales';
    sortOrder.value = event.sortOrder === 1 ? 'asc' : 'desc';
    load({ page: 1 });
}

function resetFilters() {
    suppressFilterWatch.value = true;
    search.value = '';
    orderNumber.value = '';
    transactionId.value = '';
    productQuery.value = '';
    productId.value = null;
    customerQuery.value = '';
    paymentMethod.value = null;
    orderStatus.value = null;
    quickRange.value = null;
    dateFrom.value = '';
    dateTo.value = '';
    perPage.value = 25;
    sortField.value = null;
    productSortField.value = 'net_sales';
    sortOrder.value = 'desc';
    syncDraftFilters();
    isFilterDialogOpen.value = false;
    load({
        page: 1,
        search: undefined,
        order_number: undefined,
        transaction_id: undefined,
        product_query: undefined,
        product_id: undefined,
        customer_query: undefined,
        payment_method: undefined,
        order_status: undefined,
        quick_range: undefined,
        date_from: undefined,
        date_to: undefined,
        per_page: 25,
        sort_field: undefined,
        product_sort_field: 'net_sales',
        sort_order: 'desc',
    });
    setTimeout(() => (suppressFilterWatch.value = false), 0);
}

function refreshPage() {
    load();
}

async function copyText(value, key) {
    if (!value || !navigator?.clipboard) return;
    await navigator.clipboard.writeText(value);
    copied.value = key;
    setTimeout(() => (copied.value = null), 1200);
}

onBeforeUnmount(() => clearTimeout(timeout.value));
</script>

<template>
    <Head title="کارتکس فروش محصولات">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <TopNavTitle title="کارتکس فروش محصولات" :breadcrumb="[{ label: 'کارتکس فروش محصولات' }]">
            <template #pageAction>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-surface-500">آخرین بروزرسانی: {{ lastUpdated }}</span>
                    <Button icon="pi pi-refresh" label="تازه‌سازی" outlined severity="secondary" :loading="loading" @click="refreshPage" />
                    <Button icon="pi pi-download" label="خروجی" outlined severity="secondary" disabled />
                    <Button icon="pi pi-sliders-h" label="فیلتر پیشرفته" :badge="hasActiveFilters ? String(activeFiltersCount) : null" badgeSeverity="danger" @click="openFilterDialog" />
                </div>
            </template>
        </TopNavTitle>

        <div class="mb-4 rounded-xl border border-surface-200 bg-white p-4 text-sm leading-7 text-surface-600">
            گزارش تحلیلی فروش قطعی محصولات بر اساس سفارش‌های پرداخت‌شده. فقط سفارش‌های پرداخت‌شده با تاریخ پرداخت معتبر و وضعیت غیرلغوشده/غیرمرجوعی محاسبه می‌شوند.
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div v-for="item in stats" :key="item.label" class="rounded-xl border border-surface-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="text-sm text-surface-500">{{ item.label }}</span>
                        <div class="mt-2 text-lg font-bold text-surface-900">{{ item.value }}</div>
                    </div>
                    <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', item.tone]">
                        <i :class="item.icon" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex max-w-full gap-2 overflow-x-auto pb-1">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.key"
                        :label="tab.label"
                        :icon="tab.icon"
                        :severity="activeView === tab.key ? 'primary' : 'secondary'"
                        :outlined="activeView !== tab.key"
                        @click="changeTab(tab.key)"
                    />
                </div>
                <span class="text-sm text-surface-500">نمایش {{ resultText }}</span>
            </div>

            <DataTable
                v-if="activeView === 'transactions'"
                :value="transactionRows?.data || []"
                dataKey="id"
                lazy
                paginator
                removableSort
                scrollable
                stripedRows
                scrollHeight="620px"
                :loading="loading"
                :first="firstRow"
                :rows="transactionRows?.per_page || perPage"
                :totalRecords="transactionRows?.total || 0"
                :rowsPerPageOptions="perPageOptions"
                showGridlines
                @page="onPage"
                @sort="onTransactionSort"
            >
                <template #empty>ردیفی برای فروش قطعی محصولات پیدا نشد.</template>
                <Column header="ردیف" style="width: 5rem"><template #body="{ index }">{{ (firstRow + index + 1).toLocaleString('fa-IR') }}</template></Column>
                <Column header="تصویر" style="width: 6rem">
                    <template #body="{ data }">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-surface-200 bg-surface-50">
                            <img v-if="data.product_image" :src="data.product_image" :alt="data.product_name" class="h-full w-full object-cover" />
                            <i v-else class="pi pi-image text-surface-400" />
                        </div>
                    </template>
                </Column>
                <Column field="product_name" header="نام محصول Snapshot" sortable style="min-width: 18rem">
                    <template #body="{ data }">
                        <div class="max-w-72">
                            <Link v-if="data.product_slug && !data.product_deleted" :href="`/admin/products/${data.product_slug}`" class="font-medium text-primary hover:underline">{{ data.product_name }}</Link>
                            <span v-else class="font-medium">{{ data.product_name }}</span>
                            <Tag v-if="data.product_deleted" value="محصول حذف‌شده" severity="secondary" class="mt-1" />
                        </div>
                    </template>
                </Column>
                <Column field="variant_name" header="Variant Snapshot" style="min-width: 12rem"><template #body="{ data }">{{ data.variant_name || '-' }}</template></Column>
                <Column field="product_sku" header="SKU Snapshot" sortable style="min-width: 10rem"><template #body="{ data }">{{ data.product_sku || '-' }}</template></Column>
                <Column field="order_number" header="شماره سفارش" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <button class="inline-flex items-center gap-2 text-primary hover:underline" type="button" @click="copyText(data.order_number, `order-${data.id}`)">
                            {{ data.order_number }}
                            <i :class="copied === `order-${data.id}` ? 'pi pi-check' : 'pi pi-copy'" class="text-xs" />
                        </button>
                    </template>
                </Column>
                <Column field="customer_name" header="مشتری" sortable style="min-width: 12rem"><template #body="{ data }">{{ data.customer_name || '-' }}</template></Column>
                <Column field="customer_phone" header="موبایل" style="min-width: 10rem"><template #body="{ data }">{{ data.customer_phone || '-' }}</template></Column>
                <Column field="quantity" header="تعداد" sortable style="width: 7rem"><template #body="{ data }">{{ number(data.quantity) }}</template></Column>
                <Column field="unit_price" header="قیمت واحد" sortable style="min-width: 10rem"><template #body="{ data }">{{ money(data.unit_price) }}</template></Column>
                <Column field="discount" header="تخفیف" sortable style="min-width: 10rem"><template #body="{ data }">{{ data.discount_amount !== null ? money(data.discount_amount) : '-' }}</template></Column>
                <Column field="total_price" header="مبلغ نهایی" sortable style="min-width: 11rem"><template #body="{ data }">{{ money(data.total_amount) }}</template></Column>
                <Column field="payment_method" header="روش پرداخت" style="min-width: 10rem"><template #body="{ data }">{{ paymentMethodLabel(data.payment_method) }}</template></Column>
                <Column field="transaction_id" header="شماره تراکنش" sortable style="min-width: 13rem">
                    <template #body="{ data }">
                        <button v-if="data.transaction_id" class="inline-flex items-center gap-2" type="button" @click="copyText(data.transaction_id, `tx-${data.id}`)">
                            {{ data.transaction_id }}
                            <i :class="copied === `tx-${data.id}` ? 'pi pi-check text-green-500' : 'pi pi-copy text-surface-400'" class="text-xs" />
                        </button>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column field="paid_at" header="تاریخ پرداخت" sortable style="min-width: 12rem"><template #body="{ data }">{{ formatJalaliDateTime(data.paid_at) }}</template></Column>
                <Column field="order_status" header="وضعیت سفارش" style="min-width: 10rem"><template #body="{ data }"><Tag :value="statusLabel(data.order_status)" :severity="statusSeverity(data.order_status)" /></template></Column>
                <Column header="عملیات" frozen alignFrozen="left" style="width: 7rem">
                    <template #body="{ data }">
                        <Link v-if="data.order_show_url" :href="data.order_show_url"><Button icon="pi pi-eye" rounded text severity="info" aria-label="مشاهده سفارش" /></Link>
                    </template>
                </Column>
            </DataTable>

            <DataTable
                v-else-if="activeView === 'products'"
                :value="productSummaryRows?.data || []"
                dataKey="id"
                lazy
                paginator
                removableSort
                scrollable
                stripedRows
                :loading="loading"
                :first="firstRow"
                :rows="productSummaryRows?.per_page || perPage"
                :totalRecords="productSummaryRows?.total || 0"
                :rowsPerPageOptions="perPageOptions"
                showGridlines
                @page="onPage"
                @sort="onProductSort"
            >
                <template #empty>خلاصه‌ای برای محصولات فروخته‌شده پیدا نشد.</template>
                <Column header="ردیف" style="width: 5rem"><template #body="{ index }">{{ (firstRow + index + 1).toLocaleString('fa-IR') }}</template></Column>
                <Column header="تصویر" style="width: 6rem">
                    <template #body="{ data }">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-surface-200 bg-surface-50">
                            <img v-if="data.product_image" :src="data.product_image" :alt="data.product_name" class="h-full w-full object-cover" />
                            <i v-else class="pi pi-image text-surface-400" />
                        </div>
                    </template>
                </Column>
                <Column field="product_name" header="نام محصول" sortable style="min-width: 18rem">
                    <template #body="{ data }">
                        <div class="space-y-1">
                            <span class="font-semibold">{{ data.product_name }}</span>
                            <Tag v-if="data.product_deleted" value="محصول حذف‌شده" severity="secondary" />
                        </div>
                    </template>
                </Column>
                <Column field="product_sku" header="SKU" sortable style="min-width: 10rem"><template #body="{ data }">{{ data.product_sku || '-' }}</template></Column>
                <Column field="quantity_sold" header="تعداد فروش" sortable><template #body="{ data }">{{ number(data.quantity_sold) }}</template></Column>
                <Column field="orders_count" header="سفارش‌ها" sortable><template #body="{ data }">{{ number(data.orders_count) }}</template></Column>
                <Column field="gross_sales" header="فروش ناخالص" sortable><template #body="{ data }">{{ money(data.gross_sales) }}</template></Column>
                <Column field="discount_total" header="تخفیف" sortable><template #body="{ data }">{{ money(data.discount_total) }}</template></Column>
                <Column field="net_sales" header="فروش خالص" sortable><template #body="{ data }">{{ money(data.net_sales) }}</template></Column>
                <Column field="average_unit_price" header="میانگین قیمت" sortable><template #body="{ data }">{{ money(data.average_unit_price) }}</template></Column>
                <Column field="last_sold_at" header="آخرین فروش" sortable><template #body="{ data }">{{ formatJalaliDateTime(data.last_sold_at) }}</template></Column>
                <Column field="sales_share" header="سهم فروش" sortable><template #body="{ data }">{{ Number(data.sales_share || 0).toLocaleString('fa-IR') }}٪</template></Column>
                <Column header="عملیات" frozen alignFrozen="left" style="width: 8rem">
                    <template #body="{ data }">
                        <Button icon="pi pi-list" rounded text severity="info" aria-label="مشاهده تراکنش‌ها" @click="activeView = 'transactions'; productId = data.product_id; load({ active_view: 'transactions', product_id: data.product_id, page: 1 }, viewOnly('transactions'))" />
                    </template>
                </Column>
            </DataTable>

            <div v-else class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-surface-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="font-bold">روند فروش</h3>
                        <Select v-model="chartGrouping" :options="chartGroupingOptions" optionLabel="label" optionValue="value" class="w-36" />
                    </div>
                    <Chart v-if="chartData?.timeline?.length" type="line" :data="timelineChart" :options="chartOptions" class="h-80" />
                    <div v-else class="py-16 text-center text-surface-500">داده‌ای برای نمودار روند فروش وجود ندارد.</div>
                </div>
                <div class="rounded-xl border border-surface-200 bg-white p-4">
                    <h3 class="mb-3 font-bold">محصولات برتر</h3>
                    <Chart v-if="chartData?.top_products?.length" type="bar" :data="topProductsChart" :options="chartOptions" class="h-80" />
                    <div v-else class="py-16 text-center text-surface-500">محصول پرفروشی برای نمایش وجود ندارد.</div>
                </div>
                <div class="rounded-xl border border-surface-200 bg-white p-4 xl:col-span-2">
                    <h3 class="mb-3 font-bold">سهم روش‌های پرداخت</h3>
                    <Chart v-if="chartData?.payment_methods?.length" type="doughnut" :data="paymentChart" :options="{ responsive: true, maintainAspectRatio: false }" class="h-80" />
                    <div v-else class="py-16 text-center text-surface-500">داده‌ای برای روش‌های پرداخت وجود ندارد.</div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="isFilterDialogOpen" modal dismissableMask header="فیلتر پیشرفته" class="w-[94vw] max-w-4xl" :breakpoints="{ '960px': '92vw', '640px': '96vw' }">
            <form class="space-y-5" @submit.prevent="applyFilters">
                <div>
                    <label class="mb-2 block text-sm font-medium text-surface-700">جستجوی عمومی</label>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="draftFilters.search" class="w-full" placeholder="سفارش، محصول، SKU، مشتری، تراکنش" />
                    </IconField>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">شماره سفارش</label>
                        <InputText v-model="draftFilters.orderNumber" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">شناسه تراکنش</label>
                        <InputText v-model="draftFilters.transactionId" class="w-full" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">محصول</label>
                        <InputText v-model="draftFilters.productQuery" class="w-full" placeholder="نام یا SKU محصول" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">انتخاب محصول</label>
                        <Select v-model="draftFilters.productId" :options="productFilters" optionLabel="label" optionValue="id" filter class="w-full" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">مشتری / موبایل</label>
                        <InputText v-model="draftFilters.customerQuery" class="w-full" placeholder="نام مشتری یا شماره موبایل" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">روش پرداخت</label>
                        <Select v-model="draftFilters.paymentMethod" :options="paymentMethodFilters" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">تاریخ از</label>
                        <PersianDatePicker v-model="draftFilters.dateFrom" placeholder="از تاریخ پرداخت" @update:modelValue="draftFilters.quickRange = null" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">تاریخ تا</label>
                        <PersianDatePicker v-model="draftFilters.dateTo" placeholder="تا تاریخ پرداخت" @update:modelValue="draftFilters.quickRange = null" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">بازه سریع</label>
                        <Select :modelValue="draftFilters.quickRange" :options="quickRangeOptions" optionLabel="label" optionValue="value" class="w-full" @update:modelValue="changeDraftQuickRange" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">وضعیت سفارش</label>
                        <Select v-model="draftFilters.orderStatus" :options="orderStatusFilters" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-surface-700">تعداد نمایش</label>
                        <Select v-model="draftFilters.perPage" :options="perPageOptions" class="w-full" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-surface-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
                    <Button icon="pi pi-filter-slash" label="پاک کردن" outlined severity="secondary" type="button" @click="resetFilters" />
                    <Button icon="pi pi-times" label="بستن" outlined severity="secondary" type="button" @click="isFilterDialogOpen = false" />
                    <Button icon="pi pi-check" label="اعمال فیلتر" type="submit" :loading="loading" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
