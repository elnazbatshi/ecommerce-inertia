<script setup>
import PersianDateTimePicker from '@/Components/Date/PersianDateTimePicker.vue';
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    suggestions: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }
});

const confirm = useConfirm();
const toast = useToast();
const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? null);
const status = ref(props.filters.status ?? null);
const rows = ref(Number(props.filters.rows ?? props.suggestions.per_page ?? 20));
const timeout = ref();
const showDialog = ref(false);
const editing = ref(null);
const selectedReference = ref(null);
const referenceSuggestions = ref([]);

const typeOptions = computed(() => [{ label: 'همه نوع‌ها', value: null }, ...props.types]);
const statusOptions = computed(() => [{ label: 'همه وضعیت‌ها', value: null }, ...props.statuses]);
const typeLabels = computed(() => Object.fromEntries(props.types.map((item) => [item.value, item.label])));

const form = useForm({
    title: '',
    type: 'custom',
    reference_id: null,
    url: '',
    keyword: '',
    icon: 'pi pi-search',
    sort_order: 0,
    is_active: true,
    starts_at: '',
    ends_at: ''
});

const defaultReferenceOptions = computed(() => {
    if (form.type === 'product') return props.products;
    if (form.type === 'category') return props.categories;
    if (form.type === 'brand') return props.brands;

    return [];
});

const referencePlaceholder = computed(() => {
    if (form.type === 'product') return 'انتخاب محصول';
    if (form.type === 'category') return 'انتخاب دسته‌بندی';
    if (form.type === 'brand') return 'انتخاب برند';

    return '';
});

const load = (extra = {}) => {
    router.get('/admin/search/suggestions', {
        search: search.value || undefined,
        type: type.value || undefined,
        status: status.value || undefined,
        rows: rows.value,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([type, status], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

watch(() => form.type, () => {
    selectedReference.value = null;
    referenceSuggestions.value = defaultReferenceOptions.value;
    form.reference_id = null;

    if (form.type !== 'custom') {
        form.url = '';
    }
});

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    selectedReference.value = null;
    referenceSuggestions.value = defaultReferenceOptions.value;
};

const openCreate = () => {
    editing.value = null;
    resetForm();
    showDialog.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.clearErrors();
    form.title = row.title ?? '';
    form.type = row.type ?? 'custom';
    form.reference_id = row.reference_id ?? null;
    form.url = row.url ?? '';
    form.keyword = row.keyword ?? '';
    form.icon = row.icon ?? 'pi pi-search';
    form.sort_order = Number(row.sort_order ?? 0);
    form.is_active = Boolean(row.is_active);
    form.starts_at = row.starts_at ?? '';
    form.ends_at = row.ends_at ?? '';
    selectedReference.value = row.reference ?? null;
    referenceSuggestions.value = defaultReferenceOptions.value;
    showDialog.value = true;
};

const endpointForType = () => {
    if (form.type === 'product') return '/admin/search/autocomplete/products';
    if (form.type === 'category') return '/admin/search/autocomplete/categories';
    if (form.type === 'brand') return '/admin/search/autocomplete/brands';

    return null;
};

const searchReferences = async (event) => {
    const endpoint = endpointForType();

    if (!endpoint) {
        referenceSuggestions.value = [];
        return;
    }

    const { data } = await axios.get(endpoint, { params: { query: event.query } });
    referenceSuggestions.value = data;
};

const onReferenceSelect = ({ value }) => {
    selectedReference.value = value;
    form.reference_id = value?.id ?? null;
    form.url = value?.url ?? '';

    if (value?.name) {
        form.title = value.name;
    }
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            resetForm();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'خطا', detail: 'اطلاعات فرم را بررسی کنید.', life: 4000 });
        }
    };

    if (editing.value) {
        form.put(`/admin/search/suggestions/${editing.value.id}`, options);
        return;
    }

    form.post('/admin/search/suggestions', options);
};

