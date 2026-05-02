<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'کلمات کلیدی را وارد کنید و Enter بزنید'
    },
    maxLength: {
        type: Number,
        default: 50
    }
});

const emit = defineEmits(['update:modelValue']);
const input = ref('');

const tags = computed(() => props.modelValue ?? []);

const normalize = (value) => value.trim().replace(/\s+/g, ' ');

const addTag = () => {
    const value = normalize(input.value);

    if (!value || value.length > props.maxLength || tags.value.includes(value)) {
        input.value = '';
        return;
    }

    emit('update:modelValue', [...tags.value, value]);
    input.value = '';
};

const removeTag = (index) => {
    emit('update:modelValue', tags.value.filter((_, tagIndex) => tagIndex !== index));
};

const onBackspace = () => {
    if (input.value || !tags.value.length) {
        return;
    }

    removeTag(tags.value.length - 1);
};
</script>

<template>
    <div class="tag-input" @click="$refs.keywordInput?.focus()">
        <Tag
            v-for="(tag, index) in tags"
            :key="`${tag}-${index}`"
            severity="info"
            class="tag-input__tag"
        >
            <span>{{ tag }}</span>
            <button type="button" class="tag-input__remove" aria-label="حذف کلمه کلیدی" @click.stop="removeTag(index)">
                <i class="pi pi-times" />
            </button>
        </Tag>
        <input
            ref="keywordInput"
            v-model="input"
            class="tag-input__field"
            :placeholder="tags.length ? '' : placeholder"
            @keydown.enter.prevent="addTag"
            @keydown.backspace="onBackspace"
            @keydown.tab="addTag"
        />
    </div>
</template>

<style scoped>
.tag-input {
    display: flex;
    min-height: 42px;
    width: 100%;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid var(--p-inputtext-border-color);
    border-radius: var(--p-inputtext-border-radius);
    background: var(--p-inputtext-background);
    padding: 0.4rem 0.65rem;
}

.tag-input:focus-within {
    border-color: var(--p-inputtext-focus-border-color);
    box-shadow: var(--p-inputtext-focus-ring-shadow);
}

.tag-input__tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.tag-input__remove {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    font-size: 0.75rem;
    line-height: 1;
    padding: 0;
}

.tag-input__field {
    min-width: 14rem;
    flex: 1;
    border: 0;
    background: transparent;
    color: var(--p-inputtext-color);
    outline: 0;
    padding: 0.25rem 0;
}
</style>
