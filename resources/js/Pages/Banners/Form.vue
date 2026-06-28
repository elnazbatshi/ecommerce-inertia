<script setup>
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';
import PersianDateTimePicker from '@/Components/Date/PersianDateTimePicker.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
});

defineEmits(['submit']);

const imageBrowser = ref(false);
const mobileImageBrowser = ref(false);
const imageMedia = ref(props.form.image_media ?? null);
const mobileImageMedia = ref(props.form.mobile_image_media ?? null);

const imageUrl = computed(() => imageMedia.value?.url ?? null);
const mobileImageUrl = computed(() => mobileImageMedia.value?.url ?? null);

const chooseImage = (items) => {
    const media = items[0] ?? null;
    imageMedia.value = media;
    props.form.image_media_id = media?.id ?? null;
};

const chooseMobileImage = (items) => {
    const media = items[0] ?? null;
    mobileImageMedia.value = media;
    props.form.mobile_image_media_id = media?.id ?? null;
};

const clearImage = () => {
    imageMedia.value = null;
    props.form.image_media_id = null;
};

const clearMobileImage = () => {
    mobileImageMedia.value = null;
    props.form.mobile_image_media_id = null;
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_0.85fr]">
            <div class="card space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">عنوان</label>
                        <InputText v-model="form.title" class="w-full" />
                        <small v-if="form.errors.title" class="text-red-600">{{ form.errors.title }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">زیرعنوان</label>
                        <InputText v-model="form.subtitle" class="w-full" />
                        <small v-if="form.errors.subtitle" class="text-red-600">{{ form.errors.subtitle }}</small>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">توضیحات</label>
                        <Textarea v-model="form.description" rows="4" class="w-full" />
                        <small v-if="form.errors.description" class="text-red-600">{{ form.errors.description }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">لینک</label>
                        <InputText v-model="form.link_url" dir="ltr" class="w-full text-left" placeholder="/products" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">متن دکمه</label>
                        <InputText v-model="form.button_text" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">رنگ پس‌زمینه</label>
                        <InputText v-model="form.background_color" dir="ltr" class="w-full text-left" placeholder="#111111" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">رنگ متن</label>
                        <InputText v-model="form.text_color" dir="ltr" class="w-full text-left" placeholder="#ffffff" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">ترتیب</label>
                        <InputNumber v-model="form.sort_order" class="w-full" inputClass="w-full" :min="0" />
                    </div>
                    <div class="flex items-center gap-3 pt-7">
                        <ToggleSwitch v-model="form.is_active" />
                        <span class="font-medium">فعال</span>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">شروع نمایش</label>
                        <PersianDateTimePicker v-model="form.starts_at" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">پایان نمایش</label>
                        <PersianDateTimePicker v-model="form.ends_at" />
                        <small v-if="form.errors.ends_at" class="text-red-600">{{ form.errors.ends_at }}</small>
                    </div>
                </div>
            </div>

            <div class="card space-y-4">
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="font-medium">تصویر دسکتاپ</label>
                        <Button type="button" label="انتخاب" icon="pi pi-images" size="small" outlined @click="imageBrowser = true" />
                    </div>
                    <img v-if="imageUrl" :src="imageUrl" class="h-44 w-full rounded-lg object-cover" alt="" />
                    <div v-else class="flex h-44 items-center justify-center rounded-lg border border-dashed border-surface-300 text-surface-500">
                        تصویر انتخاب نشده
                    </div>
                    <Button v-if="imageUrl" type="button" label="حذف تصویر" icon="pi pi-trash" severity="danger" text class="mt-2" @click="clearImage" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="font-medium">تصویر موبایل</label>
                        <Button type="button" label="انتخاب" icon="pi pi-images" size="small" outlined @click="mobileImageBrowser = true" />
                    </div>
                    <img v-if="mobileImageUrl" :src="mobileImageUrl" class="h-44 w-full rounded-lg object-cover" alt="" />
                    <div v-else class="flex h-44 items-center justify-center rounded-lg border border-dashed border-surface-300 text-surface-500">
                        اختیاری
                    </div>
                    <Button v-if="mobileImageUrl" type="button" label="حذف تصویر" icon="pi pi-trash" severity="danger" text class="mt-2" @click="clearMobileImage" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="form.processing" />
                </div>
            </div>
        </div>

        <MediaBrowser v-model:visible="imageBrowser" mode="single" collection="banner_desktop" @select="chooseImage" />
        <MediaBrowser v-model:visible="mobileImageBrowser" mode="single" collection="banner_mobile" @select="chooseMobileImage" />
    </form>
</template>
