<script setup>
import MediaBrowser from '@/Components/Media/MediaBrowser.vue';
import PersianDateTimePicker from '@/Components/Date/PersianDateTimePicker.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    layouts: { type: Array, default: () => [] },
    placements: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'ذخیره' },
    processing: { type: Boolean, default: false }
});

const emit = defineEmits(['submit']);

const backgroundBrowser = ref(false);
const foregroundBrowser = ref(false);
const backgroundMedia = ref(props.form.background_media ?? null);
const foregroundMedia = ref(props.form.foreground_media ?? null);

const normalizeColor = (value, fallback) => {
    if (!value) return fallback;

    return String(value).startsWith('#') ? value : `#${value}`;
};

const textColor = computed(() => normalizeColor(props.form.text_color, '#ffffff'));
const accentColor = computed(() => normalizeColor(props.form.accent_color, '#D4A017'));
const buttonColor = computed(() => normalizeColor(props.form.button_color, '#D4A017'));
const backgroundUrl = computed(() => backgroundMedia.value?.url ?? backgroundMedia.value?.image_url ?? null);
const foregroundUrl = computed(() => foregroundMedia.value?.url ?? foregroundMedia.value?.image_url ?? null);
const stats = computed(() => [
    { label: props.form.stat_1_label, value: props.form.stat_1_value },
    { label: props.form.stat_2_label, value: props.form.stat_2_value },
    { label: props.form.stat_3_label, value: props.form.stat_3_value },
].filter((stat) => stat.label || stat.value));

const previewClass = computed(() => ({
    'lg:grid-cols-[0.9fr_1.1fr]': props.form.layout === 'image_left_content_right',
    'lg:grid-cols-[1.1fr_0.9fr]': props.form.layout === 'image_right_content_left',
    'lg:grid-cols-1 text-center': props.form.layout === 'content_center'
}));

const contentClass = computed(() => ({
    'lg:order-2': props.form.layout === 'image_left_content_right',
    'mx-auto max-w-3xl': props.form.layout === 'content_center'
}));

const imageClass = computed(() => ({
    'lg:order-1': props.form.layout === 'image_left_content_right',
    'hidden': props.form.layout === 'content_center'
}));

const chooseBackground = (items) => {
    const media = items[0] ?? null;
    backgroundMedia.value = media;
    props.form.background_media_id = media?.id ?? null;
};

const chooseForeground = (items) => {
    const media = items[0] ?? null;
    foregroundMedia.value = media;
    props.form.foreground_media_id = media?.id ?? null;
};

const clearBackground = () => {
    backgroundMedia.value = null;
    props.form.background_media_id = null;
};

const clearForeground = () => {
    foregroundMedia.value = null;
    props.form.foreground_media_id = null;
};

