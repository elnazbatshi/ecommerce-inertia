<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
    processing: { type: Boolean, default: false }
});

const emit = defineEmits(['submit']);
const autoSlug = ref(!props.form.slug);
const activePanels = ref([]);

const driverOptions = [
    { label: 'زرین پال', value: 'zarinpal' },
    { label: 'نکست پی', value: 'nextpay' },
    { label: 'آی دی پی', value: 'idpay' },
    { label: 'آقای پرداخت', value: 'aqayepardakht' },
    { label: 'دیجی‌پی', value: 'digipay' },
    { label: 'اسنپ‌پی', value: 'snappay' },
    { label: 'تارا', value: 'tara' },
    { label: 'کارت به کارت', value: 'card_to_card' },
    { label: 'پرداخت در محل', value: 'cash_on_delivery' },
    { label: 'کیف پول', value: 'wallet' },
    { label: 'دستی', value: 'manual' }
];

const feeTypeOptions = [
    { label: 'بدون کارمزد', value: 'none' },
    { label: 'مبلغ ثابت', value: 'fixed' },
    { label: 'درصدی', value: 'percent' }
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

watch(
    () => props.form.settings,
    (value) => {
        if (typeof value === 'string') return;
        props.form.settings = JSON.stringify(value || {}, null, 2);
    },
    { immediate: true }
);

const onSlugInput = (value) => {
    props.form.slug = slugify(value);
    autoSlug.value = !props.form.slug;
};

const onSubmit = () => {
    try {
        props.form.settings = props.form.settings ? JSON.parse(props.form.settings) : {};
    } catch {
        props.form.setError('settings', 'فرمت JSON تنظیمات معتبر نیست.');
        return;
    }
    emit('submit');
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="onSubmit">
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
                <label class="mb-2 block font-medium">درایور</label>
                <Dropdown v-model="form.driver" :options="driverOptions" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.driver" class="text-red-600">{{ form.errors.driver }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">نوع کارمزد</label>
                <Dropdown v-model="form.fee_type" :options="feeTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.fee_type" class="text-red-600">{{ form.errors.fee_type }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">مقدار کارمزد</label>
                <InputNumber v-model="form.fee_value" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.fee_value" class="text-red-600">{{ form.errors.fee_value }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">حداقل مبلغ</label>
                <InputNumber v-model="form.min_amount" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.min_amount" class="text-red-600">{{ form.errors.min_amount }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">حداکثر مبلغ</label>
                <InputNumber v-model="form.max_amount" class="w-full" :min="0" mode="decimal" />
                <small v-if="form.errors.max_amount" class="text-red-600">{{ form.errors.max_amount }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">ترتیب</label>
                <InputNumber v-model="form.sort_order" class="w-full" :min="0" />
                <small v-if="form.errors.sort_order" class="text-red-600">{{ form.errors.sort_order }}</small>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">توضیحات</label>
                <Textarea v-model="form.description" class="w-full" rows="4" />
                <small v-if="form.errors.description" class="text-red-600">{{ form.errors.description }}</small>
            </div>
            <div class="flex items-center gap-3">
                <ToggleSwitch v-model="form.is_active" />
                <span>فعال</span>
            </div>
        </div>

        <Accordion :value="activePanels" multiple>
            <AccordionPanel value="gateway-settings">
                <AccordionHeader>تنظیمات درگاه</AccordionHeader>
                <AccordionContent>
                <Textarea v-model="form.settings" class="w-full" rows="8" dir="ltr" />
                <small v-if="form.errors.settings" class="text-red-600">{{ form.errors.settings }}</small>
                </AccordionContent>
            </AccordionPanel>
        </Accordion>

        <div class="flex justify-end">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="processing" />
        </div>
    </form>
</template>
