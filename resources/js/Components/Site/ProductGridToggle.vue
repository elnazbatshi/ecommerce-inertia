<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: 'grid'
    }
});

const emit = defineEmits(['update:modelValue']);

const setMode = (mode) => emit('update:modelValue', mode);
</script>

<template>
    <div class="inline-flex items-center rounded-lg border border-surface-200 bg-white p-1">
        <Button
            icon="pi pi-th-large"
            text
            rounded
            :severity="props.modelValue === 'grid' ? 'contrast' : 'secondary'"
            @click="setMode('grid')"
        />
        <Button
            icon="pi pi-bars"
            text
            rounded
            :severity="props.modelValue === 'list' ? 'contrast' : 'secondary'"
            @click="setMode('list')"
        />
    </div>
</template>