const errorFor = (field) => props.form.errors?.[field];
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-4">
                <div class="card">
                    <h2 class="mb-4 text-lg font-semibold">محتوای اصلی</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block font-medium">متن کوچک بالا</label>
                            <InputText v-model="form.eyebrow_text" class="w-full" />
                            <small v-if="errorFor('eyebrow_text')" class="text-red-600">{{ errorFor('eyebrow_text') }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">زیرعنوان</label>
                            <InputText v-model="form.subtitle" class="w-full" />
                            <small v-if="errorFor('subtitle')" class="text-red-600">{{ errorFor('subtitle') }}</small>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block font-medium">عنوان</label>
                            <InputText v-model="form.title" class="w-full" :invalid="Boolean(errorFor('title'))" />
                            <small v-if="errorFor('title')" class="text-red-600">{{ errorFor('title') }}</small>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block font-medium">توضیحات</label>
                            <Textarea v-model="form.description" rows="4" class="w-full" />
                            <small v-if="errorFor('description')" class="text-red-600">{{ errorFor('description') }}</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2 class="mb-4 text-lg font-semibold">تصاویر</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-md border border-surface-200 p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <h3 class="font-medium">تصویر پس‌زمینه</h3>
                                    <small class="text-surface-500">از Media Library انتخاب می‌شود.</small>
                                </div>
                                <Button type="button" label="انتخاب" icon="pi pi-images" size="small" outlined @click="backgroundBrowser = true" />
                            </div>
                            <img v-if="backgroundUrl" :src="backgroundUrl" class="h-36 w-full rounded-md object-cover" alt="background" />
                            <div v-else class="flex h-36 items-center justify-center rounded-md border border-dashed border-surface-300 text-surface-500">
                                <i class="pi pi-image text-2xl" />
                            </div>
                            <Button v-if="backgroundUrl" type="button" label="حذف تصویر" icon="pi pi-trash" severity="danger" text class="mt-2" @click="clearBackground" />
                            <small v-if="errorFor('background_media_id')" class="block text-red-600">{{ errorFor('background_media_id') }}</small>
                        </div>

                        <div class="rounded-md border border-surface-200 p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <h3 class="font-medium">تصویر اصلی</h3>
                                    <small class="text-surface-500">برای سمت چپ/راست هیرو.</small>
                                </div>
                                <Button type="button" label="انتخاب" icon="pi pi-images" size="small" outlined @click="foregroundBrowser = true" />
                            </div>
                            <img v-if="foregroundUrl" :src="foregroundUrl" class="h-36 w-full rounded-md object-cover" alt="foreground" />
                            <div v-else class="flex h-36 items-center justify-center rounded-md border border-dashed border-surface-300 text-surface-500">
                                <i class="pi pi-image text-2xl" />
                            </div>
                            <Button v-if="foregroundUrl" type="button" label="حذف تصویر" icon="pi pi-trash" severity="danger" text class="mt-2" @click="clearForeground" />
                            <small v-if="errorFor('foreground_media_id')" class="block text-red-600">{{ errorFor('foreground_media_id') }}</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2 class="mb-4 text-lg font-semibold">دکمه‌ها و Badge</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputText v-model="form.button_primary_text" placeholder="متن دکمه اصلی" class="w-full" />
                        <InputText v-model="form.button_primary_url" dir="ltr" placeholder="/products" class="w-full text-left" />
                        <InputText v-model="form.button_secondary_text" placeholder="متن دکمه دوم" class="w-full" />
                        <InputText v-model="form.button_secondary_url" dir="ltr" placeholder="/page/..." class="w-full text-left" />
                        <InputText v-model="form.badge_text" placeholder="متن Badge" class="w-full" />
                        <InputText v-model="form.badge_url" dir="ltr" placeholder="/campaign" class="w-full text-left" />
                    </div>
                </div>

                <div class="card">
                    <h2 class="mb-4 text-lg font-semibold">آمار</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <InputText v-model="form.stat_1_value" placeholder="مقدار ۱" class="mb-2 w-full" />
                            <InputText v-model="form.stat_1_label" placeholder="عنوان ۱" class="w-full" />
                        </div>
                        <div>
                            <InputText v-model="form.stat_2_value" placeholder="مقدار ۲" class="mb-2 w-full" />
                            <InputText v-model="form.stat_2_label" placeholder="عنوان ۲" class="w-full" />
                        </div>
                        <div>
                            <InputText v-model="form.stat_3_value" placeholder="مقدار ۳" class="mb-2 w-full" />
                            <InputText v-model="form.stat_3_label" placeholder="عنوان ۳" class="w-full" />
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2 class="mb-4 text-lg font-semibold">تنظیمات ظاهری و زمان‌بندی</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block font-medium">چینش</label>
                            <Select v-model="form.layout" :options="layouts" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">جایگاه نمایش</label>
                            <Select v-model="form.placement" :options="placements" optionLabel="label" optionValue="value" class="w-full" />
                            <small v-if="errorFor('placement')" class="text-red-600">{{ errorFor('placement') }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">Opacity لایه تیره</label>
                            <InputNumber v-model="form.overlay_opacity" class="w-full" inputClass="w-full" :min="0" :max="1" :step="0.05" mode="decimal" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">ترتیب</label>
                            <InputNumber v-model="form.sort_order" class="w-full" inputClass="w-full" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">رنگ متن</label>
                            <ColorPicker v-model="form.text_color" class="mb-2" />
                            <InputText v-model="form.text_color" dir="ltr" class="w-full text-left" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">رنگ تاکیدی</label>
                            <ColorPicker v-model="form.accent_color" class="mb-2" />
                            <InputText v-model="form.accent_color" dir="ltr" class="w-full text-left" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">رنگ دکمه</label>
                            <ColorPicker v-model="form.button_color" class="mb-2" />
                            <InputText v-model="form.button_color" dir="ltr" class="w-full text-left" />
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">شروع نمایش</label>
                            <PersianDateTimePicker v-model="form.starts_at" :invalid="Boolean(errorFor('starts_at'))" />
                            <small v-if="errorFor('starts_at')" class="text-red-600">{{ errorFor('starts_at') }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block font-medium">پایان نمایش</label>
                            <PersianDateTimePicker v-model="form.ends_at" :invalid="Boolean(errorFor('ends_at'))" />
                            <small v-if="errorFor('ends_at')" class="text-red-600">{{ errorFor('ends_at') }}</small>
                        </div>
                        <div class="flex items-center gap-3 pt-8">
                            <ToggleSwitch v-model="form.is_active" />
                            <span>فعال</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="card sticky top-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Preview زنده</h2>
                        <Tag :value="form.is_active ? 'فعال' : 'غیرفعال'" :severity="form.is_active ? 'success' : 'danger'" />
                    </div>
                    <div
                        class="relative overflow-hidden rounded-lg border border-surface-200 bg-[#111] shadow-sm"
                        :style="{ color: textColor }"
                    >
                        <img v-if="backgroundUrl" :src="backgroundUrl" class="absolute inset-0 h-full w-full object-cover" alt="" />
                        <div class="absolute inset-0 bg-black" :style="{ opacity: Number(form.overlay_opacity ?? 0.55) }"></div>
                        <div class="relative grid min-h-[420px] items-center gap-6 p-6" :class="previewClass">
                            <div :class="contentClass">
                                <a v-if="form.badge_text" :href="form.badge_url || '#'" class="mb-4 inline-flex rounded-full border px-3 py-1 text-xs font-bold" :style="{ borderColor: accentColor, color: accentColor }">
                                    {{ form.badge_text }}
                                </a>
                                <p v-if="form.eyebrow_text" class="text-xs font-bold uppercase tracking-wide" :style="{ color: accentColor }">{{ form.eyebrow_text }}</p>
                                <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">{{ form.title || 'عنوان هیرو' }}</h1>
                                <p v-if="form.subtitle" class="mt-3 text-lg font-semibold" :style="{ color: accentColor }">{{ form.subtitle }}</p>
                                <p class="mt-4 max-w-2xl text-sm leading-7 opacity-90">{{ form.description || 'توضیحات اسلایدر در این بخش نمایش داده می‌شود.' }}</p>
                                <div class="mt-6 flex flex-wrap gap-3" :class="{ 'justify-center': form.layout === 'content_center' }">
                                    <a v-if="form.button_primary_text" :href="form.button_primary_url || '#'" class="rounded-md px-5 py-3 text-sm font-bold text-black" :style="{ backgroundColor: buttonColor }">
                                        {{ form.button_primary_text }}
                                    </a>
                                    <a v-if="form.button_secondary_text" :href="form.button_secondary_url || '#'" class="rounded-md border px-5 py-3 text-sm font-bold" :style="{ borderColor: accentColor, color: textColor }">
                                        {{ form.button_secondary_text }}
                                    </a>
                                </div>
                                <div v-if="stats.length" class="mt-7 grid max-w-xl grid-cols-3 gap-3" :class="{ 'mx-auto': form.layout === 'content_center' }">
                                    <div v-for="stat in stats" :key="`${stat.label}-${stat.value}`" class="rounded-md border border-white/15 bg-white/10 p-3 text-center">
                                        <strong class="block text-xl" :style="{ color: accentColor }">{{ stat.value }}</strong>
                                        <span class="text-xs opacity-80">{{ stat.label }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="relative" :class="imageClass">
                                <img v-if="foregroundUrl" :src="foregroundUrl" class="h-[300px] w-full rounded-lg object-cover shadow-xl" :alt="form.title" />
                                <div v-else class="flex h-[300px] items-center justify-center rounded-lg border border-white/15 bg-white/10 text-white/70">
                                    تصویر اصلی
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Message v-if="Object.keys(form.errors).length" severity="error">لطفاً خطاهای فرم را بررسی کنید.</Message>

                <div class="flex justify-end gap-2">
                    <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="processing" />
                </div>
            </div>
        </div>

        <MediaBrowser v-model:visible="backgroundBrowser" mode="single" collection="hero_background" @select="chooseBackground" />
        <MediaBrowser v-model:visible="foregroundBrowser" mode="single" collection="hero_foreground" @select="chooseForeground" />
    </form>
</template>
