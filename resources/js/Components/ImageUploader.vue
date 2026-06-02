<script setup>
import { computed, ref, watch } from 'vue';
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';

const props = defineProps({
    modelValue: { type: [Number, String, Array, null], default: null },
    existingImages: { type: Array, default: () => [] },
    mode: { type: String, default: 'single' },
    title: { type: String, required: true },
    subtitle: { type: String, default: 'از کتابخانه رسانه انتخاب کنید یا تصویر جدید آپلود کنید' },
    emptyText: { type: String, default: 'هنوز تصویری انتخاب نشده است.' },
    error: { type: String, default: '' },
    removable: { type: Boolean, default: true },
    sortable: { type: Boolean, default: true }
});

const emit = defineEmits(['update:modelValue', 'remove-existing']);

const browserVisible = ref(false);
const previewDialog = ref(false);
const previewImage = ref(null);
const selectedMedia = ref([]);

const isMultiple = computed(() => props.mode === 'multiple');

const selectedIds = computed(() => {
    if (isMultiple.value) {
        return Array.isArray(props.modelValue) ? props.modelValue.filter(Boolean).map(Number) : [];
    }

    return props.modelValue ? [Number(props.modelValue)] : [];
});

const normalizedExistingImages = computed(() => props.existingImages.map((image, index) => ({
    id: image.id ?? `existing-${index}`,
    name: image.name ?? 'تصویر فعلی',
    url: image.url ?? image.src ?? image.main_image_url,
    size: image.size,
    isNew: false,
    raw: image
})).filter((image) => image.url));

const selectedPreviews = computed(() => {
    const ids = selectedIds.value;

    return selectedMedia.value
        .filter((media) => ids.includes(Number(media.id)))
        .map((media) => ({
            id: `media-${media.id}`,
            mediaId: Number(media.id),
            name: media.original_name ?? media.filename ?? 'تصویر انتخابی',
            url: media.url,
            size: media.size,
            isNew: true,
            raw: media
        }));
});

const allImages = computed(() => [...normalizedExistingImages.value, ...selectedPreviews.value]);

const chooseMedia = () => {
    browserVisible.value = true;
};

const onMediaSelected = (mediaItems) => {
    const items = Array.isArray(mediaItems) ? mediaItems : [mediaItems].filter(Boolean);

    if (!items.length) {
        return;
    }

    if (isMultiple.value) {
        const byId = new Map(selectedMedia.value.map((media) => [Number(media.id), media]));
        const nextIds = [...selectedIds.value];

        items.forEach((media) => {
            const id = Number(media.id);
            byId.set(id, media);

            if (!nextIds.includes(id)) {
                nextIds.push(id);
            }
        });

        selectedMedia.value = Array.from(byId.values());
        emit('update:modelValue', nextIds);
        return;
    }

    selectedMedia.value = [items[0]];
    emit('update:modelValue', Number(items[0].id));
};

const removeSelectedMedia = (image) => {
    if (isMultiple.value) {
        const nextIds = selectedIds.value.filter((id) => id !== image.mediaId);
        selectedMedia.value = selectedMedia.value.filter((media) => nextIds.includes(Number(media.id)));
        emit('update:modelValue', nextIds);
        return;
    }

    selectedMedia.value = [];
    emit('update:modelValue', null);
};

const removeImage = (image) => {
    if (!props.removable) {
        return;
    }

    if (image.isNew) {
        removeSelectedMedia(image);
        return;
    }

    emit('remove-existing', image.raw ?? image);
};

const moveNew = (image, direction) => {
    if (!image.isNew || !isMultiple.value || !props.sortable) {
        return;
    }

    const index = selectedMedia.value.findIndex((media) => Number(media.id) === image.mediaId);
    const target = index + direction;

    if (index < 0 || target < 0 || target >= selectedMedia.value.length) {
        return;
    }

    const next = [...selectedMedia.value];
    [next[index], next[target]] = [next[target], next[index]];
    selectedMedia.value = next;
    emit('update:modelValue', next.map((media) => Number(media.id)));
};

const openPreview = (image) => {
    previewImage.value = image.url;
    previewDialog.value = true;
};

const formatSize = (size) => {
    if (!size) {
        return 'تصویر ذخیره شده';
    }

    return `${Math.round(size / 1024)} کیلوبایت`;
};

watch(() => props.modelValue, (value) => {
    const isEmpty = Array.isArray(value) ? value.length === 0 : !value;

    if (isEmpty) {
        selectedMedia.value = [];
    }
});
</script>

<template>
    <div class="image-uploader">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h5 class="text-base font-semibold">{{ title }}</h5>
                <p class="text-sm text-surface-500">{{ subtitle }}</p>
            </div>
            <Tag :value="isMultiple ? 'چند تصویر' : 'یک تصویر'" severity="secondary" />
        </div>

        <div class="picker-actions">
            <Button
                type="button"
                label="انتخاب از رسانه‌ها"
                icon="pi pi-images"
                @click="chooseMedia"
            />
        </div>

        <Message v-if="error" severity="error" class="mt-3">
            {{ error }}
        </Message>

        <div v-if="allImages.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            <div v-for="image in allImages" :key="image.id" class="image-card">
                <Image :src="image.url" imageClass="image-card__img" preview />
                <div class="mt-2 min-w-0">
                    <div class="truncate text-sm font-medium">{{ image.name }}</div>
                    <div class="text-xs text-surface-500">{{ formatSize(image.size) }}</div>
                </div>
                <div class="mt-2 flex items-center justify-between gap-1">
                    <div class="flex items-center gap-1">
                        <Button type="button" icon="pi pi-search" rounded text severity="secondary" @click="openPreview(image)" />
                        <Button v-if="isMultiple && image.isNew && sortable" type="button" icon="pi pi-arrow-up" rounded text severity="secondary" @click="moveNew(image, -1)" />
                        <Button v-if="isMultiple && image.isNew && sortable" type="button" icon="pi pi-arrow-down" rounded text severity="secondary" @click="moveNew(image, 1)" />
                    </div>
                    <Button v-if="removable" type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeImage(image)" />
                </div>
            </div>
        </div>

        <div v-else class="mt-4 rounded-md border border-dashed border-surface-300 p-4 text-center text-sm text-surface-500">
            {{ emptyText }}
        </div>

        <MediaBrowser
            v-model:visible="browserVisible"
            :mode="mode"
            @select="onMediaSelected"
        />

        <Dialog v-model:visible="previewDialog" modal header="پیش‌نمایش تصویر" :style="{ width: '44rem', maxWidth: '95vw' }">
            <img v-if="previewImage" :src="previewImage" class="max-h-[70vh] w-full rounded-md object-contain" alt="پیش‌نمایش تصویر" />
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

.picker-actions {
    display: flex;
    min-height: 5.5rem;
    align-items: center;
    justify-content: center;
    border: 1px dashed var(--surface-border);
    border-radius: 8px;
    background: color-mix(in srgb, var(--surface-ground), transparent 20%);
    padding: 1rem;
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
