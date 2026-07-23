<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useCart } from '@/Composables/useCart';
import { requestCustomerOtp, syncCustomerCart, verifyCustomerOtp } from '@/Frontend/services/customerAuthApi';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    redirectAfterLogin: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:visible', 'authenticated']);
const { items } = useCart();

const step = ref('phone');
const phone = ref('');
const name = ref('');
const code = ref('');
const mode = ref('login');
const loading = ref(false);
const error = ref('');
const success = ref('');
const expiresIn = ref(0);
let timer = null;

const title = computed(() => step.value === 'phone' ? 'ورود / ثبت نام' : (mode.value === 'login' ? 'ورود مشتری' : 'ثبت نام مشتری'));
const submitLabel = computed(() => step.value === 'phone' ? 'دریافت کد تایید' : 'تایید و ادامه خرید');
const canResend = computed(() => step.value === 'otp' && expiresIn.value <= 0);

const close = () => emit('update:visible', false);

const reset = () => {
    step.value = 'phone';
    phone.value = '';
    name.value = '';
    code.value = '';
    mode.value = 'login';
    error.value = '';
    success.value = '';
    expiresIn.value = 0;
    clearInterval(timer);
};

const startTimer = (seconds) => {
    clearInterval(timer);
    expiresIn.value = seconds;
    timer = setInterval(() => {
        expiresIn.value = Math.max(0, expiresIn.value - 1);
        if (expiresIn.value <= 0) clearInterval(timer);
    }, 1000);
};

const requestOtp = async () => {
    error.value = '';
    success.value = '';
    loading.value = true;

    try {
        const data = await requestCustomerOtp(phone.value);
        mode.value = data.mode || 'login';
        step.value = 'otp';
        code.value = '';
        success.value = data.message || 'کد تایید ارسال شد.';
        startTimer(data.expires_in || 120);
    } catch (exception) {
        error.value = exception.response?.data?.message || exception.response?.data?.errors?.phone?.[0] || 'ارسال کد تایید انجام نشد.';
    } finally {
        loading.value = false;
    }
};

const verifyOtp = async () => {
    error.value = '';
    success.value = '';
    loading.value = true;

    try {
        await verifyCustomerOtp({
            phone: phone.value,
            code: code.value,
            name: mode.value === 'register' ? name.value : null,
        });

        await syncCustomerCart(items.value);

        emit('authenticated');
        close();
        reset();

        if (props.redirectAfterLogin) {
            router.visit(props.redirectAfterLogin);
        }
    } catch (exception) {
        error.value = exception.response?.data?.message || exception.response?.data?.errors?.code?.[0] || 'تایید کد انجام نشد.';
    } finally {
        loading.value = false;
    }
};

const submit = () => {
    if (step.value === 'phone') {
        requestOtp();
        return;
    }

    verifyOtp();
};

watch(() => props.visible, (visible) => {
    if (! visible) return;

    error.value = '';
    success.value = '';
});
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        dismissableMask
        :header="title"
        class="w-[min(92vw,28rem)]"
        @update:visible="$emit('update:visible', $event)"
        @hide="reset"
    >
        <form class="space-y-4 text-right" dir="rtl" @submit.prevent="submit">
            <div v-if="step === 'phone'">
                <label for="customer-phone" class="mb-2 block text-sm font-bold text-surface-800">شماره موبایل</label>
                <InputText
                    id="customer-phone"
                    v-model="phone"
                    class="w-full"
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="09123456789"
                    :disabled="loading"
                />
                <p class="mt-2 text-xs leading-6 text-surface-500">
                    اگر قبلا با این شماره ثبت نام کرده باشید وارد حساب می‌شوید، وگرنه ثبت نام سریع انجام می‌شود.
                </p>
            </div>

            <div v-else class="space-y-4">
                <div class="rounded-xl bg-surface-50 p-3 text-sm text-surface-700">
                    کد تایید به شماره <strong dir="ltr">{{ phone }}</strong> ارسال شد.
                    <span v-if="mode === 'register'">برای تکمیل ثبت نام، نام خود را هم وارد کنید.</span>
                </div>

                <div v-if="mode === 'register'">
                    <label for="customer-name" class="mb-2 block text-sm font-bold text-surface-800">نام و نام خانوادگی</label>
                    <InputText id="customer-name" v-model="name" class="w-full" autocomplete="name" :disabled="loading" />
                </div>

                <div>
                    <label for="customer-otp" class="mb-2 block text-sm font-bold text-surface-800">کد تایید</label>
                    <InputText
                        id="customer-otp"
                        v-model="code"
                        class="w-full text-center tracking-[0.5em]"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        placeholder="------"
                        :disabled="loading"
                    />
                    <div class="mt-2 flex items-center justify-between text-xs">
                        <button v-if="canResend" type="button" class="font-bold text-[#D4A017]" :disabled="loading" @click="requestOtp">
                            ارسال دوباره کد
                        </button>
                        <span v-else class="text-surface-500">ارسال دوباره تا {{ expiresIn.toLocaleString('fa-IR') }} ثانیه</span>
                        <button type="button" class="font-bold text-surface-500" :disabled="loading" @click="step = 'phone'">
                            تغییر شماره
                        </button>
                    </div>
                </div>
            </div>

            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
            <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

            <div class="flex items-center gap-2 pt-2">
                <Button type="submit" :label="submitLabel" icon="pi pi-check" class="flex-1" :loading="loading" />
                <Button type="button" label="بستن" severity="secondary" text @click="close" />
            </div>
        </form>
    </Dialog>
</template>
