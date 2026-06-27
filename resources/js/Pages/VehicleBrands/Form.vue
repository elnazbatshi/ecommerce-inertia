<script setup>
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
    processing: { type: Boolean, default: false },
    vehicleTypeOptions: { type: Array, default: () => [] },
    initialLogo: { type: Object, default: null }
});

const emit = defineEmits(['submit']);
const logoBrowserVisible = ref(false);
const logoPreview = ref(props.initialLogo);
const autoSlug = ref(!props.form.slug);

const slugify = (value) =>
    String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9\u0600-\u06FF\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

watch(
    () => props.form.name,
    (value) => {
        if (autoSlug.value) {
            props.form.slug = slugify(value);
        }
    }
);

const onSlugInput = (value) => {
    props.form.slug = slugify(value);
    autoSlug.value = !props.form.slug;
};

const chooseLogo = (items) => {
    const [media] = items;
    if (!media) return;
    props.form.logo_media_id = media.id;
    logoPreview.value = { id: media.id, url: media.url, original_name: media.original_name };
};

const clearLogo = () => {
    props.form.logo_media_id = null;
    logoPreview.value = null;
};

const logoAlt = computed(() => logoPreview.value?.original_name || 'لوگو');
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">نام برند</label>
                <InputText v-model="form.name" class="w-full" />
                <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نامک</label>
                <InputText :model-value="form.slug" class="w-full" @update:model-value="onSlugInput" />
                <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نوع وسیله</label>
                <Dropdown v-model="form.vehicle_type_id" :options="vehicleTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.vehicle_type_id" class="text-red-600">{{ form.errors.vehicle_type_id }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">کشور</label>
                <InputText v-model="form.country" class="w-full" />
                <small v-if="form.errors.country" class="text-red-600">{{ form.errors.country }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">ترتیب</label>
                <InputNumber v-model="form.sort_order" class="w-full" :min="0" />
                <small v-if="form.errors.sort_order" class="text-red-600">{{ form.errors.sort_order }}</small>
            </div>
            <div class="flex items-center gap-3 pt-8">
                <ToggleSwitch v-model="form.is_active" />
                <span>فعال</span>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">توضیحات</label>
                <Textarea v-model="form.description" class="w-full" rows="4" />
                <small v-if="form.errors.description" class="text-red-600">{{ form.errors.description }}</small>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">لوگو</label>
                <div class="flex flex-wrap items-center gap-3 rounded-md border border-surface-200 p-3">
                    <img v-if="logoPreview?.url" :src="logoPreview.url" :alt="logoAlt" class="h-16 w-16 rounded-md object-cover" />
                    <div v-else class="flex h-16 w-16 items-center justify-center rounded-md bg-surface-100 text-surface-500">
                        <i class="pi pi-image" />
                    </div>
                    <Button type="button" label="انتخاب از رسانه" icon="pi pi-images" outlined @click="logoBrowserVisible = true" />
                    <Button v-if="logoPreview" type="button" label="حذف" icon="pi pi-times" severity="danger" text @click="clearLogo" />
                </div>
                <small v-if="form.errors.logo_media_id" class="text-red-600">{{ form.errors.logo_media_id }}</small>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="processing" />
        </div>

        <MediaBrowser v-model:visible="logoBrowserVisible" mode="single" @select="chooseLogo" />
    </form>
</template>
