import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';

const unwrapMediaItems = (payload) => {
    const items = payload?.media?.data ?? payload?.data ?? payload?.media ?? payload;

    return Array.isArray(items) ? items : [];
};

export function useMediaLibrary() {
    const toast = useToast();
    const selectedMedia = ref([]);
    const mediaList = ref([]);
    const isLoading = ref(false);
    const isMultiple = ref(false);
    const collection = ref(null);
    const mediableType = ref(null);
    const mediableId = ref(null);

    const isSelected = computed(() => (media) => {
        return selectedMedia.value.some(item => item.id === media.id);
    });

    const toggleSelection = (media) => {
        if (isMultiple.value) {
            const index = selectedMedia.value.findIndex(item => item.id === media.id);
            if (index > -1) {
                selectedMedia.value.splice(index, 1);
            } else {
                selectedMedia.value.push(media);
            }
        } else {
            selectedMedia.value = selectedMedia.value.length && selectedMedia.value[0].id === media.id ? [] : [media];
        }
    };

    const searchMedia = async (query = '', filters = {}) => {
        isLoading.value = true;
        try {
            const params = {};
            if (query) params.search = query;
            if (filters.mime_type) params.mime_type = filters.mime_type;
            if (filters.uploaded_by) params.uploaded_by = filters.uploaded_by;
            if (filters.rows) params.rows = filters.rows;

            const response = await axios.get('/media', {
                params,
                headers: { Accept: 'application/json' },
            });
            mediaList.value = unwrapMediaItems(response.data);
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: 'خطا در جستجوی رسانه‌ها',
                life: 3000
            });
            throw error;
        } finally {
            isLoading.value = false;
        }
    };

    const attachMedia = async (mediaId, options = {}) => {
        try {
            const response = await axios.post('/media/attach', {
                media_id: mediaId,
                mediable_type: options.mediableType || mediableType.value,
                mediable_id: options.mediableId || mediableId.value,
                collection: options.collection || collection.value,
                sort_order: options.sortOrder || 0,
                is_featured: options.isFeatured || false,
                custom_properties: options.customProperties || null,
            });

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'رسانه با موفقیت متصل شد.',
                life: 3000
            });

            return response.data;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در اتصال رسانه',
                life: 5000
            });
            throw error;
        }
    };

    const detachMedia = async (mediaId, options = {}) => {
        try {
            const response = await axios.post('/media/detach', {
                media_id: mediaId,
                mediable_type: options.mediableType || mediableType.value,
                mediable_id: options.mediableId || mediableId.value,
                collection: options.collection || collection.value,
            });

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'رسانه با موفقیت جدا شد.',
                life: 3000
            });

            return response.data;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در جدا کردن رسانه',
                life: 5000
            });
            throw error;
        }
    };

    const reorderMedia = async (items) => {
        try {
            const response = await axios.post('/media/reorder', { items });

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'ترتیب رسانه‌ها ذخیره شد.',
                life: 3000
            });

            return response.data;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در مرتب‌سازی رسانه‌ها',
                life: 5000
            });
            throw error;
        }
    };

    const uploadMedia = async (files, metadata = {}) => {
        try {
            const fileList = Array.isArray(files) ? files : [files].filter(Boolean);
            const uploadedMedia = [];

            for (const file of fileList) {
                const formData = new FormData();
                formData.append('file', file);

                if (metadata.collection) formData.append('collection', metadata.collection);
                if (metadata.mediableType) formData.append('mediable_type', metadata.mediableType);
                if (metadata.mediableId) formData.append('mediable_id', metadata.mediableId);
                if (metadata.alt) formData.append('alt', metadata.alt);
                if (metadata.title) formData.append('title', metadata.title);
                if (metadata.caption) formData.append('caption', metadata.caption);
                if (metadata.description) formData.append('description', metadata.description);

                const response = await axios.post('/media/upload', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                uploadedMedia.push(response.data.media || response.data);
            }

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: `${uploadedMedia.length} رسانه با موفقیت آپلود شد.`,
                life: 3000
            });

            return Array.isArray(files) ? uploadedMedia : uploadedMedia[0];
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در آپلود رسانه',
                life: 5000
            });
            throw error;
        }
    };

    const deleteMedia = async (mediaId) => {
        try {
            const response = await axios.delete(`/media/${mediaId}`);
            const deletedId = Number(response.data?.deleted_id ?? mediaId);

            mediaList.value = mediaList.value.filter((media) => Number(media.id) !== deletedId);
            selectedMedia.value = selectedMedia.value.filter((media) => Number(media.id) !== deletedId);

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'رسانه با موفقیت حذف شد.',
                life: 3000
            });

            return response.data;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در حذف رسانه',
                life: 5000
            });
            throw error;
        }
    };

    const bulkDeleteMedia = async (mediaIds) => {
        try {
            const response = await axios.post('/media/bulk-delete', { ids: mediaIds });
            const deletedIds = (response.data?.deleted_ids ?? mediaIds).map((id) => Number(id));
            const deletedIdSet = new Set(deletedIds);

            mediaList.value = mediaList.value.filter((media) => !deletedIdSet.has(Number(media.id)));
            selectedMedia.value = selectedMedia.value.filter((media) => !deletedIdSet.has(Number(media.id)));

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'رسانه‌ها با موفقیت حذف شدند.',
                life: 3000
            });

            return response.data;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در حذف رسانه‌ها',
                life: 5000
            });
            throw error;
        }
    };

    const updateMedia = async (mediaId, data) => {
        try {
            const response = await axios.put(`/media/${mediaId}`, data);

            toast.add({
                severity: 'success',
                summary: 'موفق',
                detail: 'اطلاعات رسانه به‌روزرسانی شد.',
                life: 3000
            });

            return response.data.media;
        } catch (error) {
            toast.add({
                severity: 'error',
                summary: 'خطا',
                detail: error.response?.data?.message || 'خطا در به‌روزرسانی رسانه',
                life: 5000
            });
            throw error;
        }
    };

    return {
        selectedMedia,
        mediaList,
        isLoading,
        isMultiple,
        collection,
        mediableType,
        mediableId,
        isSelected,
        toggleSelection,
        searchMedia,
        attachMedia,
        detachMedia,
        reorderMedia,
        uploadMedia,
        deleteMedia,
        bulkDeleteMedia,
        updateMedia,
    };
}
