<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

defineProps({
    pages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const confirm = useConfirm();
const destroyItem = (page) => {
    confirm.require({
        message: `صفحه «${page.title}» حذف شود؟`,
        header: 'حذف صفحه',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/pages/${page.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="صفحات" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="صفحات مدیریت محتوا" :breadcrumb="[{ label: 'محتوا' }, { label: 'صفحات' }]">
            <template #pageAction><Link href="/pages/create"><Button label="صفحه جدید" icon="pi pi-plus" /></Link></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="pages.data" dataKey="id" showGridlines>
                <Column field="title" header="عنوان" />
                <Column field="slug" header="نامک" />
                <Column field="template" header="قالب" />
                <Column field="status" header="وضعیت"><template #body="{ data }"><Tag :value="data.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'" :severity="data.status === 'published' ? 'success' : 'warn'" /></template></Column>
                <Column header="سئو"><template #body="{ data }"><Tag :value="data.seo_index ? 'قابل نمایه‌سازی' : 'غیرقابل نمایه‌سازی'" :severity="data.seo_index ? 'success' : 'danger'" /></template></Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <Link :href="`/pages/${data.slug}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                        <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                    </template>
                </Column>
            </DataTable>
            <Paginator v-if="pages.total > pages.per_page" :rows="pages.per_page" :totalRecords="pages.total" :first="(pages.current_page - 1) * pages.per_page" @page="router.get('/pages', { page: $event.page + 1 }, { preserveState: true })" />
        </div>
    </AppLayout>
</template>
