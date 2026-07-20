<script setup>
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import {Link, router, usePage} from '@inertiajs/vue3';
import MegaMenu from './MegaMenu.vue';
import Navbar from './Navbar.vue';
import MiniCart from './MiniCart.vue';
import CustomerAuthModal from './CustomerAuthModal.vue';
import SiteSettingIcon from './SiteSettingIcon.vue';
import SearchBox from '@/Components/Site/SearchBox.vue';
import {getMenu} from '../services/menuApi';
import {useSiteSettings} from '../Composables/useSiteSettings';

const page = usePage();
const isLoading = ref(true);
const isMobileOpen = ref(false);
const isAuthModalOpen = ref(false);
const isAccountMenuOpen = ref(false);
const isScrolled = ref(false);
const menu = ref({location: 'header', items: [], popular_brands: [], popular_vehicles: [], quick_links: []});
const hoveredItem = ref(null);
const {settings, topbarItems} = useSiteSettings();
let menuController = null;

const navItems = computed(() => menu.value.items || []);
const activeMega = computed(() => hoveredItem.value && hoveredItem.value.children?.length);
const customer = computed(() => page.props.customer || null);
const siteName = computed(() => settings.value.general?.site_name || 'MotoShahr');
const logoUrl = computed(() => settings.value.general?.logo_url || null);
const siteDescription = computed(() => settings.value.general?.site_description || 'فروشگاه آنلاین روغن موتور و قطعات');
const brandTail = computed(() => siteName.value.startsWith('Moto') ? siteName.value.slice(4) : siteName.value);

const reloadAuth = () => {
    isAuthModalOpen.value = false;
    isAccountMenuOpen.value = false;
    router.reload({only: ['customer']});
};

const handleAccountClick = () => {
    if (customer.value) {
        isAccountMenuOpen.value = !isAccountMenuOpen.value;
        return;
    }

    isAuthModalOpen.value = true;
};

const logoutCustomer = () => {
    router.post('/customer/logout', {}, {
        preserveScroll: true,
        onSuccess: () => {
            isAccountMenuOpen.value = false;
            router.reload({only: ['customer']});
        },
    });
};

const loadMenu = async () => {
    menuController?.abort();
    menuController = new AbortController();
    isLoading.value = true;
    menu.value = await getMenu({location: 'header', signal: menuController.signal});
    isLoading.value = false;
};

const openCustomerAuthModal = () => {
    if (!customer.value) {
        isAuthModalOpen.value = true;
    }
};

const closeMobileMenu = () => {
    isMobileOpen.value = false;
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 24;
};

