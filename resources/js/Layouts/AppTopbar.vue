<template>
    <div class="layout-topbar flex justify-between" dir="ltr">
        <Link href="/admin/dashboard" class="layout-topbar-logo">
            <button class="mr-10! p-link layout-menu-button layout-topbar-button" @click.prevent="onMenuToggle()">
                <i class="pi pi-bars"></i>
            </button>
            <p class="dornika-logo">Dashboard</p>
        </Link>

        <button class="p-link layout-topbar-menu-button layout-topbar-button" @click.stop="onTopBarMenuButton()">
            <i class="pi pi-ellipsis-v"></i>
        </button>

        <div class="layout-topbar-menu" :class="topbarMenuClasses">
            <button @click="toggleSelectAction" class="p-link layout-topbar-button">
                <i class="pi pi-user"></i>
                <span>{{ userName }}</span>
            </button>
            <Popover ref="SelectActionSection" class="customer-action-overlay custom-table-action-w" :breakpoints="{ '640px': '210px' }">
                <div class="SelectActionSection">
                    <ul class="grid space-y-4">
                        <li class="cursor-pointer" @click="logout">
                            <i class="pi pi-sign-out"></i>
                            <span>Logout</span>
                        </li>
                        <li>
                            <i class="pi pi-user"></i>
                            <span>Profile</span>
                        </li>
                    </ul>
                </div>
            </Popover>
        </div>
    </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Popover from 'primevue/popover';
import { useLayout } from '@/Layouts/composables/layout';

const { layoutState, toggleMenu: onMenuToggle } = useLayout();
const page = usePage();

const SelectActionSection = ref(Popover);
const outsideClickListener = ref(null);
const topbarMenuActive = ref(false);

const toggleSelectAction = (event) => {
    topbarMenuActive.value = false;
    SelectActionSection.value?.toggle(event);
};

const logout = () => {
    topbarMenuActive.value = false;
    router.post('/logout');
};

onMounted(() => {
    bindOutsideClickListener();
});

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});

const onTopBarMenuButton = () => {
    topbarMenuActive.value = !topbarMenuActive.value;
};

watch(
    () => layoutState.staticMenuMobileActive,
    (active) => {
        if (active) {
            topbarMenuActive.value = false;
        }
    }
);

const topbarMenuClasses = computed(() => ({
    'layout-topbar-menu-mobile-active': topbarMenuActive.value
}));

const userName = computed(() => page.props.auth?.user?.name ?? 'Profile');

const bindOutsideClickListener = () => {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                topbarMenuActive.value = false;
            }
        };
        document.addEventListener('click', outsideClickListener.value);
    }
};

const unbindOutsideClickListener = () => {
    if (outsideClickListener.value) {
        document.removeEventListener('click', outsideClickListener.value);
        outsideClickListener.value = null;
    }
};

const isOutsideClicked = (event) => {
    if (!topbarMenuActive.value) return false;

    const sidebarEl = document.querySelector('.layout-topbar-menu');
    const topbarEl = document.querySelector('.layout-topbar-menu-button');

    return !(sidebarEl?.contains(event.target) || topbarEl?.contains(event.target));
};
</script>

<style lang="scss" scoped>
.layout-topbar-logo {
    font-size: 22px;
}

.dornika-logo {
    font-size: 22px;
}
</style>
