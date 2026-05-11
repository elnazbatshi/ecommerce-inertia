<script setup>
import PostForm from './PostForm.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] }
});
</script>

<template>
    <PostForm :categories="categories" :tags="tags" :products="products" :statusOptions="statusOptions" />
</template>
