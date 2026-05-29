<script setup>
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
    processing: { type: Boolean, default: false },
    typeOptions: { type: Array, default: () => [] },
    brandOptions: { type: Array, default: () => [] },
    initialImage: { type: Object, default: null }
});

const emit = defineEmits(['submit']);
const imageBrowserVisible = ref(false);
const imagePreview = ref(props.initialImage);
const autoSlug = ref(!props.form.slug);

const slugify = (value) =>
    String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9\u0600-\u06FF\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

watch(
    () => [props.form.name, props.form.vehicle_brand_id],
    () => {
        if (!autoSlug.value) return;
        const brand = props.brandOptions.find((item) => item.value === props.form.vehicle_brand_id);
        props.form.slug = slugify([brand?.label, props.form.name].filter(Boolean).join(' '));
    },
    { deep: true }
);

const onSlugInput = (value) => {
    props.form.slug = slugify(value);
    autoSlug.value = !props.form.slug;
};

const chooseImage = (items) => {
    const [media] = items;
    if (!media) return;
    props.form.image_media_id = media.id;
    imagePreview.value = { id: media.id, url: media.url, original_name: media.original_name };
};

const clearImage = () => {
    props.form.image_media_id = null;
    imagePreview.value = null;
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">برند</label>
                <Dropdown
                    v-model="form.vehicle_brand_id"
                    :options="brandOptions"
                    optionLabel="label"
                    optionValue="value"
                    filter
                    showClear
                    class="w-full"
                    placeholder="انتخاب برند"
                />
                <small v-if="form.errors.vehicle_brand_id" class="text-red-600">{{ form.errors.vehicle_brand_id }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نوع</label>
                <Dropdown v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.type" class="text-red-600">{{ form.errors.type }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نام</label>
                <InputText v-model="form.name" class="w-full" />
                <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نامک</label>
                <InputText :model-value="form.slug" class="w-full" @update:model-value="onSlugInput" />
                <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">سال شروع</label>
                <InputNumber v-model="form.year_from" :useGrouping="false" class="w-full" placeholder="مثال: 1403" />
                <small v-if="form.errors.year_from" class="text-red-600">{{ form.errors.year_from }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">سال پایان</label>
                <InputNumber v-model="form.year_to" :useGrouping="false" class="w-full" placeholder="مثال: 1403" />
                <small v-if="form.errors.year_to" class="text-red-600">{{ form.errors.year_to }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">موتور</label>
                <InputText v-model="form.engine" class="w-full" />
                <small v-if="form.errors.engine" class="text-red-600">{{ form.errors.engine }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">تیپ</label>
                <InputText v-model="form.trim" class="w-full" />
                <small v-if="form.errors.trim" class="text-red-600">{{ form.errors.trim }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">ترتیب</label>
                <InputNumber v-model="form.sort_order" :min="0" class="w-full" />
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
                <label class="mb-2 block font-medium">تصویر</label>
                <div class="flex flex-wrap items-center gap-3 rounded-md border border-surface-200 p-3">
                    <img v-if="imagePreview?.url" :src="imagePreview.url" class="h-16 w-16 rounded-md object-cover" :alt="imagePreview.original_name || 'vehicle-image'" />
                    <div v-else class="flex h-16 w-16 items-center justify-center rounded-md bg-surface-100 text-surface-500">
                        <i class="pi pi-image" />
                    </div>
                    <Button type="button" label="انتخاب از رسانه" icon="pi pi-images" outlined @click="imageBrowserVisible = true" />
                    <Button v-if="imagePreview" type="button" label="حذف" icon="pi pi-times" severity="danger" text @click="clearImage" />
                </div>
                <small v-if="form.errors.image_media_id" class="text-red-600">{{ form.errors.image_media_id }}</small>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="processing" />
        </div>

        <MediaBrowser v-model:visible="imageBrowserVisible" mode="single" @select="chooseImage" />
    </form>
</template>
