<script setup>
import PageForm from './PageForm.vue';

defineProps({
    page: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    templateOptions: { type: Array, default: () => [] }
});
</script>

<template>
    <PageForm :page="page" :statusOptions="statusOptions" :templateOptions="templateOptions" />
</template>
