<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    url: { type: String, default: '' },
    fallbackTitle: { type: String, default: 'پیش‌نمایش عنوان سئو' },
    fallbackDescription: { type: String, default: 'پیش‌نمایش توضیحات سئو اینجا نمایش داده می‌شود.' }
});

const previewTitle = computed(() => props.title || props.fallbackTitle);
const previewDescription = computed(() => props.description || props.fallbackDescription);
</script>

<template>
    <div class="rounded-md border border-surface-200 p-4">
        <div class="text-lg text-blue-700">{{ previewTitle }}</div>
        <div class="break-all text-sm text-green-700">{{ url }}</div>
        <p class="mt-1 text-sm text-surface-600">{{ previewDescription }}</p>
    </div>
</template>
