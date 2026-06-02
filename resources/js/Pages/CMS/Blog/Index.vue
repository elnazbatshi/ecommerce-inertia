<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] }
});

const confirm = useConfirm();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const timeout = ref();
const statusFilters = computed(() => [{ label: 'همه وضعیت‌ها', value: null }, ...props.statusOptions]);

const load = (extra = {}) => {
    router.get('/admin/posts', {
        search: search.value || undefined,
        status: status.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        ...extra
    }, {
        preserveState: true,
        replace: true
    });
};

watch([status, dateFrom, dateTo], () => load({ page: 1 }));
watch(search, () => {
    clearTimeout(timeout.value);
    timeout.value = setTimeout(() => load({ page: 1 }), 450);
});

const destroyItem = (post) => {
    confirm.require({
        message: `مقاله «${post.title}» حذف شود؟`,
        header: 'حذف مقاله',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/posts/${post.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="مقالات" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مقالات" :breadcrumb="[{ label: 'محتوا' }, { label: 'مقالات' }]">
            <template #pageAction><Link href="/admin/posts/create"><Button label="مقاله جدید" icon="pi pi-plus" /></Link></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="posts.data" dataKey="id" showGridlines>
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="search" class="w-full" placeholder="جستجو عنوان یا نامک" />
                        </IconField>
                        <Select v-model="status" :options="statusFilters" optionLabel="label" optionValue="value" class="w-full" />
                        <PersianDatePicker v-model="dateFrom" placeholder="از تاریخ انتشار" />
                        <PersianDatePicker v-model="dateTo" placeholder="تا تاریخ انتشار" />
                    </div>
                </template>
                <Column field="title" header="عنوان" />
                <Column field="slug" header="نامک" />
                <Column header="دسته‌بندی"><template #body="{ data }">{{ data.category?.name }}</template></Column>
                <Column field="status" header="وضعیت"><template #body="{ data }"><Tag :value="data.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'" :severity="data.status === 'published' ? 'success' : 'warn'" /></template></Column>
                <Column field="published_at" header="زمان انتشار" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.published_at) }}</template>
                </Column>
                <Column field="view_count" header="بازدید" />
                <Column header="سئو"><template #body="{ data }"><Tag :value="data.seo_index ? 'قابل نمایه‌سازی' : 'غیرقابل نمایه‌سازی'" :severity="data.seo_index ? 'success' : 'danger'" /></template></Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <Link :href="`/admin/posts/${data.slug}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                        <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                    </template>
                </Column>
            </DataTable>
            <Paginator v-if="posts.total > posts.per_page" :rows="posts.per_page" :totalRecords="posts.total" :first="(posts.current_page - 1) * posts.per_page" @page="load({ page: $event.page + 1 })" />
        </div>
    </AppLayout>
</template>
