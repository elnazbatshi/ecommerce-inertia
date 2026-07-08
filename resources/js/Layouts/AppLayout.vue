<script setup>
import { usePage } from '@inertiajs/vue3';
import { useLayout } from '@/Layouts/composables/layout';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const { layoutConfig, layoutState, isSidebarActive } = useLayout();
const page = usePage();
const toast = useToast();

const outsideClickListener = ref(null);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({ severity: 'success', summary: 'موفق', detail: flash.success, life: 3000 });
        }

        if (flash?.error) {
            toast.add({ severity: 'error', summary: 'خطا', detail: flash.error, life: 4000 });
        }
    },
    { deep: true }
);

watch(isSidebarActive, (newVal) => {
    if (newVal) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

watch(
    () => layoutState.staticMenuMobileActive,
    (active) => {
        document.body.classList.toggle('blocked-scroll', active);
    }
);

const containerClass = computed(() => {
    return {
        'layout-overlay': layoutConfig.menuMode === 'overlay',
        'layout-static': layoutConfig.menuMode === 'static',
        'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
        'layout-overlay-active': layoutState.overlayMenuActive,
        'layout-mobile-active': layoutState.staticMenuMobileActive
    };
});

function bindOutsideClickListener() {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                layoutState.overlayMenuActive = false;
                layoutState.staticMenuMobileActive = false;
                layoutState.menuHoverActive = false;
            }
        };
        document.addEventListener('click', outsideClickListener.value);
    }
}

function unbindOutsideClickListener() {
    if (outsideClickListener.value) {
        document.removeEventListener('click', outsideClickListener.value);
        outsideClickListener.value = null;
    }
}

function isOutsideClicked(event) {
    const sidebarEl = document.querySelector('.layout-sidebar');
    const topbarEl = document.querySelector('.layout-menu-button');

    return !(sidebarEl?.contains(event.target) || topbarEl?.contains(event.target));
}

function closeMobileSidebar() {
    layoutState.overlayMenuActive = false;
    layoutState.staticMenuMobileActive = false;
    layoutState.menuHoverActive = false;
}

onBeforeUnmount(() => {
    document.body.classList.remove('blocked-scroll');
});
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        <div class="layout-main-container">
            <div class="layout-main">
                <slot />
            </div>
            <app-footer></app-footer>
        </div>
        <div class="layout-mask animate-fadein" @click="closeMobileSidebar"></div>
    </div>
    <Toast />
</template>
