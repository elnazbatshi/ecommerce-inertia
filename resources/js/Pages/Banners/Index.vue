<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    section: { type: Object, required: true },
    banners: { type: Object, required: true },
});

const confirm = useConfirm();

const destroyBanner = (banner) => {
    confirm.require({
        message: `بنر «${banner.title || banner.id}» حذف شود؟`,
        header: 'حذف بنر',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/admin/banners/${banner.id}`, { preserveScroll: true }),
    });
};
</script>

<template>
    <Head :title="`بنرهای ${section.title}`" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle :title="`بنرهای ${section.title}`" :breadcrumb="[{ label: 'بخش‌های بنر', to: '/admin/banner-sections' }, { label: section.title }]">
            <template #pageAction>
                <Link :href="`/admin/banner-sections/${section.id}/banners/create`">
                    <Button label="بنر جدید" icon="pi pi-plus" />
                </Link>
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable :value="banners.data" dataKey="id" paginator :rows="banners.per_page" :totalRecords="banners.total" showGridlines>
                <template #empty>بنری برای این بخش ثبت نشده است.</template>
                <Column header="تصویر" style="width: 9rem">
                    <template #body="{ data }">
                        <img v-if="data.image" :src="data.image" class="h-16 w-28 rounded object-cover" alt="" />
                        <span v-else class="text-sm text-surface-500">بدون تصویر</span>
                    </template>
                </Column>
                <Column field="title" header="عنوان" style="min-width: 12rem" />
                <Column field="link_url" header="لینک" style="min-width: 12rem" />
                <Column field="sort_order" header="ترتیب" style="width: 7rem" />
                <Column field="is_active" header="وضعیت" style="width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="بازه زمانی" style="min-width: 13rem">
                    <template #body="{ data }">
                        <span class="text-sm">{{ data.starts_at || 'از ابتدا' }} - {{ data.ends_at || 'بدون پایان' }}</span>
                    </template>
                </Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Link :href="`/admin/banners/${data.id}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyBanner(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
