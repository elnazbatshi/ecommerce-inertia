<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    orderRows: { type: Object, default: null },
    purchasedProductRows: { type: Object, default: null },
    paymentRows: { type: Object, default: null },
    analyticsData: { type: Object, default: null },
    timelineRows: { type: Object, default: null },
    frequentlyPurchasedProducts: { type: Array, default: () => [] },
    reminderProducts: { type: Array, default: () => [] },
    nextPurchaseSuggestions: { type: Array, default: () => [] },
    additionalInsights: { type: Object, default: () => ({}) },
    lifetimeValue: { type: Number, default: 0 },
    lastPurchase: { type: Object, default: null },
    purchaseFrequency: { type: Object, default: () => ({}) },
    customerSegment: { type: Object, default: null },
    statistics: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    activeView: { type: String, default: 'orders' },
    selectedCustomer: { type: Object, default: null },
    customerSearchResults: { type: Array, default: () => [] },
    hasSelectedCustomer: { type: Boolean, default: false },
    orderStatusOptions: { type: Array, default: () => [] },
    paymentMethodOptions: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
});

const tabs = [
    { key: 'orders', label: 'سفارش‌ها', icon: 'pi pi-shopping-cart' },
    { key: 'products', label: 'کالاهای خریداری‌شده', icon: 'pi pi-box' },
    { key: 'payments', label: 'پرداخت‌ها', icon: 'pi pi-wallet' },
    { key: 'analytics', label: 'تحلیل خرید', icon: 'pi pi-chart-line' },
    { key: 'timeline', label: 'خط زمانی خرید', icon: 'pi pi-history' },
];

const activeView = ref(props.activeView || 'orders');
const selectedCustomerOption = ref(props.selectedCustomer ? customerOption(props.selectedCustomer) : null);
const customerSuggestions = ref(props.customerSearchResults);
const customerSearchTimer = ref(null);
const loading = ref(false);
const isFilterDialogOpen = ref(false);
const expandedOrders = ref({});

const filters = reactive({
    customerId: props.filters.customer_id ?? null,
    customerSearch: props.filters.customer_search ?? '',
    quickRange: props.filters.quick_range ?? null,
    dateFrom: props.filters.date_from ?? '',
    dateTo: props.filters.date_to ?? '',
    orderNumber: props.filters.order_number ?? '',
    productSearch: props.filters.product_search ?? '',
    productId: props.filters.product_id ?? null,
    paymentMethod: props.filters.payment_method ?? null,
    orderStatus: props.filters.order_status ?? null,
    minAmount: props.filters.min_amount ?? null,
    maxAmount: props.filters.max_amount ?? null,
    perPage: Number(props.filters.per_page ?? 25),
    orderSortField: props.filters.order_sort_field ?? null,
    productSortField: props.filters.product_sort_field ?? 'net_amount',
    paymentSortField: props.filters.payment_sort_field ?? null,
    sortOrder: props.filters.sort_order ?? 'desc',
    chartGrouping: props.filters.chart_grouping ?? 'day',
});

const draftFilters = reactive({ ...filters });

const orderStatusFilters = computed(() => [
    { label: 'همه وضعیت‌های معتبر', value: null },
    ...props.orderStatusOptions.map((item) => ({ label: statusLabel(item.value), value: item.value })),
]);
const paymentMethodFilters = computed(() => [
    { label: 'همه روش‌های پرداخت', value: null },
    ...props.paymentMethodOptions.map((item) => ({ label: paymentMethodLabel(item.value), value: item.value })),
]);
const quickRangeOptions = [
    { label: 'همه زمان‌ها', value: 'all' },
    { label: 'امروز', value: 'today' },
    { label: '۷ روز اخیر', value: 'last_7_days' },
    { label: '۳۰ روز اخیر', value: 'last_30_days' },
    { label: '۳ ماه اخیر', value: 'last_3_months' },
    { label: '۶ ماه اخیر', value: 'last_6_months' },
    { label: 'سال جاری', value: 'current_year' },
];
const chartGroupingOptions = [
    { label: 'روزانه', value: 'day' },
    { label: 'هفتگی', value: 'week' },
    { label: 'ماهانه', value: 'month' },
];

