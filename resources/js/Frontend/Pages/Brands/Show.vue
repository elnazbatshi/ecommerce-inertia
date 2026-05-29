<script setup>
import FrontLayout from '../../Layouts/FrontLayout.vue';
import SectionTitle from '../../Components/SectionTitle.vue';

defineProps({
    brand: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <FrontLayout>
        <SectionTitle :title="brand.name || 'Brand'" subtitle="Brand detail placeholder" />
    </FrontLayout>
</template>
