<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useMediaLibrary } from '@/Composables/useMediaLibrary';

const props = defineProps({
    visible: { type: Boolean, default: false },
    mode: { type: String, default: 'single' },
    collection: { type: String, default: null },
    mediableType: { type: String, default: null },
    mediableId: { type: [String, Number], default: null },
});

const emit = defineEmits(['update:visible', 'select']);
const toast = useToast();
const mediaLibrary = useMediaLibrary();
const {
    selectedMedia,
    mediaList,
    searchMedia: fetchMedia,
    uploadMedia,
} = mediaLibrary;
const searchTerm = ref('');
const isLoading = ref(false);
const uploadDialog = ref(false);
const selectedFiles = ref([]);

const isMultiple = computed(() => props.mode === 'multiple');

const toggleSelection = (media) => {
    if (isMultiple.value) {
        const index = selectedMedia.value.findIndex(item => item.id === media.id);
        if (index > -1) {
            selectedMedia.value.splice(index, 1);
        } else {
            selectedMedia.value.push(media);
        }
    } else {
        selectedMedia.value = [media];
    }
};

const isSelected = (media) => {
    return selectedMedia.value.some(item => item.id === media.id);
};

const searchMedia = async () => {
    isLoading.value = true;
    try {
        await fetchMedia(searchTerm.value);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'خطا',
            detail: 'خطا در جستجوی رسانه‌ها',
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
};

const uploadFiles = async (event = null) => {
    const files = Array.from(event?.files ?? selectedFiles.value ?? []);

    if (!files.length) return;

    try {
        const uploaded = await uploadMedia(files, {
            collection: props.collection,
            mediableType: props.mediableType,
            mediableId: props.mediableId,
        });

        const uploadedItems = Array.isArray(uploaded) ? uploaded : [uploaded].filter(Boolean);

        if (isMultiple.value) {
            const byId = new Map(selectedMedia.value.map((media) => [Number(media.id), media]));
            uploadedItems.forEach((media) => byId.set(Number(media.id), media));
            selectedMedia.value = Array.from(byId.values());
        } else if (uploadedItems[0]) {
            selectedMedia.value = [uploadedItems[0]];
        }

        selectedFiles.value = [];
        uploadDialog.value = false;
        await searchMedia(); // Refresh the list
    } catch (error) {
        // Error handled in composable
    }
};

const selectAndClose = () => {
    if (!selectedMedia.value.length) {
        toast.add({
            severity: 'warn',
            summary: 'هشدار',
            detail: 'هیچ رسانه‌ای انتخاب نشده است.',
            life: 3000
        });
        return;
    }

    emit('select', selectedMedia.value);
    emit('update:visible', false);
};

const closeBrowser = () => {
    emit('update:visible', false);
};

onMounted(() => {
    if (props.visible) {
        searchMedia();
    }
});

watch(() => props.visible, (visible) => {
    if (visible && !mediaList.value.length) {
        searchMedia();
    }
});
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :header="`انتخاب رسانه - ${isMultiple ? 'چندگانه' : 'تک'}`"
        :style="{ width: '80rem', maxWidth: '95vw' }"
        @update:visible="closeBrowser"
    >
        <div class="space-y-4">
            <!-- Search and Actions -->
            <div class="flex items-center gap-4">
                <InputText
                    v-model="searchTerm"
                    placeholder="جستجو در رسانه‌ها..."
                    class="flex-1"
                    @keyup.enter="searchMedia"
                />
                <Button
                    label="جستجو"
                    icon="pi pi-search"
                    :loading="isLoading"
                    @click="searchMedia"
                />
                <Button
                    label="آپلود جدید"
                    icon="pi pi-upload"
                    outlined
                    @click="uploadDialog = true"
                />
            </div>

            <!-- Media Grid -->
            <div class="media-grid">
                <div
                    v-for="media in mediaList"
                    :key="media.id"
                    :class="['media-item', { 'selected': isSelected(media) }]"
                    @click="toggleSelection(media)"
                >
                    <Image :src="media.url" imageClass="media-item__img" />
                    <div class="media-item__overlay" v-if="isSelected(media)">
                        <i class="pi pi-check text-white text-xl" />
                    </div>
                    <div class="media-item__info">
                        <div class="truncate text-sm font-medium">{{ media.original_name }}</div>
                        <div class="text-xs text-surface-500">{{ Math.round(media.size / 1024) }}KB</div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!mediaList.length && !isLoading" class="text-center py-8 text-surface-500">
                <i class="pi pi-images text-3xl mb-2 block" />
                <p>هیچ رسانه‌ای یافت نشد</p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
                <ProgressSpinner />
                <p class="mt-2 text-surface-500">در حال بارگذاری...</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <div class="text-sm text-surface-500">
                    {{ selectedMedia.length }} رسانه انتخاب شده
                </div>
                <div class="flex gap-2">
                    <Button
                        label="لغو"
                        icon="pi pi-times"
                        outlined
                        @click="closeBrowser"
                    />
                    <Button
                        label="انتخاب"
                        icon="pi pi-check"
                        :disabled="!selectedMedia.length"
                        @click="selectAndClose"
                    />
                </div>
            </div>
        </div>

        <!-- Upload Dialog -->
        <Dialog
            v-model:visible="uploadDialog"
            modal
            header="آپلود رسانه جدید"
            :style="{ width: '30rem' }"
        >
            <FileUpload
                v-model="selectedFiles"
                mode="basic"
                accept="image/*"
                :maxFileSize="10000000"
                :multiple="isMultiple"
                chooseLabel="انتخاب فایل‌ها"
                uploadLabel="آپلود"
                cancelLabel="لغو"
                :auto="false"
                @select="uploadFiles"
            />
        </Dialog>
    </Dialog>
</template>

<style scoped>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    max-height: 60vh;
    overflow-y: auto;
}

.media-item {
    position: relative;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s;
}

.media-item:hover {
    border-color: var(--primary-color);
}

.media-item.selected {
    border-color: var(--primary-color);
}

.media-item__img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.media-item__overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 123, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-item__info {
    padding: 0.5rem;
    background: var(--surface-card);
    border-top: 1px solid var(--surface-border);
}
</style>
