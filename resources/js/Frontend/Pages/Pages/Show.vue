<script setup>
import FrontLayout from '../../Layouts/FrontLayout.vue';
import SectionTitle from '../../Components/SectionTitle.vue';

defineProps({
    page: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <FrontLayout>
        <SectionTitle :title="page.title || 'Page'" subtitle="CMS page placeholder" />
    </FrontLayout>
</template>