const activeRows = computed(() => {
    if (activeView.value === 'products') return props.purchasedProductRows;
    if (activeView.value === 'payments') return props.paymentRows;
    if (activeView.value === 'timeline') return props.timelineRows;
    return props.orderRows;
});
const firstRow = computed(() => activeRows.value ? ((activeRows.value.current_page - 1) * activeRows.value.per_page) : 0);
const hasActiveFilters = computed(() => Boolean(
    filters.quickRange || filters.dateFrom || filters.dateTo || filters.orderNumber || filters.productSearch ||
    filters.productId || filters.paymentMethod || filters.orderStatus || filters.minAmount || filters.maxAmount
));
const activeFiltersCount = computed(() => [
    filters.quickRange,
    filters.dateFrom,
    filters.dateTo,
    filters.orderNumber,
    filters.productSearch,
    filters.productId,
    filters.paymentMethod,
    filters.orderStatus,
    filters.minAmount,
    filters.maxAmount,
].filter((value) => value !== null && value !== undefined && value !== '').length);
const resultText = computed(() => {
    if (!activeRows.value) return 'داده‌ای برای تب فعلی بارگذاری نشده است.';

    const from = activeRows.value.total ? firstRow.value + 1 : 0;
    const to = Math.min(firstRow.value + activeRows.value.per_page, activeRows.value.total);

    return `${from.toLocaleString('fa-IR')} تا ${to.toLocaleString('fa-IR')} از ${Number(activeRows.value.total || 0).toLocaleString('fa-IR')}`;
});
const stats = computed(() => [
    { label: 'مجموع خرید قطعی', value: money(props.statistics.total_spent), icon: 'pi pi-wallet', tone: 'bg-emerald-100 text-emerald-600' },
    { label: 'سفارش قطعی', value: number(props.statistics.orders_count), icon: 'pi pi-shopping-bag', tone: 'bg-blue-100 text-blue-600' },
    { label: 'اقلام خریداری‌شده', value: number(props.statistics.items_count), icon: 'pi pi-box', tone: 'bg-amber-100 text-amber-600' },
    { label: 'محصول متفاوت', value: number(props.statistics.products_count), icon: 'pi pi-tags', tone: 'bg-cyan-100 text-cyan-600' },
    { label: 'تخفیف دریافتی', value: money(props.statistics.discount_total), icon: 'pi pi-percentage', tone: 'bg-rose-100 text-rose-600' },
    { label: 'هزینه ارسال', value: money(props.statistics.shipping_total), icon: 'pi pi-send', tone: 'bg-orange-100 text-orange-600' },
    { label: 'میانگین سفارش', value: money(props.statistics.average_order_value), icon: 'pi pi-chart-bar', tone: 'bg-violet-100 text-violet-600' },
    { label: 'آخرین خرید', value: props.statistics.last_purchase_at ? formatJalaliDateTime(props.statistics.last_purchase_at) : '-', icon: 'pi pi-clock', tone: 'bg-slate-100 text-slate-600' },
    { label: 'اولین خرید', value: props.statistics.first_purchase_at ? formatJalaliDateTime(props.statistics.first_purchase_at) : '-', icon: 'pi pi-calendar', tone: 'bg-lime-100 text-lime-600' },
]);
const analyticCards = computed(() => [
    { label: 'ارزش طول عمر مشتری', value: money(props.lifetimeValue), help: 'کل خرید قطعی مشتری بدون اثر گرفتن از فیلتر تاریخ', icon: 'pi pi-database', tone: 'bg-emerald-100 text-emerald-600' },
    { label: 'میانگین فاصله خرید', value: props.purchaseFrequency.average_interval_days === null || props.purchaseFrequency.average_interval_days === undefined ? 'داده کافی نیست' : `${props.purchaseFrequency.average_interval_days} روز`, help: 'میانگین فاصله بین سفارش‌های قطعی', icon: 'pi pi-calendar-clock', tone: 'bg-blue-100 text-blue-600' },
    { label: 'روز از آخرین خرید', value: props.purchaseFrequency.days_since_last_purchase === null || props.purchaseFrequency.days_since_last_purchase === undefined ? '-' : `${props.purchaseFrequency.days_since_last_purchase} روز`, help: 'براساس آخرین سفارش قطعی', icon: 'pi pi-clock', tone: 'bg-amber-100 text-amber-600' },
    { label: 'نرخ خرید مجدد', value: `${Math.round((props.additionalInsights.repeat_order_ratio || 0) * 100).toLocaleString('fa-IR')}٪`, help: 'سفارش‌های بعد از اولین سفارش تقسیم بر کل سفارش‌ها', icon: 'pi pi-refresh', tone: 'bg-violet-100 text-violet-600' },
]);

const purchaseAmountChart = computed(() => ({
    labels: props.analyticsData?.purchase_amount_trend?.map((item) => item.label) || [],
    datasets: [{ label: 'مبلغ خرید', data: props.analyticsData?.purchase_amount_trend?.map((item) => item.total_amount) || [], borderColor: '#d6a84f', backgroundColor: 'rgba(214,168,79,.18)', tension: 0.35, fill: true }],
}));
const orderCountChart = computed(() => ({
    labels: props.analyticsData?.order_count_trend?.map((item) => item.label) || [],
    datasets: [{ label: 'تعداد سفارش', data: props.analyticsData?.order_count_trend?.map((item) => item.orders_count) || [], backgroundColor: '#111827' }],
}));
const itemsTrendChart = computed(() => ({
    labels: props.analyticsData?.items_quantity_trend?.map((item) => item.label) || [],
    datasets: [{ label: 'تعداد اقلام', data: props.analyticsData?.items_quantity_trend?.map((item) => item.quantity) || [], backgroundColor: '#2563eb' }],
}));
const favoriteProductsChart = computed(() => ({
    labels: props.analyticsData?.favorite_products?.map((item) => item.label) || [],
    datasets: [{ label: 'تعداد خرید', data: props.analyticsData?.favorite_products?.map((item) => item.quantity_purchased) || [], backgroundColor: '#d6a84f' }],
}));
const topSpentProductsChart = computed(() => ({
    labels: props.analyticsData?.top_spent_products?.map((item) => item.label) || [],
    datasets: [{ label: 'مبلغ خالص', data: props.analyticsData?.top_spent_products?.map((item) => item.net_amount) || [], backgroundColor: '#111827' }],
}));
const paymentMethodChart = computed(() => ({
    labels: props.analyticsData?.payment_methods?.map((item) => paymentMethodLabel(item.method)) || [],
    datasets: [{ label: 'مبلغ', data: props.analyticsData?.payment_methods?.map((item) => item.total_amount) || [], backgroundColor: ['#d6a84f', '#111827', '#2563eb', '#16a34a', '#dc2626'] }],
}));
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { font: { family: 'IRANSansWebFaNum' } } } },
    scales: { y: { ticks: { callback: (value) => Number(value).toLocaleString('fa-IR') } } },
};

function customerOption(customer) {
    return {
        id: customer.id,
        name: customer.name,
        phone: customer.phone,
        email: customer.email,
        label: `${customer.name || 'مشتری بدون نام'} - ${customer.phone || '-'} #${customer.id}`,
    };
}

function money(value) {
    return `${Number(value ?? 0).toLocaleString('fa-IR')} تومان`;
}

function number(value) {
    return Number(value ?? 0).toLocaleString('fa-IR');
}