watch(isMobileOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

onMounted(() => {
    loadMenu();
    handleScroll();
    window.addEventListener('scroll', handleScroll, {passive: true});
    window.addEventListener('motoShahr:open-customer-auth', openCustomerAuthModal);
});

onBeforeUnmount(() => {
    menuController?.abort();
    document.body.classList.remove('overflow-hidden');
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('motoShar:open-customer-auth', openCustomerAuthModal);
});
</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-white/10 bg-[#0b0d10] text-white shadow-[0_16px_50px_rgba(0,0,0,0.35)]">
        <div
            class="overflow-hidden border-b border-white/10 bg-[#08090b] transition-all duration-200"
            :class="isScrolled ? 'max-h-0 opacity-0' : 'max-h-10 opacity-100'"
        >
            <div
                class="site-container flex h-9 items-center justify-center gap-9 overflow-x-auto whitespace-nowrap text-[13px] font-bold leading-none text-[#c8cbd0] [scrollbar-width:none]">
                <span v-for="item in topbarItems" :key="`${item.title}-${item.sort_order}`"
                      class="inline-flex items-center gap-2.5 leading-none">
                    <SiteSettingIcon :name="item.icon" class="translate-y-px text-[#D4A017]"/>
                    {{ item.title }}
                </span>
            </div>
        </div>

        <div class="bg-[#0b0d10] transition-all duration-200" :class="isScrolled ? 'lg:-mt-1' : ''">
            <div class="site-container grid items-center gap-3 py-3.5 lg:grid-cols-[225px_minmax(0,1.18fr)_280px] lg:gap-5 lg:py-4">

                    <button
                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white transition duration-200 hover:border-[#D4A017] hover:text-[#D4A017] lg:hidden"
                        type="button" @click="isMobileOpen = true">
                        <i class="pi pi-bars" aria-hidden="true"></i>
                    </button>

                    <Link href="/" class="order-2 flex w-44 items-center gap-2 lg:order-1 lg:w-[210px]">
                        <img v-if="logoUrl" :src="logoUrl" :alt="siteName" class="h-[5.15rem] w-auto max-w-[176px] object-contain [image-rendering:auto]"/>
                        <h1 v-else class="text-2xl font-black text-white">
                            <span
                            class="text-[#D4A017]">Moto</span>{{ brandTail }}</h1>
                    </Link>

                    <div class="order-3 min-w-0 lg:order-2">
                        <SearchBox  />
                    </div>







                <div class="order-1 flex items-center justify-end gap-2.5 lg:order-3">
                    <Link href="/blog"
                          class="hidden rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm font-black text-white transition duration-200 hover:border-[var(--site-gold)] hover:text-[var(--site-gold)] md:inline-flex">
                        مجله موتوپارت
                    </Link>

                    <div class="relative">
                        <button type="button"
                                class="flex h-11 items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 text-sm font-black text-white transition duration-200 hover:border-[#D4A017] hover:text-[#D4A017] hover:shadow-[0_10px_24px_rgba(212,160,23,0.14)]"
                                @click="handleAccountClick">
                            {{ customer ? (customer.name || customer.phone) : 'ورود / ثبت نام' }}
                        </button>

                        <div
                            v-if="customer && isAccountMenuOpen"
                            class="absolute left-0 top-full z-[100] mt-3 w-56 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl"
                        >
                            <Link href="/profile/orders"
                                  class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                  @click="isAccountMenuOpen = false">
                                <span>سفارش‌های من</span>
                                <i class="pi pi-shopping-bag"></i>
                            </Link>
                            <Link href="/profile/wishlist"
                                  class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                  @click="isAccountMenuOpen = false">
                                <span>علاقه‌مندی‌ها</span>
                                <i class="pi pi-heart"></i>
                            </Link>
                            <Link href="/profile/addresses"
                                  class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                  @click="isAccountMenuOpen = false">
                                <span>آدرس‌های من</span>
                                <i class="pi pi-map-marker"></i>
                            </Link>
                            <button type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-sm text-red-600 hover:bg-red-50"
                                    @click="logoutCustomer">
                                <span>خروج</span>
                                <i class="pi pi-sign-out"></i>
                            </button>
                        </div>
                    </div>

                    <MiniCart/>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="hidden border-y border-white/10 bg-[#0b0d10] lg:block">
            <div class="site-container flex h-14 items-center gap-3">
                <span v-for="n in 6" :key="n" class="skeleton-pill"></span>
            </div>
        </div>
        <div v-else class="hidden lg:block" @mouseleave="hoveredItem = null">
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

        <CustomerAuthModal v-if="!customer" v-model:visible="isAuthModalOpen" @authenticated="reloadAuth"/>

        <transition name="fade-slide">
            <div v-if="isMobileOpen" class="fixed inset-0 z-[120] lg:hidden">
                <button class="absolute inset-0 bg-black/65 backdrop-blur-sm" type="button" aria-label="بستن منو"
                        @click="closeMobileMenu"></button>
                <aside
                    class="absolute right-0 top-0 h-full w-[min(86vw,22rem)] overflow-y-auto border-l border-white/10 bg-[#0b0d10] p-5 text-white shadow-2xl">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <strong class="text-xl font-black"><span class="text-[#D4A017]">Moto</span>{{
                                    brandTail
                                }}</strong>
                        </div>
                        <button type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-white transition duration-200 hover:border-[#D4A017] hover:text-[#D4A017]"
                                @click="closeMobileMenu">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <nav class="space-y-1">
                        <a
                            v-for="item in navItems"
                            :key="item.slug || item.title"
                            :href="item.url || '#'"
                            class="flex items-center justify-between rounded-xl px-3 py-3 text-sm font-bold text-[#d6d8dc] transition duration-200 hover:bg-white/5 hover:text-[#D4A017]"
                            @click="closeMobileMenu"
                        >
                            <span>{{ item.title }}</span>
                            <i v-if="item.children?.length" class="pi pi-angle-left text-xs"></i>
                        </a>
                    </nav>
                </aside>
            </div>
        </transition>
    </header>
</template>
