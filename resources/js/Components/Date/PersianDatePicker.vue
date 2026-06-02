<script setup>
import { computed, ref, watch } from 'vue';
import {
    PERSIAN_MONTHS,
    PERSIAN_WEEKDAYS,
    currentJalaliParts,
    formatJalaliDate,
    gregorianToJalaliParts,
    jalaliMonthDays,
    jalaliToGregorianDate,
    parseJalaliValue,
    startOfJalaliMonthWeekday,
    toBackendDate,
    toPersianDigits
} from '@/Utils/persianDate';

const props = defineProps({
    modelValue: { type: [String, Date, null], default: null },
    placeholder: { type: String, default: 'انتخاب تاریخ' },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true }
});

const emit = defineEmits(['update:modelValue', 'change']);

const panel = ref(null);
const typedValue = ref('');
const today = currentJalaliParts();
const selected = ref(gregorianToJalaliParts(props.modelValue));
const viewYear = ref(selected.value?.year ?? today.year);
const viewMonth = ref(selected.value?.month ?? today.month);

const syncFromModel = () => {
    selected.value = gregorianToJalaliParts(props.modelValue);
    typedValue.value = formatJalaliDate(props.modelValue, '');

    if (selected.value) {
        viewYear.value = selected.value.year;
        viewMonth.value = selected.value.month;
    }
};

watch(() => props.modelValue, syncFromModel, { immediate: true });

const days = computed(() => {
    const blanks = Array.from({ length: startOfJalaliMonthWeekday(viewYear.value, viewMonth.value) }, () => null);
    const monthLength = jalaliMonthDays(viewYear.value, viewMonth.value);
    const monthDays = Array.from({ length: monthLength }, (_, index) => index + 1);

    return [...blanks, ...monthDays];
});

const monthLabel = computed(() => `${PERSIAN_MONTHS[viewMonth.value - 1]} ${toPersianDigits(viewYear.value)}`);

const isSelectedDay = (day) =>
    day &&
    selected.value?.year === viewYear.value &&
    selected.value?.month === viewMonth.value &&
    selected.value?.day === day;

const isToday = (day) => day && today.year === viewYear.value && today.month === viewMonth.value && today.day === day;

const emitValue = (date) => {
    const value = toBackendDate(date);
    emit('update:modelValue', value);
    emit('change', value);
};

const selectDay = (day) => {
    if (!day) return;

    const date = jalaliToGregorianDate(viewYear.value, viewMonth.value, day);
    emitValue(date);
    panel.value?.hide();
};

const goMonth = (step) => {
    let nextMonth = viewMonth.value + step;
    let nextYear = viewYear.value;

    if (nextMonth > 12) {
        nextMonth = 1;
        nextYear += 1;
    } else if (nextMonth < 1) {
        nextMonth = 12;
        nextYear -= 1;
    }

    viewMonth.value = nextMonth;
    viewYear.value = nextYear;
};

const pickToday = () => {
    viewYear.value = today.year;
    viewMonth.value = today.month;
    emitValue(jalaliToGregorianDate(today.year, today.month, today.day));
    panel.value?.hide();
};

const clear = () => {
    typedValue.value = '';
    emit('update:modelValue', '');
    emit('change', '');
};

const commitTypedValue = () => {
    if (!typedValue.value.trim()) {
        clear();
        return;
    }

    const parsed = parseJalaliValue(typedValue.value);

    if (!parsed) {
        typedValue.value = formatJalaliDate(props.modelValue, '');
        return;
    }

    emitValue(parsed);
};

const togglePanel = (event) => {
    if (props.disabled) return;

    panel.value?.toggle(event);
};
</script>

<template>
    <div class="persian-date-picker">
        <div class="flex gap-1">
            <IconField class="flex-1">
                <InputIcon><i class="pi pi-calendar" /></InputIcon>
                <InputText
                    v-model="typedValue"
                    dir="ltr"
                    class="w-full"
                    :disabled="disabled"
                    :invalid="invalid"
                    :placeholder="placeholder"
                    @focus="togglePanel"
                    @keydown.enter.prevent="commitTypedValue"
                    @blur="commitTypedValue"
                />
            </IconField>
            <Button type="button" icon="pi pi-calendar" severity="secondary" outlined :disabled="disabled" @click="togglePanel" />
            <Button v-if="clearable" type="button" icon="pi pi-times" severity="secondary" text :disabled="disabled || !typedValue" @click="clear" />
        </div>

        <Popover ref="panel">
            <div class="jalali-panel">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <Button type="button" icon="pi pi-angle-right" severity="secondary" text rounded @click="goMonth(-1)" />
                    <div class="text-center font-semibold">{{ monthLabel }}</div>
                    <Button type="button" icon="pi pi-angle-left" severity="secondary" text rounded @click="goMonth(1)" />
                </div>

                <div class="mb-2 grid grid-cols-7 gap-1 text-center text-xs text-surface-500">
                    <span v-for="weekday in PERSIAN_WEEKDAYS" :key="weekday">{{ weekday }}</span>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="(day, index) in days"
                        :key="`${viewYear}-${viewMonth}-${index}`"
                        type="button"
                        class="jalali-day"
                        :class="{ 'is-empty': !day, 'is-today': isToday(day), 'is-selected': isSelectedDay(day) }"
                        :disabled="!day"
                        @click="selectDay(day)"
                    >
                        {{ day ? toPersianDigits(day) : '' }}
                    </button>
                </div>

                <div class="mt-3 flex justify-between gap-2">
                    <Button type="button" label="امروز" size="small" severity="secondary" text @click="pickToday" />
                    <Button v-if="clearable" type="button" label="پاک کردن" size="small" severity="secondary" text @click="clear" />
                </div>
            </div>
        </Popover>
    </div>
</template>

<style scoped>
.jalali-panel {
    width: 18rem;
    direction: rtl;
}

.jalali-day {
    aspect-ratio: 1;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    color: var(--text-color);
    font-size: 0.875rem;
    transition: background-color 120ms ease, border-color 120ms ease;
}

.jalali-day:not(.is-empty):hover {
    background: var(--surface-hover);
}

.jalali-day.is-empty {
    cursor: default;
}

.jalali-day.is-today {
    border-color: var(--primary-color);
}

.jalali-day.is-selected {
    background: var(--primary-color);
    color: var(--primary-contrast-color);
}
</style>
