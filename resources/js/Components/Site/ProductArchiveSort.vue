<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: 'newest'
    }
});

const emit = defineEmits(['update:modelValue']);

const sortOptions = [
    { label: 'جدیدترین', value: 'newest' },
    { label: 'پرفروش‌ترین', value: 'bestselling' },
    { label: 'ارزان‌ترین', value: 'price_asc' },
    { label: 'گران‌ترین', value: 'price_desc' },
    { label: 'بیشترین تخفیف', value: 'discount_desc' },
];
</script>

<template>
    <Dropdown
        :model-value="props.modelValue"
        :options="sortOptions"
        optionLabel="label"
        optionValue="value"
        class="w-full md:w-56"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>