const removeRow = (row) => {
    confirm.require({
        message: `پیشنهاد «${row.title}» حذف شود؟`,
        header: 'حذف پیشنهاد',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/search/suggestions/${row.id}`, { preserveScroll: true })
    });
};

const toggleStatus = (row) => {
    router.patch(`/admin/search/suggestions/${row.id}/toggle`, {}, { preserveScroll: true });
};

const referenceIcon = (row) => {
    if (row.icon) return row.icon;
    if (row.type === 'product') return 'pi pi-box';
    if (row.type === 'category') return 'pi pi-folder';
    if (row.type === 'brand') return 'pi pi-tag';

    return 'pi pi-search';
};
</script>

<template>
    <Head title="پیشنهادهای سرچ" />

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مدیریت پیشنهادهای سرچ" :breadcrumb="[{ label: 'جستجو' }, { label: 'پیشنهادها' }]">
            <template #pageAction>
                <Button label="پیشنهاد جدید" icon="pi pi-plus" @click="openCreate" />
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                :value="suggestions.data"
                dataKey="id"
                lazy
                paginator
                :first="(suggestions.current_page - 1) * suggestions.per_page"
                :rows="suggestions.per_page"
                :totalRecords="suggestions.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو بر اساس عنوان" />
                        </IconField>
                        <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>پیشنهادی پیدا نشد.</template>

                <Column header="تصویر/آیکون" style="width: 7rem">
                    <template #body="{ data }">
                        <img
                            v-if="data.reference?.image_url"
                            :src="data.reference.image_url"
                            class="h-14 w-14 rounded-md object-cover"
                            :alt="data.title"
                        />
                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-md bg-surface-100 text-surface-500">
                            <i :class="referenceIcon(data)" />
                        </div>
                    </template>
                </Column>
                <Column field="title" header="عنوان" style="min-width: 14rem" />
                <Column field="type" header="نوع" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="typeLabels[data.type] ?? data.type" severity="info" />
                    </template>
                </Column>
                <Column header="آیتم متصل‌شده" style="min-width: 12rem">
                    <template #body="{ data }">{{ data.reference_title ?? '-' }}</template>
                </Column>
                <Column field="url" header="URL" style="min-width: 14rem">
                    <template #body="{ data }">
                        <span dir="ltr" class="block max-w-64 truncate text-left">{{ data.url || '-' }}</span>
                    </template>
                </Column>
                <Column field="sort_order" header="ترتیب" style="width: 7rem" />
                <Column field="is_active" header="وضعیت" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <ToggleSwitch :model-value="data.is_active" @change="toggleStatus(data)" />
                            <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                        </div>
                    </template>
                </Column>
                <Column header="عملیات" style="width: 8rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="secondary" aria-label="ویرایش" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="removeRow(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="showDialog"
            modal
            :header="editing ? 'ویرایش پیشنهاد سرچ' : 'پیشنهاد جدید'"
            :style="{ width: '48rem' }"
            class="mx-3"
        >
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium">نوع پیشنهاد</label>
                    <Select v-model="form.type" :options="props.types" optionLabel="label" optionValue="value" class="w-full" :invalid="Boolean(form.errors.type)" />
                    <small v-if="form.errors.type" class="text-red-500">{{ form.errors.type }}</small>
                </div>

                <div v-if="form.type !== 'custom'" class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium">{{ referencePlaceholder }}</label>
                    <AutoComplete
                        v-model="selectedReference"
                        :suggestions="referenceSuggestions"
                        optionLabel="name"
                        forceSelection
                        dropdown
                        class="w-full"
                        :placeholder="referencePlaceholder"
                        :invalid="Boolean(form.errors.reference_id)"
                        @complete="searchReferences"
                        @item-select="onReferenceSelect"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <img v-if="option.image_url" :src="option.image_url" class="h-8 w-8 rounded object-cover" :alt="option.name" />
                                <span v-else class="flex h-8 w-8 items-center justify-center rounded bg-surface-100 text-surface-500">
                                    <i :class="option.icon || referenceIcon({ type: form.type })" />
                                </span>
                                <div>
                                    <div>{{ option.name }}</div>
                                    <small v-if="option.meta" class="text-surface-500">{{ option.meta }}</small>
                                </div>
                            </div>
                        </template>
                    </AutoComplete>
                    <small v-if="form.errors.reference_id" class="text-red-500">{{ form.errors.reference_id }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">عنوان</label>
                    <InputText v-model="form.title" class="w-full" :invalid="Boolean(form.errors.title)" />
                    <small v-if="form.errors.title" class="text-red-500">{{ form.errors.title }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">کلمه کلیدی</label>
                    <InputText v-model="form.keyword" class="w-full" :invalid="Boolean(form.errors.keyword)" />
                    <small v-if="form.errors.keyword" class="text-red-500">{{ form.errors.keyword }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">URL</label>
                    <InputText v-model="form.url" dir="ltr" class="w-full text-left" :disabled="form.type !== 'custom'" :invalid="Boolean(form.errors.url)" />
                    <small v-if="form.errors.url" class="text-red-500">{{ form.errors.url }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">آیکون</label>
                    <InputText v-model="form.icon" dir="ltr" class="w-full text-left" :invalid="Boolean(form.errors.icon)" />
                    <small v-if="form.errors.icon" class="text-red-500">{{ form.errors.icon }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">ترتیب</label>
                    <InputNumber v-model="form.sort_order" class="w-full" inputClass="w-full" :invalid="Boolean(form.errors.sort_order)" />
                    <small v-if="form.errors.sort_order" class="text-red-500">{{ form.errors.sort_order }}</small>
                </div>

                <div class="flex items-center gap-3 pt-7">
                    <ToggleSwitch v-model="form.is_active" />
                    <span>فعال</span>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">شروع نمایش</label>
                    <PersianDateTimePicker v-model="form.starts_at" :invalid="Boolean(form.errors.starts_at)" />
                    <small v-if="form.errors.starts_at" class="text-red-500">{{ form.errors.starts_at }}</small>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">پایان نمایش</label>
                    <PersianDateTimePicker v-model="form.ends_at" :invalid="Boolean(form.errors.ends_at)" />
                    <small v-if="form.errors.ends_at" class="text-red-500">{{ form.errors.ends_at }}</small>
                </div>

                <button type="submit" class="hidden" />
            </form>

            <template #footer>
                <Button label="انصراف" severity="secondary" text :disabled="form.processing" @click="showDialog = false" />
                <Button label="ذخیره" icon="pi pi-check" :loading="form.processing" @click="submit" />
            </template>
        </Dialog>
    </AppLayout>
</template>
