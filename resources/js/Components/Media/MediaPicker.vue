<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useMediaLibrary } from '@/Composables/useMediaLibrary';
import MediaBrowser from './MediaBrowser.vue';

const props = defineProps({
    modelValue: { type: [Array, Object, null], default: null },
    mode: { type: String, default: 'single' },
    collection: { type: String, default: null },
    mediableType: { type: String, required: true },
    mediableId: { type: [String, Number], required: true },
    existingImages: { type: Array, default: () => [] },
    title: { type: String, default: 'انتخاب تصویر' },
    subtitle: { type: String, default: 'تصاویر موجود را انتخاب کنید یا تصویر جدیدی آپلود کنید' },
    emptyText: { type: String, default: 'هیچ تصویری انتخاب نشده است' },
    error: { type: String, default: '' },
    removable: { type: Boolean, default: true },
    sortable: { type: Boolean, default: true },
    showUpload: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'remove-existing']);
const toast = useToast();
const mediaLibrary = useMediaLibrary();
const browserVisible = ref(false);
const previewImage = ref(null);
const previewDialog = ref(false);

const isMultiple = computed(() => props.mode === 'multiple');
const selectedFiles = computed(() => {
    if (isMultiple.value) {
        return Array.isArray(props.modelValue) ? props.modelValue : [];
    }
    return props.modelValue ? [props.modelValue] : [];
});

const normalizedExistingImages = computed(() => props.existingImages.map((image, index) => ({
    id: image.id ?? `existing-${index}`,
    name: image.name ?? 'تصویر فعلی',
    url: image.url ?? image.src,
    size: image.size,
    isNew: false,
    raw: image
})).filter((image) => image.url));

const allImages = computed(() => [...normalizedExistingImages.value, ...selectedFiles.value]);

const openBrowser = () => {
    mediaLibrary.isMultiple.value = isMultiple.value;
    mediaLibrary.collection.value = props.collection;
    mediaLibrary.mediableType.value = props.mediableType;
    mediaLibrary.mediableId.value = props.mediableId;
    mediaLibrary.selectedMedia.value = [];
    browserVisible.value = true;
};

const onMediaSelected = async (selectedMedia) => {
    try {
        for (const media of selectedMedia) {
            await mediaLibrary.attachMedia(media.id, {
                mediableType: props.mediableType,
                mediableId: props.mediableId,
                collection: props.collection,
                sortOrder: selectedFiles.value.length,
                isFeatured: false,
            });
        }

        emit('update:modelValue', isMultiple.value ? [...selectedFiles.value, ...selectedMedia] : selectedMedia[0]);
    } catch (error) {
        // Error handled in composable
    }
};

const removeImage = (image) => {
    if (!props.removable) return;

    if (image.isNew) {
        const index = selectedFiles.value.findIndex(item => item.id === image.id);
        if (index > -1) {
            emit('update:modelValue', isMultiple.value ? selectedFiles.value.filter((_, i) => i !== index) : null);
        }
    } else {
        emit('remove-existing', image.raw ?? image);
    }
};

const moveImage = (image, direction) => {
    if (!image.isNew || !isMultiple.value || !props.sortable) return;

    const index = selectedFiles.value.findIndex(item => item.id === image.id);
    const target = index + direction;
    if (target < 0 || target >= selectedFiles.value.length) return;

    const next = [...selectedFiles.value];
    [next[index], next[target]] = [next[target], next[index]];
    emit('update:modelValue', next);
};

const openPreview = (image) => {
    previewImage.value = image.url;
    previewDialog.value = true;
};

const formatSize = (size) => {
    if (!size) return 'تصویر ذخیره‌شده';
    return `${Math.round(size / 1024)} کیلوبایت`;
};
</script>

<template>
    <div class="media-picker">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold">{{ title }}</h3>
                <p class="text-sm text-surface-500">{{ subtitle }}</p>
            </div>
            <div class="flex gap-2">
                <Button
                    v-if="showUpload"
                    label="آپلود جدید"
                    icon="pi pi-upload"
                    size="small"
                    @click="openBrowser"
                />
                <Button
                    label="انتخاب از کتابخانه"
                    icon="pi pi-images"
                    size="small"
                    outlined
                    @click="openBrowser"
                />
            </div>
        </div>

        <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

        <div v-if="allImages.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="image in allImages" :key="image.id" class="image-card">
                <Image :src="image.url" imageClass="image-card__img" preview />
                <div class="mt-2 min-w-0">
                    <div class="truncate text-sm font-medium">{{ image.name }}</div>
                    <div class="text-xs text-surface-500">{{ formatSize(image.size) }}</div>
                </div>
                <div class="mt-2 flex items-center justify-between gap-1">
                    <div class="flex items-center gap-1">
                        <Button type="button" icon="pi pi-search" rounded text severity="secondary" @click="openPreview(image)" />
                        <Button
                            v-if="isMultiple && image.isNew && sortable"
                            type="button"
                            icon="pi pi-arrow-up"
                            rounded
                            text
                            severity="secondary"
                            @click="moveImage(image, -1)"
                        />
                        <Button
                            v-if="isMultiple && image.isNew && sortable"
                            type="button"
                            icon="pi pi-arrow-down"
                            rounded
                            text
                            severity="secondary"
                            @click="moveImage(image, 1)"
                        />
                    </div>
                    <Button
                        v-if="removable"
                        type="button"
                        icon="pi pi-trash"
                        rounded
                        text
                        severity="danger"
                        @click="removeImage(image)"
                    />
                </div>
            </div>
        </div>

        <div v-else class="rounded-md border border-dashed border-surface-300 p-8 text-center text-sm text-surface-500">
            <i class="pi pi-images text-2xl mb-2 block" />
            {{ emptyText }}
        </div>

        <!-- Media Browser -->
        <MediaBrowser
            v-model:visible="browserVisible"
            :mode="mode"
            :collection="collection"
            :mediable-type="mediableType"
            :mediable-id="mediableId"
            @select="onMediaSelected"
        />

        <!-- Preview Dialog -->
        <Dialog
            v-model:visible="previewDialog"
            modal
            header="پیش‌نمایش تصویر"
            :style="{ width: '44rem', maxWidth: '95vw' }"
        >
            <img v-if="previewImage" :src="previewImage" class="max-h-[70vh] w-full rounded-md object-contain" alt="پیش‌نمایش تصویر" />
        </Dialog>
    </div>
</template>

<style scoped>
.media-picker {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: var(--surface-card);
    padding: 1rem;
}

.image-card {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    padding: 0.75rem;
    background: var(--surface-card);
    transition: border-color 0.2s;
}

.image-card:hover {
    border-color: var(--primary-color);
}

.image-card__img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}
</style>