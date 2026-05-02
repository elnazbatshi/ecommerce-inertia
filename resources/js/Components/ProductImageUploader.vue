<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [File, Array, null],
        default: null
    },
    existingImages: {
        type: Array,
        default: () => []
    },
    mode: {
        type: String,
        default: 'single'
    },
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: 'JPG، PNG یا WebP تا ۲ مگابایت'
    },
    error: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'remove-existing']);
const previewDialog = ref(false);
const previewImage = ref(null);
const localError = ref('');
const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp'];
const maxSize = 2 * 1024 * 1024;

const isMultiple = computed(() => props.mode === 'multiple');
const selectedFiles = computed(() => {
    if (isMultiple.value) {
        return Array.isArray(props.modelValue) ? props.modelValue : [];
    }

    return props.modelValue ? [props.modelValue] : [];
});

const selectedPreviews = computed(() => selectedFiles.value.map((file, index) => ({
    id: `new-${index}-${file.name}`,
    name: file.name,
    size: file.size,
    url: URL.createObjectURL(file),
    file,
    isNew: true
})));

const allImages = computed(() => [
    ...props.existingImages.map((image) => ({ ...image, isNew: false })),
    ...selectedPreviews.value
]);

const validateFile = (file) => {
    if (!acceptedTypes.includes(file.type)) {
        return 'فرمت فایل باید jpg، png یا webp باشد.';
    }

    if (file.size > maxSize) {
        return 'حجم فایل نباید بیشتر از ۲ مگابایت باشد.';
    }

    return '';
};

const onSelect = (event) => {
    localError.value = '';
    const files = Array.from(event.files ?? []);
    const validFiles = [];

    for (const file of files) {
        const error = validateFile(file);
        if (error) {
            localError.value = error;
            continue;
        }
        validFiles.push(file);
    }

    if (!validFiles.length) {
        return;
    }

    if (isMultiple.value) {
        emit('update:modelValue', [...selectedFiles.value, ...validFiles]);
        return;
    }

    emit('update:modelValue', validFiles[0]);
};

const removeNew = (index) => {
    if (isMultiple.value) {
        emit('update:modelValue', selectedFiles.value.filter((_, fileIndex) => fileIndex !== index));
        return;
    }

    emit('update:modelValue', null);
};

const removeImage = (image) => {
    if (image.isNew) {
        const index = selectedPreviews.value.findIndex((item) => item.id === image.id);
        removeNew(index);
        return;
    }

    emit('remove-existing', image);
};

const openPreview = (image) => {
    previewImage.value = image.url || image.main_image_url;
    previewDialog.value = true;
};

const formatSize = (size) => {
    if (!size) {
        return '';
    }

    return `${Math.round(size / 1024)} KB`;
};
</script>

<template>
    <div class="image-uploader">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold">{{ title }}</h3>
                <p class="text-sm text-surface-500">{{ subtitle }}</p>
            </div>
            <Tag :value="isMultiple ? 'چند تصویر' : 'یک تصویر'" severity="secondary" />
        </div>

        <FileUpload
            mode="advanced"
            customUpload
            :multiple="isMultiple"
            :accept="'.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp'"
            :maxFileSize="maxSize"
            chooseLabel="انتخاب تصویر"
            uploadLabel="افزودن"
            cancelLabel="پاک کردن"
            :showUploadButton="false"
            :showCancelButton="false"
            @select="onSelect"
        >
            <template #empty>
                <div class="upload-empty">
                    <i class="pi pi-cloud-upload text-4xl text-primary" />
                    <div class="mt-3 font-medium">تصویر را بکشید یا کلیک کنید</div>
                    <div class="mt-1 text-sm text-surface-500">JPG، PNG یا WebP تا ۲ مگابایت</div>
                </div>
            </template>
            <template #content>
                <div class="upload-empty compact">
                    <i class="pi pi-cloud-upload text-2xl text-primary" />
                    <span>تصویر را بکشید یا کلیک کنید</span>
                </div>
            </template>
        </FileUpload>

        <Message v-if="localError || error" severity="error" class="mt-3">
            {{ localError || error }}
        </Message>

        <div v-if="allImages.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="image in allImages" :key="image.id" class="image-card">
                <Image
                    :src="image.url || image.main_image_url"
                    imageClass="image-card__img"
                    preview
                />
                <div class="mt-2 min-w-0">
                    <div class="truncate text-sm font-medium">{{ image.name || 'تصویر محصول' }}</div>
                    <div class="text-xs text-surface-500">{{ formatSize(image.size) }}</div>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <Button type="button" icon="pi pi-search" rounded text severity="secondary" @click="openPreview(image)" />
                    <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeImage(image)" />
                </div>
            </div>
        </div>

        <Dialog v-model:visible="previewDialog" modal header="پیش‌نمایش تصویر" :style="{ width: '42rem', maxWidth: '95vw' }">
            <img v-if="previewImage" :src="previewImage" class="max-h-[70vh] w-full rounded-md object-contain" alt="preview" />
        </Dialog>
    </div>
</template>

<style scoped>
.image-uploader {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: var(--surface-card);
    padding: 1rem;
}

.upload-empty {
    display: flex;
    min-height: 9rem;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    border: 1px dashed var(--surface-border);
    border-radius: 8px;
    background: color-mix(in srgb, var(--surface-ground), transparent 20%);
    padding: 1rem;
    text-align: center;
}

.upload-empty.compact {
    min-height: 5rem;
    gap: 0.5rem;
}

.image-card {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: var(--surface-ground);
    padding: 0.75rem;
}

:deep(.image-card__img) {
    height: 9rem;
    width: 100%;
    border-radius: 6px;
    object-fit: cover;
}
</style>
