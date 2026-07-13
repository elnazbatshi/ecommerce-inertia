<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
});

const icons = {
    truck: 'pi pi-truck',
    shield: 'pi pi-shield',
    headset: 'pi pi-headphones',
    rotate: 'pi pi-refresh',
    package: 'pi pi-box',
    'credit-card': 'pi pi-credit-card',
    'map-pin': 'pi pi-map-marker',
    phone: 'pi pi-phone',
    mail: 'pi pi-envelope',
    clock: 'pi pi-clock',
    star: 'pi pi-star',
    wrench: 'pi pi-wrench',
    oil: 'pi pi-filter',
    motorcycle: 'pi pi-car',
};

const iconClass = computed(() => icons[props.name] || 'pi pi-circle');
</script>

<template>
    <i :class="iconClass" aria-hidden="true"></i>
</template>
