<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import MegaMenu from './MegaMenu.vue';
import Navbar from './Navbar.vue';
import SearchBox from '@/Components/Site/SearchBox.vue';
import { getMenu } from '../services/menuApi';

const isLoading = ref(true);
const isMobileOpen = ref(false);
const menu = ref({ location: 'header', items: [], popular_brands: [], popular_vehicles: [], quick_links: [] });
const hoveredItem = ref(null);
let menuController = null;

const navItems = computed(() => menu.value.items || []);
const activeMega = computed(() => hoveredItem.value && hoveredItem.value.children?.length);

const loadMenu = async () => {
    menuController?.abort();
    menuController = new AbortController();
    isLoading.value = true;
    menu.value = await getMenu({ location: 'header', signal: menuController.signal });
    isLoading.value = false;
};

onMounted(loadMenu);
onBeforeUnmount(() => menuController?.abort());
</script>

<template>
    <header class="site-header sticky top-0 z-50">
        <div class="topbar-dark">
            <div class="site-container topbar-items">
                <span>ارسال سریع به سراسر کشور</span>
                <span>۷ روز ضمانت بازگشت کالا</span>
                <span>ضمانت اصالت کالا</span>
                <span>خرید امن</span>
            </div>
        </div>

        <div class="header-main">
            <div class="site-container grid items-center gap-4 py-5 lg:grid-cols-[260px_1fr_280px]">
                <button class="site-icon-btn lg:hidden" type="button" @click="isMobileOpen = !isMobileOpen">☰</button>

                <div class="order-2 lg:order-1">
                    <h1 class="text-2xl font-black text-[var(--site-dark)]">MotoPart</h1>
                    <p class="text-xs text-[var(--site-text-secondary)]">فروشگاه آنلاین روغن موتور و قطعات</p>
                </div>

                <div class="order-3 lg:order-2">
                    <SearchBox />
                </div>

                <div class="order-1 flex items-center justify-end gap-2 lg:order-3">
                    <a href="/login" class="site-icon-btn">ورود / ثبت نام</a>
                    <a href="#" class="site-icon-btn">سبد خرید <span class="cart-badge">0</span></a>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="site-navbar">
            <div class="site-container flex h-16 items-center gap-3">
                <span v-for="n in 6" :key="n" class="skeleton-pill"></span>
            </div>
        </div>
        <div v-else @mouseleave="hoveredItem = null">
            <Navbar
                :items="navItems"
                :active-slug="hoveredItem?.slug || ''"
                @hover-item="hoveredItem = $event"
            />

            <MegaMenu
                :open="!!activeMega"
                :category="hoveredItem"
                :brands="menu.popular_brands || []"
                :vehicles="menu.popular_vehicles || []"
                :quick-links="menu.quick_links || []"
            />
        </div>
    </header>
</template>
