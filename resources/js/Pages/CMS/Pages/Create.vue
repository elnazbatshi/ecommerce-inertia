<script setup>
import PageForm from './PageForm.vue';

defineProps({
    statusOptions: { type: Array, default: () => [] },
    templateOptions: { type: Array, default: () => [] }
});
</script>

<template>
    <PageForm :statusOptions="statusOptions" :templateOptions="templateOptions" />
</template>
