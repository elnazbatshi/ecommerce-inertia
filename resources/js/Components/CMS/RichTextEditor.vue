<script setup>
import { ref } from 'vue';
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    allowHtmlSource: { type: Boolean, default: false },
    allowMediaBrowser: { type: Boolean, default: false },
    mediaCollection: { type: String, default: 'editor_content' }
});

const emit = defineEmits(['update:modelValue']);
const sourceMode = ref(false);
const browserVisible = ref(false);

const escapeAttr = (value = '') => String(value).replace(/"/g, '&quot;');

const insertMedia = (items) => {
    const images = items
        .filter((media) => media?.url)
        .map((media) => {
            const alt = escapeAttr(media.alt || media.title || media.original_name || '');
            return `<p><img src="${media.url}" alt="${alt}" /></p>`;
        })
        .join('');

    if (!images) return;

    emit('update:modelValue', `${props.modelValue || ''}${images}`);
};
</script>

<template>
    <div>
        <div v-if="allowHtmlSource || allowMediaBrowser" class="mb-3 flex flex-wrap justify-end gap-2">
            <Button
                v-if="allowMediaBrowser"
                label="انتخاب تصویر از رسانه‌ها"
                icon="pi pi-images"
                size="small"
                outlined
                @click="browserVisible = true"
            />

            <div v-if="allowHtmlSource" class="inline-flex rounded-lg border border-surface-200 bg-white p-1">
                <Button
                    label="ویرایشگر"
                    size="small"
                    text
                    :severity="!sourceMode ? 'contrast' : 'secondary'"
                    @click="sourceMode = false"
                />
                <Button
                    label="HTML"
                    icon="pi pi-code"
                    size="small"
                    text
                    :severity="sourceMode ? 'contrast' : 'secondary'"
                    @click="sourceMode = true"
                />
            </div>
        </div>

        <Textarea
            v-if="allowHtmlSource && sourceMode"
            :modelValue="modelValue"
            dir="ltr"
            rows="16"
            class="html-source-editor w-full font-mono"
            autoResize
            placeholder="<section>...</section>"
            @update:modelValue="emit('update:modelValue', $event)"
        />

        <Editor
            v-else
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

        <MediaBrowser
            v-if="allowMediaBrowser"
            v-model:visible="browserVisible"
            mode="single"
            :collection="mediaCollection"
            @select="insertMedia"
        />
    </div>
</template>

<style scoped>
:deep(.ql-editor) {
    direction: rtl;
    min-height: 320px;
    text-align: right;
}

.html-source-editor {
    min-height: 320px;
    direction: ltr;
    text-align: left;
    line-height: 1.7;
    white-space: pre;
}
</style>
