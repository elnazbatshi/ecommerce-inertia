<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import { useMediaLibrary } from '@/Composables/useMediaLibrary';
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';
import MediaUploader from '@/Components/Media/MediaUploader.vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    media: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const toast = useToast();
const mediaLibrary = useMediaLibrary();
const {
    mediaList,
    isLoading,
    searchMedia,
    deleteMedia: deleteMediaRequest,
    bulkDeleteMedia,
} = mediaLibrary;
mediaList.value = Array.isArray(props.media?.data) ? props.media.data : [];

const browserVisible = ref(false);
const uploaderVisible = ref(false);
const selectedMedia = ref([]);
const filters = ref({
    search: props.filters.search ?? '',
    mime_type: null,
    uploaded_by: props.filters.uploaded_by ?? null,
});

const columns = [
    { field: 'id', header: 'ID', sortable: true },
    { field: 'original_name', header: 'نام فایل', sortable: true },
    { field: 'mime_type', header: 'نوع', sortable: true },
    { field: 'size', header: 'حجم', sortable: true },
    { field: 'uploaded_by_name', header: 'آپلودکننده', sortable: true },
    { field: 'created_at', header: 'تاریخ آپلود', sortable: true },
];

const mimeTypeOptions = [
    { label: 'همه', value: null },
    { label: 'تصاویر', value: 'image' },
    { label: 'ویدیوها', value: 'video' },
    { label: 'اسناد', value: 'document' },
];

const onFilter = () => {
    searchMedia(filters.value.search, {
        mime_type: filters.value.mime_type,
        uploaded_by: filters.value.uploaded_by,
    });
};

const viewMedia = (media) => {
    window.open(media.url, '_blank');
};

const editMedia = (media) => {
    router.visit(`/admin/media/${media.id}/edit`);
};

const deleteMedia = async (media) => {
    if (confirm('آیا از حذف این رسانه اطمینان دارید؟')) {
        try {
            await deleteMediaRequest(media.id);
            selectedMedia.value = selectedMedia.value.filter((item) => Number(item.id) !== Number(media.id));
            toast.add({
                severity: 'success',
                summary: 'موفقیت',
                detail: 'رسانه با موفقیت حذف شد.',
                life: 3000
            });
        } catch (error) {
            // Error handled in composable
        }
    }
};

const bulkDelete = async () => {
    if (!selectedMedia.value.length) {
        toast.add({
            severity: 'warn',
            summary: 'هشدار',
            detail: 'هیچ رسانه‌ای انتخاب نشده است.',
            life: 3000
        });
        return;
    }

    if (confirm(`آیا از حذف ${selectedMedia.value.length} رسانه انتخاب شده اطمینان دارید؟`)) {
        try {
            await bulkDeleteMedia(selectedMedia.value.map(m => m.id));
            selectedMedia.value = [];
            toast.add({
                severity: 'success',
                summary: 'موفقیت',
                detail: 'رسانه‌ها با موفقیت حذف شدند.',
                life: 3000
            });
        } catch (error) {
            // Error handled in composable
        }
    }
};

const onUploadSuccess = () => {
    uploaderVisible.value = false;
    onFilter(); // Refresh the list
};
</script>

