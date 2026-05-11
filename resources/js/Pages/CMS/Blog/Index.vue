<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const confirm = useConfirm();
const destroyItem = (post) => {
    confirm.require({
        message: `مقاله «${post.title}» حذف شود؟`,
        header: 'حذف مقاله',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(`/posts/${post.slug}`, { preserveScroll: true })
    });
};
</script>

<template>
    <Head title="مقالات" />
    <AppLayout>
        <ConfirmDialog />
        <TopNavTitle title="مقالات" :breadcrumb="[{ label: 'محتوا' }, { label: 'مقالات' }]">
            <template #pageAction><Link href="/posts/create"><Button label="مقاله جدید" icon="pi pi-plus" /></Link></template>
        </TopNavTitle>
        <div class="card">
            <DataTable :value="posts.data" dataKey="id" showGridlines>
                <Column field="title" header="عنوان" />
                <Column field="slug" header="نامک" />
                <Column header="دسته‌بندی"><template #body="{ data }">{{ data.category?.name }}</template></Column>
                <Column field="status" header="وضعیت"><template #body="{ data }"><Tag :value="data.status === 'published' ? 'منتشر شده' : 'پیش‌نویس'" :severity="data.status === 'published' ? 'success' : 'warn'" /></template></Column>
                <Column field="view_count" header="بازدید" />
                <Column header="سئو"><template #body="{ data }"><Tag :value="data.seo_index ? 'قابل نمایه‌سازی' : 'غیرقابل نمایه‌سازی'" :severity="data.seo_index ? 'success' : 'danger'" /></template></Column>
                <Column header="عملیات" style="width: 9rem">
                    <template #body="{ data }">
                        <Link :href="`/posts/${data.slug}/edit`"><Button icon="pi pi-pencil" rounded text severity="secondary" /></Link>
                        <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data)" />
                    </template>
                </Column>
            </DataTable>
            <Paginator v-if="posts.total > posts.per_page" :rows="posts.per_page" :totalRecords="posts.total" :first="(posts.current_page - 1) * posts.per_page" @page="router.get('/posts', { page: $event.page + 1 }, { preserveState: true })" />
        </div>
    </AppLayout>
</template>
