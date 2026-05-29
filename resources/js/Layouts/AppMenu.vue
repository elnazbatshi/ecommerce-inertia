<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'اصلی',
        items: [{ label: 'داشبورد', icon: 'pi pi-fw pi-home', to: '/admin/dashboard' }]
    },
    {
        label: 'مدیریت فروشگاه',
        items: [
            { label: 'محصولات', icon: 'pi pi-fw pi-box', to: '/admin/products' },
            { label: 'دسته‌بندی‌ها', icon: 'pi pi-fw pi-sitemap', to: '/admin/categories' },
            { label: 'برندها', icon: 'pi pi-fw pi-tags', to: '/admin/brands' },
            {
                label: 'مدیریت خودرو',
                icon: 'pi pi-fw pi-car',
                items: [
                    { label: 'برند خودرو', icon: 'pi pi-fw pi-tag', to: '/admin/vehicle-brands' },
                    { label: 'خودروها', icon: 'pi pi-fw pi-car', to: '/admin/vehicles' }
                ]
            },
            { label: 'ویژگی‌ها', icon: 'pi pi-fw pi-sliders-h', to: '/admin/attributes' },
            { label: 'نقش‌ها و دسترسی‌ها', icon: 'pi pi-fw pi-lock', to: '/admin/accesses' },
            { label: 'سفارش‌ها', icon: 'pi pi-fw pi-shopping-cart', to: '/admin/orders' },
            { label: 'پرداخت‌ها', icon: 'pi pi-fw pi-credit-card', to: '/admin/payments' },
            { label: 'مشتریان', icon: 'pi pi-fw pi-users', to: '/admin/customers' }
        ]
    },
    {
        label: 'مدیریت محتوا',
        items: [
            { label: 'مقالات', icon: 'pi pi-fw pi-file-edit', to: '/admin/posts' },
            { label: 'دسته‌بندی مقالات', icon: 'pi pi-fw pi-folder', to: '/admin/post-categories' },
            { label: 'برچسب‌ها', icon: 'pi pi-fw pi-tags', to: '/admin/post-tags' },
            { label: 'صفحات', icon: 'pi pi-fw pi-file', to: '/admin/pages' },
            { label: 'رسانه‌ها', icon: 'pi pi-fw pi-images', to: '/admin/media' },
            { label: 'محتوای برندها', icon: 'pi pi-fw pi-bookmark', to: '/admin/brands' },
            { label: 'منوهای سایت', icon: 'pi pi-fw pi-list', to: '/admin/menus' }
        ]
    },
    {
        label: 'مدیریت سایت',
        items: [{ label: 'اسلایدر صفحه اصلی', icon: 'pi pi-fw pi-images', to: '/admin/hero-sliders' }]
    },
    {
        label: 'مدیریت سرچ',
        items: [
            { label: 'پیشنهادهای سرچ', icon: 'pi pi-fw pi-search', to: '/admin/search/suggestions' },
            { label: 'گزارش سرچ‌ها', icon: 'pi pi-fw pi-chart-bar', to: '/admin/search/logs' }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>