function statusLabel(status) {
    return { pending: 'در انتظار بررسی', processing: 'در حال پردازش', shipped: 'ارسال شده', delivered: 'تحویل داده شده', cancelled: 'لغو شده', returned: 'مرجوع شده' }[status] ?? status;
}

function statusSeverity(status) {
    return { pending: 'warn', processing: 'info', shipped: 'help', delivered: 'success', cancelled: 'danger', returned: 'secondary' }[status] ?? 'secondary';
}

function paymentMethodLabel(method) {
    return { online: 'آنلاین', card_to_card: 'کارت به کارت', cash: 'نقدی', wallet: 'کیف پول', unknown: 'نامشخص' }[method] ?? method;
}

function requestPayload(extra = {}) {
    return {
        customer_id: filters.customerId || undefined,
        customer_search: filters.customerSearch || undefined,
        active_view: activeView.value,
        chart_grouping: filters.chartGrouping,
        quick_range: filters.quickRange || undefined,
        date_from: filters.dateFrom || undefined,
        date_to: filters.dateTo || undefined,
        order_number: filters.orderNumber || undefined,
        product_search: filters.productSearch || undefined,
        product_id: filters.productId || undefined,
        payment_method: filters.paymentMethod || undefined,
        order_status: filters.orderStatus || undefined,
        min_amount: filters.minAmount || undefined,
        max_amount: filters.maxAmount || undefined,
        per_page: filters.perPage,
        order_sort_field: filters.orderSortField || undefined,
        product_sort_field: filters.productSortField || undefined,
        payment_sort_field: filters.paymentSortField || undefined,
        sort_order: filters.sortOrder || undefined,
        ...extra,
    };
}

function viewOnly(key = activeView.value) {
    const base = ['statistics', 'filters', 'activeView', 'selectedCustomer', 'hasSelectedCustomer'];
    if (key === 'products') return ['purchasedProductRows', ...base];
    if (key === 'payments') return ['paymentRows', ...base];
    if (key === 'analytics') return ['analyticsData', 'frequentlyPurchasedProducts', 'reminderProducts', 'nextPurchaseSuggestions', 'additionalInsights', ...base];
    if (key === 'timeline') return ['timelineRows', ...base];
    return ['orderRows', ...base];
}

