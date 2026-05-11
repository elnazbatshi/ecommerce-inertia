<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useMediaLibrary } from '@/Composables/useMediaLibrary';

const props = defineProps({
    collection: { type: String, default: null },
    mediableType: { type: String, default: null },
    mediableId: { type: [String, Number], default: null },
    multiple: { type: Boolean, default: true },
    maxFileSize: { type: Number, default: 10000000 }, // 10MB
    accept: { type: String, default: 'image/*' },
    autoUpload: { type: Boolean, default: true },
    showPreview: { type: Boolean, default: true },
});

const emit = defineEmits(['uploaded', 'error']);
const toast = useToast();
const mediaLibrary = useMediaLibrary();
const selectedFiles = ref([]);
const isUploading = ref(false);

const onSelect = async (event) => {
    if (props.autoUpload && event.files.length) {
        await uploadFiles(event.files);
    }
};

const uploadFiles = async (files = selectedFiles.value) => {
    if (!files.length) return;

    isUploading.value = true;
    try {
        const uploadedMedia = await mediaLibrary.uploadMedia(files, {
            collection: props.collection,
            mediableType: props.mediableType,
            mediableId: props.mediableId,
        });

        selectedFiles.value = [];
        emit('uploaded', uploadedMedia);

        toast.add({
            severity: 'success',
            summary: 'موفقیت',
            detail: `${files.length} فایل با موفقیت آپلود شد.`,
            life: 3000
        });
    } catch (error) {
        emit('error', error);
        // Error handled in composable
    } finally {
        isUploading.value = false;
    }
};

const removeFile = (file) => {
    selectedFiles.value = selectedFiles.value.filter(f => f !== file);
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <div class="media-uploader">
        <FileUpload
            v-model="selectedFiles"
            mode="advanced"
            :accept="accept"
            :maxFileSize="maxFileSize"
            :multiple="multiple"
            chooseLabel="انتخاب فایل‌ها"
            uploadLabel="آپلود"
            cancelLabel="لغو"
            :auto="autoUpload"
            :showUploadButton="!autoUpload"
            :showCancelButton="false"
            @select="onSelect"
            @upload="uploadFiles"
        >
            <template #empty>
                <div class="text-center py-8">
                    <i class="pi pi-cloud-upload text-4xl text-surface-400 mb-4 block" />
                    <p class="text-surface-600 mb-2">فایل‌های خود را اینجا بکشید و رها کنید</p>
                    <p class="text-sm text-surface-500">یا برای انتخاب فایل کلیک کنید</p>
                </div>
            </template>

            <template #file="{ file, index }">
                <div class="file-item">
                    <div class="file-info">
                        <img v-if="file.type.startsWith('image/')" :src="file.objectURL" class="file-preview" alt="پیش‌نمایش" />
                        <div class="file-details">
                            <div class="file-name">{{ file.name }}</div>
                            <div class="file-size">{{ formatSize(file.size) }}</div>
                        </div>
                    </div>
                    <Button
                        type="button"
                        icon="pi pi-times"
                        rounded
                        text
                        severity="danger"
                        @click="removeFile(file)"
                    />
                </div>
            </template>
        </FileUpload>

        <div v-if="!autoUpload && selectedFiles.length" class="mt-4 flex justify-end">
            <Button
                label="آپلود فایل‌ها"
                icon="pi pi-upload"
                :loading="isUploading"
                @click="uploadFiles"
            />
        </div>
    </div>
</template>

<style scoped>
.media-uploader :deep(.p-fileupload-content) {
    border: 2px dashed var(--surface-border);
    border-radius: 8px;
    background: var(--surface-section);
    transition: border-color 0.2s;
}

.media-uploader :deep(.p-fileupload-content:hover) {
    border-color: var(--primary-color);
}

.file-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: var(--surface-card);
    margin-bottom: 0.5rem;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.file-preview {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.file-details {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-weight: 500;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-size {
    font-size: 0.75rem;
    color: var(--text-color-secondary);
}
</style>
