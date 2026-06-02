<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    sliders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const rows = ref(Number(props.filters.rows ?? props.sliders.per_page ?? 10));
const timeout = ref();

const statusOptions = computed(() => [{ label: 'همه وضعیت‌ها', value: null }, ...props.statuses]);
const layoutLabels = {
    image_left_content_right: 'تصویر چپ، محتوا راست',
    image_right_content_left: 'محتوا چپ، تصویر راست',
    content_center: 'محتوا وسط'
};

const load = (extra = {}) => {
    router.get('/admin/hero-sliders', {
        search: search.value || undefined,
        status: status.value || undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch(status, () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const destroySlider = (slider) => {
    confirm.require({
        message: `اسلایدر «${slider.title}» حذف شود؟`,
        header: 'حذف اسلایدر',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/hero-sliders/${slider.id}`, { preserveScroll: true })
    });
};

const toggleStatus = (slider) => {
    router.patch(`/admin/hero-sliders/${slider.id}/toggle-status`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="اسلایدر صفحه اصلی" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="اسلایدر صفحه اصلی" :breadcrumb="[{ label: 'مدیریت سایت' }, { label: 'اسلایدر صفحه اصلی' }]">
            <template #pageAction>
                <Link href="/admin/hero-sliders/create">
                    <Button label="اسلایدر جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="sliders.data"
                dataKey="id"
                lazy
                paginator
                :first="(sliders.current_page - 1) * sliders.per_page"
                :rows="sliders.per_page"
                :totalRecords="sliders.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو در عنوان و زیرعنوان" />
                        </IconField>
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>اسلایدی پیدا نشد.</template>

                <Column header="تصویر" style="width: 7rem">
                    <template #body="{ data }">
                        <img
                            v-if="data.background_media?.url || data.foreground_media?.url"
                            :src="data.foreground_media?.url || data.background_media?.url"
                            class="h-14 w-14 rounded-md object-cover"
                            :alt="data.title"
                        />
                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-md bg-surface-100 text-surface-400">
                            <i class="pi pi-image" />
                        </div>
                    </template>
                </Column>
                <Column field="title" header="عنوان" style="min-width: 14rem" />
                <Column header="Badge" style="min-width: 9rem">
                    <template #body="{ data }">{{ data.badge_text || '-' }}</template>
                </Column>
                <Column header="چینش" style="min-width: 12rem">
                    <template #body="{ data }">{{ layoutLabels[data.layout] ?? data.layout }}</template>
                </Column>
                <Column field="sort_order" header="ترتیب" style="width: 7rem" />
                <Column header="زمان‌بندی" style="min-width: 13rem">
                    <template #body="{ data }">
                        <div class="text-sm">
                            <div>شروع: {{ formatJalaliDateTime(data.starts_at) }}</div>
                            <div>پایان: {{ formatJalaliDateTime(data.ends_at) }}</div>
                        </div>
                    </template>
                </Column>
                <Column field="is_active" header="وضعیت" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <ToggleSwitch :model-value="data.is_active" @change="toggleStatus(data)" />
                            <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                        </div>
                    </template>
                </Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/hero-sliders/${data.id}/edit`">
                                <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" />
                            </Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="destroySlider(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
