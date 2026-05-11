<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'اصلی',
        items: [{ label: 'داشبورد', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'مدیریت',
        items: [
            { label: 'محصولات', icon: 'pi pi-fw pi-box', to: '/products' },
            { label: 'دسته‌بندی‌ها', icon: 'pi pi-fw pi-sitemap', to: '/categories' },
            { label: 'برندها', icon: 'pi pi-fw pi-tags', to: '/brands' },
            { label: 'ویژگی‌ها', icon: 'pi pi-fw pi-sliders-h', to: '/attributes' },
            { label: 'نقش‌ها و دسترسی‌ها', icon: 'pi pi-fw pi-lock', to: '/accesses' },
            { label: 'سفارش‌ها', icon: 'pi pi-fw pi-shopping-cart', to: '/orders' },
            { label: 'پرداخت‌ها', icon: 'pi pi-fw pi-credit-card', to: '/payments' },
            { label: 'مشتریان', icon: 'pi pi-fw pi-users', to: '/customers' }
        ]
    },
    {
        label: 'محتوا',
        items: [
            { label: 'مقالات', icon: 'pi pi-fw pi-file-edit', to: '/posts' },
            { label: 'دسته‌بندی مقالات', icon: 'pi pi-fw pi-folder', to: '/post-categories' },
            { label: 'برچسب‌ها', icon: 'pi pi-fw pi-tags', to: '/post-tags' },
            { label: 'صفحات', icon: 'pi pi-fw pi-file', to: '/pages' },
            { label: 'محتوای برندها', icon: 'pi pi-fw pi-bookmark', to: '/brands' }
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
