<script setup>
import TagInput from '@/Components/TagInput.vue';
import SeoPreview from '@/Components/CMS/SeoPreview.vue';
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    basePath: { type: String, required: true },
    fallbackTitle: { type: String, default: '' },
    fallbackDescription: { type: String, default: '' }
});

const seoUrl = computed(() => `${window.location.origin}${props.basePath}/${props.form.slug || 'نامک-خودکار'}`);
</script>

<template>
    <div class="card">
        <h2 class="mb-4 text-lg font-semibold">سئو</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">نامک</label>
                <InputText v-model="form.slug" class="w-full" />
                <small v-if="form.errors?.slug" class="text-red-600">{{ form.errors.slug }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نشانی اصلی</label>
                <InputText v-model="form.canonical_url" class="w-full" />
                <small v-if="form.errors?.canonical_url" class="text-red-600">{{ form.errors.canonical_url }}</small>
            </div>
            <div>
                <div class="mb-2 flex justify-between">
                    <label class="font-medium">عنوان سئو</label>
                    <small :class="(form.meta_title?.length ?? 0) > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title?.length ?? 0 }}/60</small>
                </div>
                <InputText v-model="form.meta_title" class="w-full" />
            </div>
            <div>
                <div class="mb-2 flex justify-between">
                    <label class="font-medium">توضیحات سئو</label>
                    <small :class="(form.meta_description?.length ?? 0) > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description?.length ?? 0 }}/160</small>
                </div>
                <Textarea v-model="form.meta_description" rows="3" class="w-full" />
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">کلمات کلیدی</label>
                <TagInput v-model="form.meta_keywords" />
            </div>
            <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_index" /><span>اجازه نمایه‌سازی</span></div>
            <div class="flex items-center gap-3"><ToggleSwitch v-model="form.seo_follow" /><span>اجازه دنبال‌کردن لینک‌ها</span></div>
        </div>
        <div class="mt-5">
            <SeoPreview
                :title="form.meta_title"
                :description="form.meta_description"
                :url="seoUrl"
                :fallbackTitle="fallbackTitle"
                :fallbackDescription="fallbackDescription"
            />
        </div>
    </div>
</template>
