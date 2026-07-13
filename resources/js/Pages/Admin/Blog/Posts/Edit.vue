<script setup>
import Form from './Form.vue';

defineProps({
    post: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['draft', 'published', 'archived'] },
});
</script>

<template>
    <Form :post="post" :categories="categories" :tags="tags" :products="products" :statuses="statuses" />
</template>