<template>
    <div class="media-index">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold">کتابخانه رسانه</h1>
                <p class="text-surface-600 mt-1">مدیریت فایل‌ها و تصاویر آپلود شده</p>
            </div>
            <div class="flex gap-2">
                <Button
                    label="آپلود جدید"
                    icon="pi pi-upload"
                    @click="uploaderVisible = true"
                />
                <Button
                    label="مرور رسانه‌ها"
                    icon="pi pi-images"
                    outlined
                    @click="browserVisible = true"
                />
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface-card border border-surface-border rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2">جستجو</label>
                    <InputText
                        v-model="filters.search"
                        placeholder="جستجو در نام فایل‌ها..."
                        class="w-full"
                        @keyup.enter="onFilter"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">نوع فایل</label>
                    <Dropdown
                        v-model="filters.mime_type"
                        :options="mimeTypeOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="انتخاب نوع"
                        class="w-full"
                        @change="onFilter"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">آپلودکننده</label>
                    <InputText
                        v-model="filters.uploaded_by"
                        placeholder="نام آپلودکننده..."
                        class="w-full"
                        @keyup.enter="onFilter"
                    />
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <Button
                    label="اعمال فیلتر"
                    icon="pi pi-search"
                    @click="onFilter"
                />
                <Button
                    label="پاک کردن"
                    icon="pi pi-times"
                    outlined
                    @click="filters = { search: '', mime_type: null, uploaded_by: null }; onFilter()"
                />
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selectedMedia.length" class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium">{{ selectedMedia.length }} رسانه انتخاب شده</span>
                <div class="flex gap-2">
                    <Button
                        label="حذف انتخاب شده‌ها"
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        @click="bulkDelete"
                    />
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <DataTable
            v-model:selection="selectedMedia"
            :value="mediaList"
            :loading="isLoading"
            dataKey="id"
            selectionMode="multiple"
            paginator
            :rows="15"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            tableStyle="min-width: 50rem"
        >
            <template #empty>
                <div class="text-center py-8 text-surface-500">
                    <i class="pi pi-images text-3xl mb-2 block" />
                    <p>هیچ رسانه‌ای یافت نشد</p>
                </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width: 3rem" />

            <Column field="id" header="ID" sortable style="width: 5rem" />

            <Column field="original_name" header="نام فایل" sortable>
                <template #body="slotProps">
                    <div class="flex items-center gap-3">
                        <Image
                            :src="slotProps.data.url"
                            imageClass="w-12 h-12 object-cover rounded"
                            preview
                        />
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ slotProps.data.original_name }}</div>
                            <div class="text-sm text-surface-500">{{ slotProps.data.filename }}</div>
                        </div>
                    </div>
                </template>
            </Column>

            <Column field="mime_type" header="نوع" sortable style="width: 8rem">
                <template #body="slotProps">
                    <Tag :value="slotProps.data.mime_type" severity="info" />
                </template>
            </Column>

            <Column field="size" header="حجم" sortable style="width: 8rem">
                <template #body="slotProps">
                    {{ Math.round(slotProps.data.size / 1024) }} KB
                </template>
            </Column>

            <Column field="uploaded_by_name" header="آپلودکننده" sortable />

            <Column field="created_at" header="تاریخ آپلود" sortable style="width: 10rem">
                <template #body="slotProps">
                    {{ formatJalaliDateTime(slotProps.data.created_at) }}
                </template>
            </Column>

            <Column header="عملیات" style="width: 10rem">
                <template #body="slotProps">
                    <div class="flex gap-1">
                        <Button
                            type="button"
                            icon="pi pi-eye"
                            rounded
                            text
                            severity="info"
                            @click="viewMedia(slotProps.data)"
                        />
                        <Button
                            type="button"
                            icon="pi pi-pencil"
                            rounded
                            text
                            severity="warning"
                            @click="editMedia(slotProps.data)"
                        />
                        <Button
                            type="button"
                            icon="pi pi-trash"
                            rounded
                            text
                            severity="danger"
                            @click="deleteMedia(slotProps.data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Media Browser Dialog -->
        <MediaBrowser
            v-model:visible="browserVisible"
            mode="multiple"
        />

        <!-- Media Uploader Dialog -->
        <Dialog
            v-model:visible="uploaderVisible"
            modal
            header="آپلود رسانه جدید"
            :style="{ width: '40rem' }"
        >
            <MediaUploader
                :multiple="true"
                :auto-upload="true"
                @uploaded="onUploadSuccess"
            />
        </Dialog>
    </div>
</template>

<style scoped>
.media-index {
    padding: 1.5rem;
}
</style>
