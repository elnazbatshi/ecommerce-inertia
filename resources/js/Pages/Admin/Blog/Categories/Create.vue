<script setup>
import Form from './Form.vue';

defineProps({
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <Form :categories="categories" />
</template>
