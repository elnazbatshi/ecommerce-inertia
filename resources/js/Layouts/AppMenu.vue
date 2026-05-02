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
            { label: 'سفارش‌ها', icon: 'pi pi-fw pi-shopping-cart', to: '/dashboard' },
            { label: 'مشتریان', icon: 'pi pi-fw pi-users', to: '/dashboard' }
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
