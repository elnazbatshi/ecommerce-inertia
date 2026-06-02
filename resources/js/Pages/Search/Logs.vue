<script setup>
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatJalaliDateTime, toPersianDigits } from '@/Utils/persianDate';
import { Head, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] },
    matchedTypes: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const query = ref(props.filters.query ?? '');
const type = ref(props.filters.type ?? null);
const matchedType = ref(props.filters.matched_type ?? null);
const dateFrom = ref(props.filters.from ?? '');
const dateTo = ref(props.filters.to ?? '');
const noResult = ref(Boolean(props.filters.no_result));
const rows = ref(Number(props.filters.rows ?? props.logs.per_page ?? 30));
const timeout = ref();

const typeOptions = computed(() => [{ label: 'همه نوع‌ها', value: null }, ...props.types]);
const matchedTypeOptions = computed(() => [{ label: 'همه Matchها', value: null }, ...props.matchedTypes]);
const typeLabels = computed(() => Object.fromEntries(props.types.map((item) => [item.value, item.label])));
const matchedTypeLabels = computed(() => Object.fromEntries(props.matchedTypes.map((item) => [item.value, item.label])));

const statCards = computed(() => [
    { label: 'کل سرچ‌ها', value: toPersianDigits(props.stats.total ?? 0), icon: 'pi pi-search', severity: 'info' },
    { label: 'پرتکرارترین عبارت', value: props.stats.top_query || '-', icon: 'pi pi-chart-line', severity: 'success' },
    { label: 'سرچ‌های بدون نتیجه', value: toPersianDigits(props.stats.no_result ?? 0), icon: 'pi pi-filter-slash', severity: 'warn' },
    { label: 'محبوب‌ترین برند', value: props.stats.top_brand || '-', icon: 'pi pi-tag', severity: 'secondary' },
    { label: 'محبوب‌ترین دسته‌بندی', value: props.stats.top_category || '-', icon: 'pi pi-folder', severity: 'contrast' }
]);

const load = (extra = {}) => {
    router.get('/admin/search/logs', {
        query: query.value || undefined,
        type: type.value || undefined,
        matched_type: matchedType.value || undefined,
        from: dateFrom.value || undefined,
        to: dateTo.value || undefined,
        no_result: noResult.value ? 1 : undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([type, matchedType, dateFrom, dateTo, noResult], () => load({ page: 1 }));
watch(query, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const removeLog = (row) => {
    confirm.require({
        message: `لاگ جستجوی «${row.query}» حذف شود؟`,
        header: 'حذف لاگ',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/search/logs/${row.id}`, { preserveScroll: true })
    });
};

const shortUserAgent = (value) => {
    if (!value) return '-';

    return value.length > 64 ? `${value.slice(0, 64)}...` : value;
};
</script>

<template>
    <Head title="لاگ‌های سرچ" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="گزارش جستجوها" :breadcrumb="[{ label: 'جستجو' }, { label: 'لاگ‌ها' }]" />

        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
            <div v-for="card in statCards" :key="card.label" class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="mb-2 text-sm text-surface-500">{{ card.label }}</p>
                        <strong class="block truncate text-xl font-semibold text-surface-900">{{ card.value }}</strong>
                    </div>
                    <Tag :severity="card.severity" class="h-10 w-10 justify-center rounded-md">
                        <i :class="card.icon" />
                    </Tag>
                </div>
            </div>
        </div>

        <div class="card">
            <DataTable
                :value="logs.data"
                dataKey="id"
                lazy
                paginator
                :first="(logs.current_page - 1) * logs.per_page"
                :rows="logs.per_page"
                :totalRecords="logs.total"
                :rowsPerPageOptions="[10, 30, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3 xl:grid-cols-6">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="query" class="w-full" placeholder="عبارت جستجو" />
                        </IconField>
                        <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="matchedType" :options="matchedTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <PersianDatePicker v-model="dateFrom" placeholder="از تاریخ" />
                        <PersianDatePicker v-model="dateTo" placeholder="تا تاریخ" />
                        <div class="flex items-center gap-2 rounded-md border border-surface-200 px-3 py-2">
                            <ToggleSwitch v-model="noResult" />
                            <span class="text-sm">فقط بدون نتیجه</span>
                        </div>
                    </div>
                </template>

                <template #empty>لاگی پیدا نشد.</template>

                <Column field="query" header="عبارت" style="min-width: 12rem" />
                <Column field="type" header="نوع سرچ" style="width: 10rem">
                    <template #body="{ data }">
                        <Tag :value="typeLabels[data.type] ?? data.type" severity="info" />
                    </template>
                </Column>
                <Column header="آیتم match شده" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div v-if="data.matched_type || data.matched_id" class="flex flex-col gap-1">
                            <Tag v-if="data.matched_type" :value="matchedTypeLabels[data.matched_type] ?? data.matched_type" severity="secondary" />
                            <small v-if="data.matched_id" class="text-surface-500">ID: {{ toPersianDigits(data.matched_id) }}</small>
                        </div>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column field="results_count" header="تعداد نتیجه" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag
                            :value="toPersianDigits(data.results_count ?? 0)"
                            :severity="Number(data.results_count) > 0 ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column header="user" style="min-width: 10rem">
                    <template #body="{ data }">{{ data.user?.name || '-' }}</template>
                </Column>
                <Column field="ip_address" header="ip" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span dir="ltr" class="block text-left">{{ data.ip_address || '-' }}</span>
                    </template>
                </Column>
                <Column field="searched_at" header="searched_at شمسی" style="min-width: 10rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.searched_at) }}</template>
                </Column>
                <Column field="user_agent" header="user_agent خلاصه" style="min-width: 16rem">
                    <template #body="{ data }">
                        <span dir="ltr" class="block max-w-80 truncate text-left">{{ shortUserAgent(data.user_agent) }}</span>
                    </template>
                </Column>
                <Column header="عملیات" style="width: 6rem">
                    <template #body="{ data }">
                        <div class="flex justify-center">
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="removeLog(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
