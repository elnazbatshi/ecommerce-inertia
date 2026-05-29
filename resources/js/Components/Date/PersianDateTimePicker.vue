<script setup>
import { computed, ref, watch } from 'vue';
import PersianDatePicker from '@/Components/Date/PersianDatePicker.vue';
import {
    parseGregorianValue,
    toBackendDate,
    toBackendDateTime
} from '@/Utils/persianDate';

const props = defineProps({
    modelValue: { type: [String, Date, null], default: null },
    placeholder: { type: String, default: 'انتخاب تاریخ و زمان' },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true }
});

const emit = defineEmits(['update:modelValue', 'change']);

const datePart = ref('');
const hour = ref(0);
const minute = ref(0);
const syncing = ref(false);

const syncFromModel = () => {
    syncing.value = true;
    const date = parseGregorianValue(props.modelValue);

    datePart.value = toBackendDate(date);
    hour.value = date ? date.getHours() : 0;
    minute.value = date ? date.getMinutes() : 0;
    syncing.value = false;
};

watch(() => props.modelValue, syncFromModel, { immediate: true });

const normalizedHour = computed(() => Math.min(23, Math.max(0, Number(hour.value ?? 0))));
const normalizedMinute = computed(() => Math.min(59, Math.max(0, Number(minute.value ?? 0))));

const emitValue = () => {
    if (syncing.value) return;

    if (!datePart.value) {
        emit('update:modelValue', '');
        emit('change', '');
        return;
    }

    const date = parseGregorianValue(`${datePart.value} ${normalizedHour.value}:${normalizedMinute.value}:00`);
    const value = toBackendDateTime(date);

    emit('update:modelValue', value);
    emit('change', value);
};

const onDateChange = (value) => {
    datePart.value = value;
    emitValue();
};

const clear = () => {
    datePart.value = '';
    hour.value = 0;
    minute.value = 0;
    emitValue();
};
</script>

<template>
    <div class="persian-date-time-picker grid grid-cols-1 gap-2 md:grid-cols-[minmax(0,1fr)_5.5rem_5.5rem]">
        <PersianDatePicker
            :modelValue="datePart"
            :placeholder="placeholder"
            :disabled="disabled"
            :invalid="invalid"
            :clearable="clearable"
            @update:modelValue="onDateChange"
        />
        <InputNumber
            v-model="hour"
            :min="0"
            :max="23"
            :disabled="disabled"
            inputClass="w-full text-center"
            placeholder="ساعت"
            @update:modelValue="emitValue"
        />
        <InputNumber
            v-model="minute"
            :min="0"
            :max="59"
            :disabled="disabled"
            inputClass="w-full text-center"
            placeholder="دقیقه"
            @update:modelValue="emitValue"
        />
        <Button
            v-if="clearable"
            type="button"
            class="md:hidden"
            icon="pi pi-times"
            label="پاک کردن"
            severity="secondary"
            text
            :disabled="disabled || !datePart"
            @click="clear"
        />
    </div>
</template>
