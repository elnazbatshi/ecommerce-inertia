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
            {
                label: 'مدیریت کالاها',
                icon: 'pi pi-fw pi-tags',
                items: [
                    { label: 'محصولات', icon: 'pi pi-fw pi-box', to: '/admin/products' },
                    { label: 'دسته‌بندی‌ها', icon: 'pi pi-fw pi-sitemap', to: '/admin/categories' },
                    { label: 'برندها', icon: 'pi pi-fw pi-tags', to: '/admin/brands' },
                    { label: 'ویژگی‌ها', icon: 'pi pi-fw pi-sliders-h', to: '/admin/attributes' },
                    { label: 'نظرات محصولات', icon: 'pi pi-fw pi-star', to: '/admin/product-reviews' },
                ]
            },
            {
                label: 'مدیریت سفارشات',
                icon: 'pi pi-fw pi-shopping-bag',
                items: [
                    { label: 'سفارش‌ها', icon: 'pi pi-fw pi-shopping-cart', to: '/admin/orders' },
                    { label: 'پرداخت‌ها', icon: 'pi pi-fw pi-wallet', to: '/admin/payments' },
                    { label: 'کارتکس فروش محصولات', icon: 'pi pi-fw pi-chart-line', to: '/admin/product-sales-ledger' },
                    { label: 'کارتکس خرید مشتریان', icon: 'pi pi-fw pi-users', to: '/admin/customer-purchase-ledger' },
                ]
            },
            {
                label: 'سیستم',
                icon: 'pi pi-fw pi-cog',
                items: [
                    { label: 'روش‌های ارسال', icon: 'pi pi-fw pi-send', to: '/admin/shipping-methods' },
                    { label: 'روش‌های پرداخت', icon: 'pi pi-fw pi-credit-card', to: '/admin/payment-methods' },
                    { label: 'نقش‌ها و دسترسی‌ها', icon: 'pi pi-fw pi-lock', to: '/admin/accesses' },
                ]
            },
            {
                label: 'مدیریت آدرس‌ها',
                icon: 'pi pi-fw pi-map-marker',
                items: [
                    { label: 'استان‌ها', icon: 'pi pi-fw pi-map', to: '/admin/provinces' },
                    { label: 'شهرها', icon: 'pi pi-fw pi-building', to: '/admin/cities' }
                ]
            },
            {
                label: 'مدیریت خودرو',
                icon: 'pi pi-fw pi-car',
                items: [
                    { label: 'نوع وسیله', icon: 'pi pi-fw pi-th-large', to: '/admin/vehicle-types' },
                    { label: 'برند خودرو', icon: 'pi pi-fw pi-tag', to: '/admin/vehicle-brands' },
                    { label: 'خودروها', icon: 'pi pi-fw pi-car', to: '/admin/vehicles' }
                ]
            },




            { label: 'مشتریان', icon: 'pi pi-fw pi-users', to: '/admin/customers' }
        ]
    },
    {
        label: 'مدیریت محتوا',
        items: [
            { label: 'مقالات', icon: 'pi pi-fw pi-file-edit', to: '/admin/blog-posts' },
            { label: 'دسته‌بندی مقالات', icon: 'pi pi-fw pi-folder', to: '/admin/blog-categories' },
            { label: 'برچسب‌ها', icon: 'pi pi-fw pi-tags', to: '/admin/blog-tags' },
            { label: 'صفحات', icon: 'pi pi-fw pi-file', to: '/admin/pages' },
            { label: 'رسانه‌ها', icon: 'pi pi-fw pi-images', to: '/admin/media' },
            { label: 'محتوای برندها', icon: 'pi pi-fw pi-bookmark', to: '/admin/brands' },
            { label: 'منوهای سایت', icon: 'pi pi-fw pi-list', to: '/admin/menus' }
        ]
    },
    {
        label: 'مدیریت سایت',
        items: [
            { label: 'تنظیمات سایت', icon: 'pi pi-fw pi-cog', to: '/admin/site-settings' },
            { label: 'اسلایدر صفحه اصلی', icon: 'pi pi-fw pi-images', to: '/admin/hero-sliders' },
            { label: 'مدیریت بنرها', icon: 'pi pi-fw pi-ticket', to: '/admin/banner-sections' }
        ]
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
            <app-menu-item v-if="!item.separator" :item="item" :index="i" />
            <li v-if="item.separator" class="menu-separator" />
        </template>
    </ul>
</template>
