<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    reviews: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const status = ref(props.filters.status ?? null);
const rows = ref(Number(props.filters.rows ?? props.reviews.per_page ?? 10));

const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));
const statusFilters = computed(() => [{ label: 'همه وضعیت‌ها', value: null, severity: 'secondary' }, ...props.statusOptions]);

const load = (extra = {}) => {
    router.get('/admin/product-reviews', {
        status: status.value || undefined,
        rows: rows.value,
        ...extra,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(status, () => load({ page: 1 }));

const onPage = (event) => {
    rows.value = event.rows;
    load({ page: event.page + 1 });
};

const approveReview = (review) => {
    router.patch(`/admin/product-reviews/${review.id}/approve`, {}, { preserveScroll: true });
};

const rejectReview = (review) => {
    router.patch(`/admin/product-reviews/${review.id}/reject`, {}, { preserveScroll: true });
};

const deleteReview = (review) => {
    confirm.require({
        message: 'این نظر حذف شود؟',
        header: 'حذف نظر',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/product-reviews/${review.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head title="نظرات محصولات">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="نظرات محصولات" :breadcrumb="[{ label: 'نظرات محصولات' }]" />

        <div class="card">
            <DataTable
                :value="reviews.data"
                dataKey="id"
                lazy
                paginator
                :first="(reviews.current_page - 1) * reviews.per_page"
                :rows="reviews.per_page"
                :totalRecords="reviews.total"
                :rowsPerPageOptions="[10, 20, 50]"
                showGridlines
                @page="onPage"
            >
                <template #header>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <Select v-model="status" :options="statusFilters" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </template>

                <template #empty>نظری یافت نشد.</template>

                <Column field="product.name" header="محصول" style="min-width: 14rem">
                    <template #body="{ data }">
                        <Link v-if="data.product" :href="`/admin/products/${data.product.slug}/edit`" class="font-bold text-primary hover:underline">
                            {{ data.product.name }}
                        </Link>
                        <span v-else>-</span>
                    </template>
                </Column>

                <Column header="مشتری" style="min-width: 11rem">
                    <template #body="{ data }">
                        <div>{{ data.customer?.name || '-' }}</div>
                        <small class="text-surface-500">{{ data.customer?.phone || '-' }}</small>
                    </template>
                </Column>

                <Column field="rating" header="امتیاز" style="width: 8rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1 text-amber-500">
                            <i v-for="star in 5" :key="star" :class="star <= data.rating ? 'pi pi-star-fill' : 'pi pi-star'" />
                        </div>
                    </template>
                </Column>

                <Column header="نظر" style="min-width: 22rem">
                    <template #body="{ data }">
                        <div class="font-bold">{{ data.title || 'بدون عنوان' }}</div>
                        <p class="mt-1 line-clamp-3 text-sm text-surface-600">{{ data.comment }}</p>
                        <Tag v-if="data.is_buyer" class="mt-2" value="خریدار این محصول" severity="success" />
                    </template>
                </Column>

                <Column field="status" header="وضعیت" style="width: 9rem">
                    <template #body="{ data }">
                        <Tag :value="statusMap[data.status]?.label ?? data.status" :severity="statusMap[data.status]?.severity ?? 'secondary'" />
                    </template>
                </Column>

                <Column field="created_at" header="تاریخ" style="min-width: 11rem">
                    <template #body="{ data }">{{ formatJalaliDateTime(data.created_at) }}</template>
                </Column>

                <Column header="عملیات" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Button icon="pi pi-check" rounded text severity="success" aria-label="تایید" :disabled="data.status === 'approved'" @click="approveReview(data)" />
                            <Button icon="pi pi-times" rounded text severity="warning" aria-label="رد" :disabled="data.status === 'rejected'" @click="rejectReview(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="deleteReview(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
