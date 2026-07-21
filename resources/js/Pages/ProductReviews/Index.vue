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
const selectedReview = ref(null);
const reviewDialogVisible = ref(false);

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

const closeReviewDialog = () => {
    reviewDialogVisible.value = false;
    selectedReview.value = null;
};

const approveReview = (review) => {
    if (!review) return;
    router.patch(`/admin/product-reviews/${review.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => closeReviewDialog(),
    });
};

const rejectReview = (review) => {
    if (!review) return;
    router.patch(`/admin/product-reviews/${review.id}/reject`, {}, {
        preserveScroll: true,
        onSuccess: () => closeReviewDialog(),
    });
};

const openReviewDialog = (review) => {
    selectedReview.value = review;
    reviewDialogVisible.value = true;
};

const deleteReview = (review) => {
    if (!review) return;

    confirm.require({
        message: 'این نظر حذف شود؟',
        header: 'حذف نظر',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/product-reviews/${review.id}`, {
            preserveScroll: true,
            onSuccess: () => closeReviewDialog(),
        }),
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
                        <Link v-if="data.product?.slug" :href="`/admin/products/${data.product.slug}/edit`" class="font-bold text-primary hover:underline">
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

                <Column header="عملیات" style="width: 14rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Button icon="pi pi-eye" rounded text severity="info" aria-label="مشاهده" @click="openReviewDialog(data)" />
                            <Button icon="pi pi-check" rounded text severity="success" aria-label="تایید" :disabled="data.status === 'approved'" @click="approveReview(data)" />
                            <Button icon="pi pi-times" rounded text severity="warning" aria-label="رد" :disabled="data.status === 'rejected'" @click="rejectReview(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" aria-label="حذف" @click="deleteReview(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="reviewDialogVisible"
            modal
            header="مشاهده نظر مشتری"
            :style="{ width: '900px', maxWidth: '96vw' }"
            @hide="closeReviewDialog"
        >
            <div v-if="selectedReview" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <template #title>اطلاعات مشتری</template>
                        <template #content>
                            <dl class="review-info-list">
                                <div><dt>نام مشتری</dt><dd>{{ selectedReview.customer?.name || 'ثبت نشده' }}</dd></div>
                                <div><dt>شماره موبایل</dt><dd dir="ltr">{{ selectedReview.customer?.phone || 'ثبت نشده' }}</dd></div>
                                <div><dt>تاریخ ثبت نظر</dt><dd>{{ selectedReview.created_at ? formatJalaliDateTime(selectedReview.created_at) : 'ثبت نشده' }}</dd></div>
                                <div>
                                    <dt>وضعیت فعلی</dt>
                                    <dd><Tag :value="statusMap[selectedReview.status]?.label ?? selectedReview.status" :severity="statusMap[selectedReview.status]?.severity ?? 'secondary'" /></dd>
                                </div>
                                <div><dt>خرید تایید شده</dt><dd>{{ selectedReview.is_buyer ? 'بله' : 'خیر' }}</dd></div>
                            </dl>
                        </template>
                    </Card>

                    <Card>
                        <template #title>اطلاعات محصول</template>
                        <template #content>
                            <div class="flex gap-4">
                                <div class="min-w-0">
                                    <h3 class="m-0 text-lg font-black text-surface-900">{{ selectedReview.product?.name || 'ثبت نشده' }}</h3>
                                    <Link v-if="selectedReview.product?.slug" :href="`/admin/products/${selectedReview.product.slug}/edit`" class="mt-3 inline-flex">
                                        <Button label="رفتن به محصول" icon="pi pi-external-link" size="small" outlined />
                                    </Link>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <Card>
                    <template #content>
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="m-0 text-base font-black text-surface-900">امتیاز</h3>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xl text-amber-500">
                                        <i v-for="star in 5" :key="star" :class="star <= selectedReview.rating ? 'pi pi-star-fill' : 'pi pi-star'" />
                                    </span>
                                    <span class="font-bold text-surface-700">{{ selectedReview.rating }} از 5</span>
                                </div>
                            </div>
                            <div v-if="selectedReview.title" class="md:text-left">
                                <h3 class="m-0 text-base font-black text-surface-900">عنوان نظر</h3>
                                <p class="m-0 mt-2 text-surface-700">{{ selectedReview.title }}</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Panel header="متن کامل نظر">
                    <div class="max-h-56 overflow-y-auto whitespace-pre-line leading-8 text-surface-700">
                        {{ selectedReview.comment || 'ثبت نشده' }}
                    </div>
                </Panel>
            </div>

            <template #footer>
                <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button label="بستن" severity="secondary" outlined @click="closeReviewDialog" />
                    <Button label="تایید" icon="pi pi-check" severity="success" :disabled="selectedReview?.status === 'approved'" @click="approveReview(selectedReview)" />
                    <Button label="رد" icon="pi pi-times" severity="warning" :disabled="selectedReview?.status === 'rejected'" @click="rejectReview(selectedReview)" />
                    <Button label="حذف" icon="pi pi-trash" severity="danger" @click="deleteReview(selectedReview)" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.review-info-list {
    display: grid;
    gap: 0.75rem;
}

.review-info-list > div {
    display: grid;
    grid-template-columns: minmax(7rem, 0.45fr) minmax(0, 1fr);
    gap: 0.75rem;
    border-bottom: 1px solid var(--surface-border);
    padding-bottom: 0.75rem;
}

.review-info-list > div:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.review-info-list dt {
    color: var(--text-color-secondary);
    font-size: 0.875rem;
    font-weight: 700;
}

.review-info-list dd {
    margin: 0;
    font-weight: 800;
}

@media (max-width: 640px) {
    .review-info-list > div {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }
}
</style>
