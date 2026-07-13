<script setup>
import Form from './Form.vue';

defineProps({
    tag: { type: Object, required: true },
});
</script>

<template>
    <Form :tag="tag" />
</template>
