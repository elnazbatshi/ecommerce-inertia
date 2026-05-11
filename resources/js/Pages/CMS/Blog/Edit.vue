<script setup>
import PostForm from './PostForm.vue';

defineProps({
    post: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] }
});
</script>

<template>
    <PostForm :post="post" :categories="categories" :tags="tags" :products="products" :statusOptions="statusOptions" />
</template>
