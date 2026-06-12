<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MegaMenu from './MegaMenu.vue';
import Navbar from './Navbar.vue';
import MiniCart from './MiniCart.vue';
import CustomerAuthModal from './CustomerAuthModal.vue';
import SearchBox from '@/Components/Site/SearchBox.vue';
import { getMenu } from '../services/menuApi';

const page = usePage();
const isLoading = ref(true);
const isMobileOpen = ref(false);
const isAuthModalOpen = ref(false);
const menu = ref({ location: 'header', items: [], popular_brands: [], popular_vehicles: [], quick_links: [] });
const hoveredItem = ref(null);
let menuController = null;

const navItems = computed(() => menu.value.items || []);
const activeMega = computed(() => hoveredItem.value && hoveredItem.value.children?.length);
const customer = computed(() => page.props.customer || null);

const reloadAuth = () => {
    router.reload({ only: ['customer'] });
};

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
                <span>7 روز ضمانت بازگشت کالا</span>
                <span>ضمانت اصالت کالا</span>
                <span>خرید امن</span>
            </div>
        </div>

        <div class="header-main">
            <div class="site-container grid items-center gap-4 py-5 lg:grid-cols-[260px_1fr_280px]">
                <button class="site-icon-btn lg:hidden" type="button" @click="isMobileOpen = !isMobileOpen">☰</button>

                <Link href="/" class="order-2 block lg:order-1">
                    <h1 class="text-2xl font-black text-[var(--site-dark)]">MotoPart</h1>
                    <p class="text-xs text-[var(--site-text-secondary)]">فروشگاه آنلاین روغن موتور و قطعات</p>
                </Link>

                <div class="order-3 lg:order-2">
                    <SearchBox />
                </div>

                <div class="order-1 flex items-center justify-end gap-2 lg:order-3">
                    <button type="button" class="site-icon-btn" @click="isAuthModalOpen = true">
                        {{ customer ? (customer.name || customer.phone) : 'ورود / ثبت نام' }}
                    </button>
                    <MiniCart />
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

        <CustomerAuthModal v-model:visible="isAuthModalOpen" @authenticated="reloadAuth" />
    </header>
</template>
