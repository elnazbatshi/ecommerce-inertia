<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
    processing: { type: Boolean, default: false }
});

const emit = defineEmits(['submit']);
const autoSlug = ref(!props.form.slug);

const typeOptions = [
    { label: 'ارسال ثابت', value: 'fixed' },
    { label: 'ارسال رایگان', value: 'free' },
    { label: 'بر اساس وزن', value: 'weight_based' },
    { label: 'بر اساس شهر', value: 'city_based' },
    { label: 'تحویل حضوری', value: 'pickup' }
];

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
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">نام</label>
                <InputText v-model="form.name" class="w-full" />
                <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">اسلاگ</label>
                <InputText :model-value="form.slug" class="w-full" @update:model-value="onSlugInput" />
                <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نوع</label>
                <Dropdown v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.type" class="text-red-600">{{ form.errors.type }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">هزینه پایه</label>
                <InputNumber v-model="form.base_cost" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.base_cost" class="text-red-600">{{ form.errors.base_cost }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">ارسال رایگان از مبلغ</label>
                <InputNumber v-model="form.free_from_amount" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.free_from_amount" class="text-red-600">{{ form.errors.free_from_amount }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">حداقل مبلغ سفارش</label>
                <InputNumber v-model="form.min_order_amount" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.min_order_amount" class="text-red-600">{{ form.errors.min_order_amount }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">حداکثر مبلغ سفارش</label>
                <InputNumber v-model="form.max_order_amount" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.max_order_amount" class="text-red-600">{{ form.errors.max_order_amount }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">زمان تحویل</label>
                <InputText v-model="form.estimated_delivery_days" class="w-full" placeholder="مثال: 2 تا 4 روز کاری" />
                <small v-if="form.errors.estimated_delivery_days" class="text-red-600">{{ form.errors.estimated_delivery_days }}</small>
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
        </div>

        <div class="flex justify-end">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="processing" />
        </div>
    </form>
</template>

