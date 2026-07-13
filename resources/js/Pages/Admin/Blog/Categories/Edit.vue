<script setup>
import Form from './Form.vue';

defineProps({
    category: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <Form :category="category" :categories="categories" />
</template>
