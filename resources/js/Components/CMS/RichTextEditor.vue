<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' }
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <Editor
            :modelValue="modelValue"
            editorStyle="height: 320px"
            dir="rtl"
            @update:modelValue="emit('update:modelValue', $event)"
        >
            <template #toolbar>
                <span class="ql-formats">
                    <select class="ql-header">
                        <option value="1"></option>
                        <option value="2"></option>
                        <option value="3"></option>
                        <option selected></option>
                    </select>
                </span>
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-link"></button>
                    <button class="ql-image"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-direction" value="rtl"></button>
                </span>
            </template>
        </Editor>
        <small v-if="error" class="text-red-600">{{ error }}</small>
    </div>
</template>

<style scoped>
:deep(.ql-editor) {
    direction: rtl;
    min-height: 320px;
    text-align: right;
}
</style>