function load(extra = {}, only = viewOnly()) {
    loading.value = true;
    router.get('/admin/customer-purchase-ledger', requestPayload(extra), {
        only,
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
}

function searchCustomers(event) {
    clearTimeout(customerSearchTimer.value);
    const query = event.query || '';
    if (query.length < 2) {
        customerSuggestions.value = [];
        return;
    }

    customerSearchTimer.value = setTimeout(() => {
        router.get('/admin/customer-purchase-ledger', requestPayload({ customer_search: query }), {
            only: ['customerSearchResults', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                customerSuggestions.value = page.props.customerSearchResults || [];
            },
        });
    }, 350);
}

function selectCustomer(event) {
    const customer = event.value;
    selectedCustomerOption.value = customer;
    activeView.value = 'orders';
    filters.customerId = customer?.id ?? null;
    filters.customerSearch = customer?.label ?? '';
    filters.productId = null;
    filters.orderSortField = null;
    filters.productSortField = 'net_amount';
    filters.paymentSortField = null;
    load({ customer_id: filters.customerId, customer_search: undefined, active_view: 'orders', product_id: undefined, page: 1 }, viewOnly('orders'));
}

function clearCustomer() {
    selectedCustomerOption.value = null;
    activeView.value = 'orders';
    Object.assign(filters, {
        customerId: null,
        customerSearch: '',
        productId: null,
        orderSortField: null,
        productSortField: 'net_amount',
        paymentSortField: null,
    });
    load({ customer_id: undefined, customer_search: undefined, active_view: 'orders', product_id: undefined, page: 1 });
}

function syncDraftFilters() {
    Object.assign(draftFilters, filters);
}

function openFilterDialog() {
    syncDraftFilters();
    isFilterDialogOpen.value = true;
}

function applyFilters() {
    Object.assign(filters, draftFilters);
    isFilterDialogOpen.value = false;
    load({ page: 1 });
}

function resetFilters() {
    Object.assign(filters, {
        quickRange: null,
        dateFrom: '',
        dateTo: '',
        orderNumber: '',
        productSearch: '',
        productId: null,
        paymentMethod: null,
        orderStatus: null,
        minAmount: null,
        maxAmount: null,
        perPage: 25,
        orderSortField: null,
        productSortField: 'net_amount',
        paymentSortField: null,
        sortOrder: 'desc',
    });
    syncDraftFilters();
    isFilterDialogOpen.value = false;
    load({
        page: 1,
        quick_range: undefined,
        date_from: undefined,
        date_to: undefined,
        order_number: undefined,
        product_search: undefined,
        product_id: undefined,
        payment_method: undefined,
        order_status: undefined,
        min_amount: undefined,
        max_amount: undefined,
        per_page: 25,
        order_sort_field: undefined,
        product_sort_field: 'net_amount',
        payment_sort_field: undefined,
        sort_order: 'desc',
    });
}

function changeTab(key) {
    activeView.value = key;
    load({ active_view: key, page: 1 }, viewOnly(key));
}

function onPage(event) {
    filters.perPage = event.rows;
    load({ page: event.page + 1, per_page: event.rows });
}

function onOrderSort(event) {
    filters.orderSortField = event.sortField || null;
    filters.sortOrder = event.sortOrder === 1 ? 'asc' : 'desc';
    load({ page: 1 });
}

function onProductSort(event) {
    filters.productSortField = event.sortField || 'net_amount';
    filters.sortOrder = event.sortOrder === 1 ? 'asc' : 'desc';
    load({ page: 1 });
}

function onPaymentSort(event) {
    filters.paymentSortField = event.sortField || null;
    filters.sortOrder = event.sortOrder === 1 ? 'asc' : 'desc';
    load({ page: 1 });
}

function onTimelinePage(event) {
    load({ timeline_page: event.page + 1, timeline_per_page: event.rows }, viewOnly('timeline'));
}

function filterOrdersByProduct(row) {
    activeView.value = 'orders';
    filters.productId = row.product_id;
    filters.productSearch = row.product_id ? '' : row.product_name;
    load({ active_view: 'orders', product_id: row.product_id || undefined, product_search: row.product_id ? undefined : row.product_name, page: 1 }, viewOnly('orders'));
}

function changeChartGrouping(value) {
    filters.chartGrouping = value;
    load({ chart_grouping: value, page: 1 }, viewOnly('analytics'));
}

function refreshPage() {
    load();
}

onBeforeUnmount(() => clearTimeout(customerSearchTimer.value));
</script>

<template>
    <Head title="کارتکس خرید مشتریان">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <TopNavTitle title="کارتکس خرید مشتریان" :breadcrumb="[{ label: 'کارتکس خرید مشتریان' }]">
            <template #pageAction>
                <div class="flex flex-wrap items-center gap-2">
                    <Button icon="pi pi-refresh" label="تازه‌سازی" outlined severity="secondary" :loading="loading" @click="refreshPage" />
                    <Button icon="pi pi-filter" label="فیلتر کارتکس مشتری" :badge="hasActiveFilters ? String(activeFiltersCount) : null" badgeSeverity="danger" :disabled="!filters.customerId" @click="openFilterDialog" />
                    <Button v-if="filters.customerId" icon="pi pi-times" label="پاک‌کردن مشتری" outlined severity="secondary" @click="clearCustomer" />
                </div>
            </template>
        </TopNavTitle>

        <div class="mb-4 rounded-xl border border-surface-200 bg-white p-4 text-sm leading-7 text-surface-600">
            بررسی سفارش‌ها، کالاهای خریداری‌شده و مبالغ پرداختی هر مشتری. این گزارش فقط سفارش‌های پرداخت‌شده با تاریخ پرداخت معتبر و وضعیت غیرلغوشده/غیرمرجوعی را محاسبه می‌کند.
        </div>

        <div class="mb-4 rounded-xl border border-surface-200 bg-white p-4 shadow-sm">
            <label class="mb-2 block text-sm font-medium text-surface-700">انتخاب مشتری</label>
            <AutoComplete
                v-model="selectedCustomerOption"
                :suggestions="customerSuggestions"
                optionLabel="label"
                forceSelection
                class="w-full"
                inputClass="w-full"
                placeholder="نام، موبایل، ایمیل یا شناسه مشتری را وارد کنید"
                @complete="searchCustomers"
                @item-select="selectCustomer"
            >
                <template #option="{ option }">
                    <div class="flex flex-col gap-1 text-right">
                        <span class="font-semibold">{{ option.name || 'مشتری بدون نام' }} <small class="text-surface-400">#{{ option.id }}</small></span>
                        <span class="text-xs text-surface-500">{{ option.phone || '-' }} <span v-if="option.email">- {{ option.email }}</span></span>
                    </div>
                </template>
            </AutoComplete>
        </div>

        <div v-if="!filters.customerId" class="rounded-xl border border-dashed border-surface-300 bg-white p-10 text-center shadow-sm">
            <i class="pi pi-user-plus text-4xl text-surface-300" />
            <h2 class="mt-4 text-lg font-bold text-surface-900">برای مشاهده کارتکس، ابتدا یک مشتری را انتخاب کنید.</h2>
            <p class="mt-2 text-sm text-surface-500">بعد از انتخاب مشتری، سفارش‌ها، کالاهای خریداری‌شده، پرداخت‌ها و تحلیل خرید نمایش داده می‌شود.</p>
        </div>

        <template v-else>
            <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-surface-200 bg-white p-4 shadow-sm xl:col-span-1">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-surface-900">{{ selectedCustomer?.name || 'مشتری بدون نام' }}</h2>
                            <p class="mt-1 text-sm text-surface-500">{{ selectedCustomer?.phone || '-' }}</p>
                            <p v-if="selectedCustomer?.email" class="text-sm text-surface-500">{{ selectedCustomer.email }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <Tag v-if="customerSegment" :value="customerSegment.label" :severity="customerSegment.severity" />
                            <Tag v-if="selectedCustomer?.status" :value="selectedCustomer.status" severity="info" />
                        </div>
                    </div>
                    <p v-if="customerSegment?.description" class="mt-3 rounded-lg bg-surface-50 p-3 text-xs leading-6 text-surface-600">{{ customerSegment.description }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-surface-400">عضویت</span>
                            <div class="mt-1 font-medium">{{ formatJalaliDateTime(selectedCustomer?.created_at) }}</div>
                        </div>
                        <div>
                            <span class="text-surface-400">آدرس‌ها</span>
                            <div class="mt-1 font-medium">{{ number(selectedCustomer?.addresses_count) }}</div>
                        </div>
                        <div v-if="selectedCustomer?.last_login_at">
                            <span class="text-surface-400">آخرین ورود</span>
                            <div class="mt-1 font-medium">{{ formatJalaliDateTime(selectedCustomer.last_login_at) }}</div>
                        </div>
                    </div>
                    <Link v-if="selectedCustomer?.admin_url" :href="selectedCustomer.admin_url" class="mt-4 inline-flex items-center gap-2 text-sm text-primary hover:underline">
                        <i class="pi pi-external-link text-xs" />
                        مشاهده پرونده مشتری
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:col-span-2 xl:grid-cols-3">
                    <div v-for="item in stats" :key="item.label" class="rounded-xl border border-surface-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-sm text-surface-500">{{ item.label }}</span>
                                <div class="mt-2 text-base font-bold text-surface-900">{{ item.value }}</div>
                            </div>
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', item.tone]">
                                <i :class="item.icon" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-surface-200 bg-white p-4 shadow-sm xl:col-span-1">
                    <h3 class="mb-3 font-bold text-surface-900">آخرین خرید مشتری</h3>
                    <div v-if="lastPurchase" class="flex gap-3">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-xl bg-surface-100">
                            <img v-if="lastPurchase.first_product_image" :src="lastPurchase.first_product_image" :alt="lastPurchase.first_product_name" class="h-full w-full object-cover" />
                            <i v-else class="pi pi-image text-2xl text-surface-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-surface-900">{{ lastPurchase.first_product_name || 'بدون قلم' }}</p>
                            <p v-if="lastPurchase.other_items_count" class="mt-1 text-xs text-surface-500">+ {{ number(lastPurchase.other_items_count) }} کالای دیگر</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-surface-600">
                                <span>{{ lastPurchase.order_number }}</span>
                                <span>{{ money(lastPurchase.total_amount) }}</span>
                                <span>{{ formatJalaliDateTime(lastPurchase.paid_at) }}</span>
                                <span>{{ lastPurchase.days_since }} روز پیش</span>
                            </div>
                            <Link :href="lastPurchase.order_url" class="mt-3 inline-flex text-sm text-primary hover:underline">مشاهده سفارش</Link>
                        </div>
                    </div>
                    <div v-else class="rounded-lg border border-dashed border-surface-200 p-6 text-center text-sm text-surface-500">این مشتری هنوز خرید قطعی ندارد.</div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:col-span-2 xl:grid-cols-4">
                    <div v-for="item in analyticCards" :key="item.label" class="rounded-xl border border-surface-200 bg-white p-4 shadow-sm" :title="item.help">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-sm text-surface-500">{{ item.label }}</span>
                                <div class="mt-2 text-base font-bold text-surface-900">{{ item.value }}</div>
                            </div>
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', item.tone]">
                                <i :class="item.icon" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex max-w-full gap-2 overflow-x-auto pb-1">
                        <Button v-for="tab in tabs" :key="tab.key" :label="tab.label" :icon="tab.icon" :severity="activeView === tab.key ? 'primary' : 'secondary'" :outlined="activeView !== tab.key" @click="changeTab(tab.key)" />
                    </div>
                    <span v-if="activeView !== 'analytics'" class="text-sm text-surface-500">نمایش {{ resultText }}</span>
                </div>

                <DataTable
                    v-if="activeView === 'orders'"
                    v-model:expandedRows="expandedOrders"
                    :value="orderRows?.data || []"
                    dataKey="id"
                    lazy
                    paginator
                    removableSort
                    scrollable
                    stripedRows
                    showGridlines
                    :loading="loading"
                    :first="firstRow"
                    :rows="orderRows?.per_page || filters.perPage"
                    :totalRecords="orderRows?.total || 0"
                    :rowsPerPageOptions="perPageOptions"
                    @page="onPage"
                    @sort="onOrderSort"
                >
                    <template #empty>این مشتری هنوز خرید قطعی در این فیلترها ندارد.</template>
                    <Column expander style="width: 4rem" />
                    <Column header="ردیف" style="width: 5rem"><template #body="{ index }">{{ (firstRow + index + 1).toLocaleString('fa-IR') }}</template></Column>
                    <Column field="order_number" header="شماره سفارش" sortable style="min-width: 12rem"><template #body="{ data }"><Link :href="data.order_url" class="text-primary hover:underline">{{ data.order_number }}</Link></template></Column>
                    <Column field="created_at" header="تاریخ ثبت" sortable style="min-width: 12rem"><template #body="{ data }">{{ formatJalaliDateTime(data.created_at) }}</template></Column>
                    <Column field="paid_at" header="تاریخ پرداخت" sortable style="min-width: 12rem"><template #body="{ data }">{{ formatJalaliDateTime(data.paid_at) }}</template></Column>
                    <Column field="items_count" header="تعداد اقلام" sortable><template #body="{ data }">{{ number(data.items_count) }}</template></Column>
                    <Column field="subtotal" header="مبلغ کالاها" sortable><template #body="{ data }">{{ money(data.subtotal) }}</template></Column>
                    <Column field="discount" header="تخفیف" sortable><template #body="{ data }">{{ money(data.discount_amount) }}</template></Column>
                    <Column field="shipping_amount" header="ارسال" sortable><template #body="{ data }">{{ money(data.shipping_amount) }}</template></Column>
                    <Column field="total_amount" header="مبلغ نهایی" sortable><template #body="{ data }">{{ money(data.total_amount) }}</template></Column>
                    <Column field="payment_method" header="روش پرداخت"><template #body="{ data }">{{ paymentMethodLabel(data.payment_method) }}</template></Column>
                    <Column field="transaction_id" header="شناسه تراکنش"><template #body="{ data }">{{ data.transaction_id || '-' }}</template></Column>
                    <Column field="status" header="وضعیت" sortable><template #body="{ data }"><Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" /></template></Column>
                    <Column header="عملیات" frozen alignFrozen="left" style="width: 6rem"><template #body="{ data }"><Link :href="data.order_url"><Button icon="pi pi-eye" rounded text severity="info" /></Link></template></Column>
                    <template #expansion="{ data }">
                        <div class="rounded-lg bg-surface-50 p-4">
                            <h4 class="mb-3 font-bold">اقلام سفارش</h4>
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                <div v-for="item in data.items" :key="item.id" class="flex gap-3 rounded-lg border border-surface-200 bg-white p-3">
                                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-lg bg-surface-100">
                                        <img v-if="item.image_url" :src="item.image_url" :alt="item.product_name" class="h-full w-full object-cover" />
                                        <i v-else class="pi pi-image text-surface-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold">{{ item.product_name }}</span>
                                            <Tag v-if="item.is_deleted" value="محصول حذف‌شده" severity="secondary" />
                                        </div>
                                        <p class="mt-1 text-xs text-surface-500">{{ item.variant_name || 'بدون Variant' }} - {{ item.sku || '-' }}</p>
                                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-surface-600">
                                            <span>تعداد: {{ number(item.quantity) }}</span>
                                            <span>واحد: {{ money(item.unit_price) }}</span>
                                            <span>تخفیف: {{ item.discount_amount !== null ? money(item.discount_amount) : '-' }}</span>
                                            <span>جمع: {{ money(item.total_amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>

                <DataTable
                    v-else-if="activeView === 'products'"
                    :value="purchasedProductRows?.data || []"
                    dataKey="group_key"
                    lazy
                    paginator
                    removableSort
                    scrollable
                    stripedRows
                    showGridlines
                    :loading="loading"
                    :first="firstRow"
                    :rows="purchasedProductRows?.per_page || filters.perPage"
                    :totalRecords="purchasedProductRows?.total || 0"
                    :rowsPerPageOptions="perPageOptions"
                    @page="onPage"
                    @sort="onProductSort"
                >
                    <template #empty>کالایی برای خرید قطعی این مشتری پیدا نشد.</template>
                    <Column header="ردیف" style="width: 5rem"><template #body="{ index }">{{ (firstRow + index + 1).toLocaleString('fa-IR') }}</template></Column>
                    <Column header="تصویر" style="width: 6rem"><template #body="{ data }"><div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-surface-200 bg-surface-50"><img v-if="data.image_url" :src="data.image_url" :alt="data.product_name" class="h-full w-full object-cover" /><i v-else class="pi pi-image text-surface-400" /></div></template></Column>
                    <Column field="product_name" header="نام محصول" sortable style="min-width: 18rem"><template #body="{ data }"><div class="space-y-1"><Link v-if="data.product_url" :href="data.product_url" class="font-semibold text-primary hover:underline">{{ data.product_name }}</Link><span v-else class="font-semibold">{{ data.product_name }}</span><Tag v-if="data.is_deleted" value="محصول حذف‌شده" severity="secondary" /></div></template></Column>
                    <Column field="product_sku" header="SKU" sortable><template #body="{ data }">{{ data.product_sku || '-' }}</template></Column>
                    <Column field="quantity_purchased" header="تعداد خرید" sortable><template #body="{ data }">{{ number(data.quantity_purchased) }}</template></Column>
                    <Column field="orders_count" header="تعداد سفارش" sortable><template #body="{ data }">{{ number(data.orders_count) }}</template></Column>
                    <Column field="gross_amount" header="ناخالص" sortable><template #body="{ data }">{{ money(data.gross_amount) }}</template></Column>
                    <Column field="discount_amount" header="تخفیف" sortable><template #body="{ data }">{{ money(data.discount_amount) }}</template></Column>
                    <Column field="net_amount" header="خالص" sortable><template #body="{ data }">{{ money(data.net_amount) }}</template></Column>
                    <Column field="average_unit_price" header="میانگین واحد" sortable><template #body="{ data }">{{ money(data.average_unit_price) }}</template></Column>
                    <Column field="first_purchased_at" header="اولین خرید" sortable><template #body="{ data }">{{ formatJalaliDateTime(data.first_purchased_at) }}</template></Column>
                    <Column field="last_purchased_at" header="آخرین خرید" sortable><template #body="{ data }">{{ formatJalaliDateTime(data.last_purchased_at) }}</template></Column>
                    <Column header="عملیات" frozen alignFrozen="left"><template #body="{ data }"><Button icon="pi pi-list" label="سفارش‌ها" text severity="info" @click="filterOrdersByProduct(data)" /></template></Column>
                </DataTable>

                <DataTable
                    v-else-if="activeView === 'payments'"
                    :value="paymentRows?.data || []"
                    dataKey="id"
                    lazy
                    paginator
                    removableSort
                    scrollable
                    stripedRows
                    showGridlines
                    :loading="loading"
                    :first="firstRow"
                    :rows="paymentRows?.per_page || filters.perPage"
                    :totalRecords="paymentRows?.total || 0"
                    :rowsPerPageOptions="perPageOptions"
                    @page="onPage"
                    @sort="onPaymentSort"
                >
                    <template #empty>پرداخت موفقی برای این مشتری پیدا نشد.</template>
                    <Column header="ردیف" style="width: 5rem"><template #body="{ index }">{{ (firstRow + index + 1).toLocaleString('fa-IR') }}</template></Column>
                    <Column field="order_number" header="شماره سفارش" sortable><template #body="{ data }"><Link :href="data.order_url" class="text-primary hover:underline">{{ data.order_number }}</Link></template></Column>
                    <Column field="method" header="روش پرداخت" sortable><template #body="{ data }">{{ paymentMethodLabel(data.method) }}</template></Column>
                    <Column field="amount" header="مبلغ" sortable><template #body="{ data }">{{ money(data.amount) }}</template></Column>
                    <Column field="transaction_id" header="شناسه تراکنش" sortable><template #body="{ data }">{{ data.transaction_id || '-' }}</template></Column>
                    <Column field="reference_id" header="شماره مرجع" sortable><template #body="{ data }">{{ data.reference_id || '-' }}</template></Column>
                    <Column field="paid_at" header="تاریخ پرداخت" sortable><template #body="{ data }">{{ formatJalaliDateTime(data.paid_at) }}</template></Column>
                    <Column field="status" header="وضعیت" sortable><template #body="{ data }"><Tag value="پرداخت شده" severity="success" /></template></Column>
                    <Column header="عملیات" frozen alignFrozen="left"><template #body="{ data }"><Link :href="data.order_url"><Button icon="pi pi-eye" rounded text severity="info" /></Link></template></Column>
                </DataTable>

                <div
                    v-else-if="activeView === 'timeline'"
                    class="rounded-xl border border-surface-200 bg-white p-4"
                >
                    <div v-if="loading" class="py-12 text-center text-surface-500">در حال بارگذاری خط زمانی...</div>
                    <div v-else-if="!timelineRows?.data?.length" class="py-12 text-center text-surface-500">خط زمانی خرید قطعی برای این مشتری وجود ندارد.</div>
                    <template v-else>
                        <Timeline :value="timelineRows.data" align="alternate" class="customer-purchase-timeline">
                            <template #marker>
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-white shadow-sm">
                                    <i class="pi pi-shopping-bag" />
                                </span>
                            </template>
                            <template #content="{ item }">
                                <div class="mb-4 rounded-xl border border-surface-200 bg-surface-50 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <Link :href="item.order_url" class="font-bold text-primary hover:underline">{{ item.order_number }}</Link>
                                            <p class="mt-1 text-sm text-surface-500">{{ formatJalaliDateTime(item.paid_at) }}</p>
                                        </div>
                                        <Tag :value="statusLabel(item.status)" :severity="statusSeverity(item.status)" />
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                                        <span>مبلغ: <strong>{{ money(item.total_amount) }}</strong></span>
                                        <span>اقلام: <strong>{{ number(item.items_count) }}</strong></span>
                                        <span>پرداخت: <strong>{{ paymentMethodLabel(item.payment_method) }}</strong></span>
                                        <Link :href="item.order_url" class="text-primary hover:underline">مشاهده سفارش</Link>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <Tag v-for="product in item.products" :key="product" :value="product" severity="secondary" />
                                    </div>
                                </div>
                            </template>
                        </Timeline>
                        <Paginator
                            :first="timelineRows ? ((timelineRows.current_page - 1) * timelineRows.per_page) : 0"
                            :rows="timelineRows?.per_page || 10"
                            :totalRecords="timelineRows?.total || 0"
                            :rowsPerPageOptions="[10, 20, 50]"
                            @page="onTimelinePage"
                        />
                    </template>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex justify-end">
                        <Select :modelValue="filters.chartGrouping" :options="chartGroupingOptions" optionLabel="label" optionValue="value" class="w-40" @update:modelValue="changeChartGrouping" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">روند مبلغ خرید</h3><Chart v-if="analyticsData?.purchase_amount_trend?.length" type="line" :data="purchaseAmountChart" :options="chartOptions" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">روند تعداد سفارش‌ها</h3><Chart v-if="analyticsData?.order_count_trend?.length" type="bar" :data="orderCountChart" :options="chartOptions" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">روند تعداد اقلام</h3><Chart v-if="analyticsData?.items_quantity_trend?.length" type="bar" :data="itemsTrendChart" :options="chartOptions" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">کالاهای محبوب مشتری</h3><Chart v-if="analyticsData?.favorite_products?.length" type="bar" :data="favoriteProductsChart" :options="chartOptions" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">بیشترین هزینه روی کالاها</h3><Chart v-if="analyticsData?.top_spent_products?.length" type="bar" :data="topSpentProductsChart" :options="chartOptions" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                        <div class="rounded-xl border border-surface-200 bg-white p-4"><h3 class="mb-3 font-bold">سهم روش‌های پرداخت</h3><Chart v-if="analyticsData?.payment_methods?.length" type="doughnut" :data="paymentMethodChart" :options="{ responsive: true, maintainAspectRatio: false }" class="h-80" /><div v-else class="py-12 text-center text-surface-500">داده‌ای برای نمودار وجود ندارد.</div></div>
                    </div>
                    <div class="rounded-xl border border-surface-200 bg-white p-4 text-sm text-surface-600">
                        میانگین فاصله بین خریدها:
                        <strong class="text-surface-900">{{ analyticsData?.average_days_between_orders !== null && analyticsData?.average_days_between_orders !== undefined ? `${analyticsData.average_days_between_orders} روز` : 'برای محاسبه حداقل دو خرید لازم است' }}</strong>
                    </div>

                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                        <div class="rounded-xl border border-surface-200 bg-white p-4">
                            <h3 class="mb-3 font-bold">شاخص‌های تکمیلی مشتری</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between"><span class="text-surface-500">ماه‌های فعال</span><strong>{{ number(additionalInsights.active_months) }}</strong></div>
                                <div class="flex justify-between"><span class="text-surface-500">بیشترین سفارش</span><strong>{{ money(additionalInsights.max_order_total) }}</strong></div>
                                <div class="flex justify-between"><span class="text-surface-500">کمترین سفارش</span><strong>{{ money(additionalInsights.min_order_total) }}</strong></div>
                                <div class="flex justify-between"><span class="text-surface-500">محصول محبوب تعدادی</span><strong>{{ additionalInsights.favorite_product_by_quantity || '-' }}</strong></div>
                                <div class="flex justify-between"><span class="text-surface-500">محصول محبوب مبلغی</span><strong>{{ additionalInsights.favorite_product_by_amount || '-' }}</strong></div>
                                <div class="flex justify-between"><span class="text-surface-500">روش پرداخت محبوب</span><strong>{{ paymentMethodLabel(additionalInsights.favorite_payment_method) }}</strong></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-surface-200 bg-white p-4 xl:col-span-2">
                            <h3 class="mb-3 font-bold">کالاهای پرخرید مشتری</h3>
                            <div v-if="frequentlyPurchasedProducts.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div v-for="product in frequentlyPurchasedProducts.slice(0, 6)" :key="`${product.product_id}-${product.product_variant_id}-${product.product_sku}`" class="flex gap-3 rounded-lg border border-surface-200 p-3">
                                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-lg bg-surface-100">
                                        <img v-if="product.image_url" :src="product.image_url" :alt="product.product_name" class="h-full w-full object-cover" />
                                        <i v-else class="pi pi-image text-surface-400" />
                                    </div>
                                    <div class="min-w-0 flex-1 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold">{{ product.product_name }}</span>
                                            <Tag v-if="product.is_deleted" value="حذف‌شده" severity="secondary" />
                                        </div>
                                        <p class="mt-1 text-xs text-surface-500">{{ product.product_sku || '-' }}</p>
                                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-surface-600">
                                            <span>{{ number(product.quantity_purchased) }} عدد</span>
                                            <span>{{ number(product.orders_count) }} سفارش</span>
                                            <span>{{ money(product.net_amount) }}</span>
                                            <span>{{ product.days_since_last_purchase }} روز از آخرین خرید</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center text-sm text-surface-500">داده کافی برای کالاهای پرخرید وجود ندارد.</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <div class="rounded-xl border border-surface-200 bg-white p-4">
                            <h3 class="mb-3 font-bold">پیشنهاد خرید بعدی</h3>
                            <div v-if="nextPurchaseSuggestions.length" class="space-y-3">
                                <div v-for="item in nextPurchaseSuggestions" :key="item.group_key" class="rounded-lg border border-surface-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold">{{ item.product_name }}</p>
                                            <p class="mt-1 text-xs text-surface-500">{{ item.product_sku || '-' }}</p>
                                        </div>
                                        <Tag :value="item.priority" :severity="item.priority === 'high' ? 'danger' : item.priority === 'medium' ? 'warn' : 'info'" />
                                    </div>
                                    <p class="mt-2 text-xs leading-6 text-surface-600">{{ item.reason }}</p>
                                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-surface-500">
                                        <span>آخرین خرید: {{ formatJalaliDateTime(item.last_purchased_at) }}</span>
                                        <span>فاصله معمول: {{ item.average_interval_days }} روز</span>
                                        <span>گذشته: {{ item.days_since_last_purchase }} روز</span>
                                    </div>
                                    <Link v-if="item.product_url" :href="item.product_url" class="mt-2 inline-flex text-xs text-primary hover:underline">مشاهده محصول</Link>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center text-sm text-surface-500">فعلاً پیشنهاد قابل اتکایی برای خرید بعدی وجود ندارد.</div>
                        </div>

                        <div class="rounded-xl border border-surface-200 bg-white p-4">
                            <h3 class="mb-3 font-bold">کالاهای نیازمند یادآوری خرید مجدد</h3>
                            <div v-if="reminderProducts.length" class="space-y-3">
                                <div v-for="item in reminderProducts" :key="item.group_key" class="rounded-lg border border-surface-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold">{{ item.product_name }}</p>
                                            <p class="mt-1 text-xs text-surface-500">{{ item.product_sku || '-' }}</p>
                                        </div>
                                        <Tag :value="item.reminder_status" :severity="item.severity" />
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-surface-600">
                                        <span>دفعات سفارش: {{ number(item.orders_count) }}</span>
                                        <span>تعداد کل: {{ number(item.quantity_purchased) }}</span>
                                        <span>اولین خرید: {{ formatJalaliDateTime(item.first_purchased_at) }}</span>
                                        <span>آخرین خرید: {{ formatJalaliDateTime(item.last_purchased_at) }}</span>
                                        <span>میانگین فاصله: {{ item.average_interval_days }} روز</span>
                                        <span>نسبت تأخیر: {{ item.overdue_ratio }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center text-sm text-surface-500">کالای تکرارشونده‌ای برای یادآوری پیدا نشد.</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <Dialog v-model:visible="isFilterDialogOpen" modal dismissableMask header="فیلتر کارتکس مشتری" class="w-[94vw] max-w-4xl" :breakpoints="{ '960px': '92vw', '640px': '96vw' }">
            <form class="space-y-5" @submit.prevent="applyFilters">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">بازه سریع</label><Select :modelValue="draftFilters.quickRange" :options="quickRangeOptions" optionLabel="label" optionValue="value" class="w-full" @update:modelValue="(value) => { draftFilters.quickRange = value; if (value) { draftFilters.dateFrom = ''; draftFilters.dateTo = ''; } }" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">تعداد نمایش</label><Select v-model="draftFilters.perPage" :options="perPageOptions" class="w-full" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">تاریخ از</label><PersianDatePicker v-model="draftFilters.dateFrom" placeholder="از تاریخ پرداخت" @update:modelValue="draftFilters.quickRange = null" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">تاریخ تا</label><PersianDatePicker v-model="draftFilters.dateTo" placeholder="تا تاریخ پرداخت" @update:modelValue="draftFilters.quickRange = null" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">شماره سفارش</label><InputText v-model="draftFilters.orderNumber" class="w-full" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">نام یا SKU محصول</label><InputText v-model="draftFilters.productSearch" class="w-full" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">روش پرداخت</label><Select v-model="draftFilters.paymentMethod" :options="paymentMethodFilters" optionLabel="label" optionValue="value" class="w-full" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">وضعیت سفارش</label><Select v-model="draftFilters.orderStatus" :options="orderStatusFilters" optionLabel="label" optionValue="value" class="w-full" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">حداقل مبلغ سفارش</label><InputNumber v-model="draftFilters.minAmount" class="w-full" inputClass="w-full" :min="0" /></div>
                    <div><label class="mb-2 block text-sm font-medium text-surface-700">حداکثر مبلغ سفارش</label><InputNumber v-model="draftFilters.maxAmount" class="w-full" inputClass="w-full" :min="0" /></div>
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
