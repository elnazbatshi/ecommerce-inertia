<script setup>
import BlogCard from './Blog/BlogCard.vue';

defineProps({
    post: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <BlogCard :post="post" />
</template>
