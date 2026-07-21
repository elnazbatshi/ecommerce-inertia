<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';

const props = defineProps({
    hero: {
        type: Object,
        default: null,
    },
});

const contactForm = ref(null);
const activeFaq = ref(0);
const notice = ref('');

const fallbackHero = {
    image_url: '/storage/media/2026/06/photo-1558981806-ec527fa84c39-6a3e408740d92.jpg',
    title: 'تماس با ما',
    subtitle: 'ما اینجاییم تا به شما کمک کنیم',
    description: 'برای مشاوره قبل از خرید، پیگیری سفارش، پشتیبانی پس از خرید یا همکاری با موتوشهر با ما در ارتباط باشید.',
    badge: 'ارتباط با پشتیبانی موتوشهر',
    overlay_opacity: 60,
    text_position: 'right',
};

const heroData = computed(() => props.hero ? { ...fallbackHero, ...props.hero } : null);
const heroImage = computed(() => heroData.value?.image_url || fallbackHero.image_url);
const heroOverlayOpacity = computed(() => {
    const opacity = Number(heroData.value?.overlay_opacity ?? fallbackHero.overlay_opacity);

    return Math.min(100, Math.max(0, Number.isFinite(opacity) ? opacity : fallbackHero.overlay_opacity)) / 100;
});
const heroOverlayStyle = computed(() => ({
    background: `linear-gradient(90deg, rgba(6, 8, 11, 0.98) 0%, rgba(6, 8, 11, ${Math.max(heroOverlayOpacity.value, 0.35)}) 48%, rgba(6, 8, 11, ${Math.max(heroOverlayOpacity.value - 0.25, 0.20)}) 100%)`,
}));
const heroTextPositionClass = computed(() => ({
    right: 'justify-self-start text-right',
    center: 'justify-self-center text-center',
    left: 'justify-self-end text-left',
}[heroData.value?.text_position] ?? 'justify-self-start text-right'));
const heroTextMaxClass = computed(() => heroData.value?.text_position === 'center' ? 'mx-auto' : '');

const form = reactive({
    name: '',
    mobile: '',
    email: '',
    subject: '',
    message: '',
});

const errors = reactive({});

const subjects = [
    'مشاوره قبل از خرید',
    'پیگیری سفارش',
    'پشتیبانی پس از خرید',
    'درخواست مرجوعی',
    'همکاری با موتوشهر',
    'سایر موارد',
];

const quickFeatures = [
    { title: 'فروشگاه اینترنتی', text: 'موتوشهر فقط به صورت آنلاین فعالیت می‌کند.', icon: 'pi pi-shopping-bag' },
    { title: 'پشتیبانی پاسخ‌گو', text: 'از خرید تا پس از دریافت کالا همراه شما هستیم.', icon: 'pi pi-verified' },
    { title: 'ارسال سریع و مطمئن', text: 'سفارش‌ها در سریع‌ترین زمان ممکن ارسال می‌شوند.', icon: 'pi pi-truck' },
    { title: 'حفظ اطلاعات کاربران', text: 'اطلاعات شما امن و محرمانه نگهداری می‌شود.', icon: 'pi pi-shield' },
];

const contactItems = [
    { label: 'تلفن پشتیبانی', value: '021-12345678', hint: 'همه روزه از 9 تا 19', href: 'tel:02112345678', icon: 'pi pi-phone' },
    { label: 'پشتیبانی موبایل', value: '0912-123-4567', hint: 'همه روزه از 9 تا 19', href: 'tel:09121234567', icon: 'pi pi-mobile' },
    { label: 'ایمیل', value: 'info@motoshahr.com', hint: 'پاسخ‌گویی از طریق ایمیل', href: 'mailto:info@motoshahr.com', icon: 'pi pi-envelope' },
    { label: 'ساعات پاسخ‌گویی', value: 'شنبه تا پنجشنبه، 9:00 تا 19:00\nجمعه، 10:00 تا 14:00', hint: '', href: null, icon: 'pi pi-clock' },
];

const helpCards = [
    {
        title: 'مشاوره قبل از خرید',
        text: 'برای انتخاب بهترین محصول متناسب با خودرو یا موتور خود راهنمایی بگیرید.',
        action: 'دریافت مشاوره',
        subject: 'مشاوره قبل از خرید',
        icon: 'pi pi-comments',
    },
    {
        title: 'پیگیری سفارش',
        text: 'برای پیگیری وضعیت سفارش، ارسال و زمان تحویل با ما در تماس باشید.',
        action: 'پیگیری سفارش',
        subject: 'پیگیری سفارش',
        icon: 'pi pi-clipboard',
    },
    {
        title: 'پشتیبانی پس از خرید',
        text: 'سوالات مربوط به گارانتی، نصب، مرجوعی و مشکلات احتمالی محصولات.',
        action: 'مشاهده راهنما',
        subject: 'پشتیبانی پس از خرید',
        icon: 'pi pi-box',
    },
];

const faqs = [
    {
        question: 'روش‌های ارسال و هزینه‌ها چگونه است؟',
        answer: 'جزئیات ارسال با توجه به نوع سفارش، مقصد و شرایط بسته‌بندی بررسی می‌شود و هنگام نهایی شدن خرید نمایش داده خواهد شد.',
    },
    {
        question: 'چگونه سفارش خود را پیگیری کنم؟',
        answer: 'برای پیگیری سفارش، شماره سفارش یا شماره موبایلی که با آن خرید کرده‌اید را در پیام خود بنویسید تا سریع‌تر بررسی شود.',
    },
    {
        question: 'شرایط مرجوعی کالا به چه صورت است؟',
        answer: 'شرایط مرجوعی به وضعیت کالا، زمان ثبت درخواست و نوع محصول بستگی دارد و توسط تیم پشتیبانی بررسی می‌شود.',
    },
    {
        question: 'آیا محصولات دارای گارانتی هستند؟',
        answer: 'اطلاعات ضمانت و اصالت برای هر کالا جداگانه بررسی می‌شود؛ برای جزئیات دقیق محصول موردنظر با پشتیبانی تماس بگیرید.',
    },
];

const messageLength = computed(() => form.message.length);

const clearErrors = () => {
    Object.keys(errors).forEach((key) => delete errors[key]);
};

const validate = () => {
    clearErrors();

    if (!form.name.trim()) {
        errors.name = 'نام و نام خانوادگی را وارد کنید.';
    }

    if (!form.mobile.trim()) {
        errors.mobile = 'شماره موبایل را وارد کنید.';
    }

    if (!form.subject) {
        errors.subject = 'موضوع پیام را انتخاب کنید.';
    }

    if (form.message.trim().length < 10) {
        errors.message = 'متن پیام باید حداقل 10 کاراکتر باشد.';
    }

    return Object.keys(errors).length === 0;
};

const submitVisualForm = () => {
    notice.value = '';

    if (!validate()) {
        return;
    }

    notice.value = 'فرم فعلاً نمایشی است و در مرحله بعد به بک‌اند متصل می‌شود.';
    form.name = '';
    form.mobile = '';
    form.email = '';
    form.subject = '';
    form.message = '';
};

const pickSubject = (subject) => {
    form.subject = subject;
    notice.value = '';
    contactForm.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <Head>
        <title>تماس با ما | موتوشهر</title>
        <meta
            head-key="description"
            name="description"
            content="برای مشاوره خرید، پیگیری سفارش و دریافت پشتیبانی با تیم موتوشهر در ارتباط باشید."
        />
        <link rel="canonical" href="/contact" />
    </Head>

    <FrontLayout>
        <main class="bg-[#F5F6F8] text-[#111827]">
            <section v-if="heroData" class="contact-hero">
                <img
                    :src="heroImage"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover object-center opacity-55 md:opacity-70"
                    aria-hidden="true"
                />
                <div class="absolute inset-0" :style="heroOverlayStyle" aria-hidden="true" />
                <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-l from-transparent via-[#D4A017]/70 to-transparent" aria-hidden="true" />

                <div class="relative mx-auto grid h-full max-w-[1320px] items-center gap-8 px-4 py-9 sm:px-6 lg:grid-cols-[minmax(0,1fr)_180px] lg:px-8">
                    <div class="max-w-3xl" :class="[heroTextPositionClass, heroTextMaxClass]">
                        <span v-if="heroData.badge" class="hero-dynamic-badge inline-flex items-center gap-2 rounded-full border border-[#D4A017]/35 bg-[#D4A017]/10 px-4 py-2 text-sm font-black text-[#E0B437]">
                            <i class="pi pi-headphones" aria-hidden="true" />
                            {{ heroData.badge }}
                        </span>
                        <h1 v-if="heroData.title" class="hero-dynamic-title mt-5 text-4xl font-black leading-[1.35] text-white md:text-5xl">{{ heroData.title }}</h1>
                        <p v-if="heroData.subtitle" class="hero-dynamic-subtitle mt-3 text-xl font-bold text-[#E0B437]">{{ heroData.subtitle }}</p>
                        <p v-if="heroData.description" class="hero-dynamic-description mt-4 max-w-2xl text-sm leading-8 text-white/75 md:text-base" :class="heroTextMaxClass">
                            {{ heroData.description }}
                        </p>
                        <span v-if="heroData.badge" class="inline-flex items-center gap-2 rounded-full border border-[#D4A017]/35 bg-[#D4A017]/10 px-4 py-2 text-sm font-black text-[#E0B437]">
                            <i class="pi pi-headphones" aria-hidden="true" />
                            ارتباط با پشتیبانی موتوشهر
                        </span>
                        <h1 class="mt-5 text-4xl font-black leading-[1.35] text-white md:text-5xl">تماس با ما</h1>
                        <p class="mt-3 text-xl font-bold text-[#E0B437]">ما اینجاییم تا به شما کمک کنیم</p>
                        <p class="mt-4 max-w-2xl text-sm leading-8 text-white/75 md:text-base">
                            برای مشاوره قبل از خرید، پیگیری سفارش، پشتیبانی پس از خرید یا همکاری با موتوشهر با ما در ارتباط باشید.
                        </p>
                    </div>

                    <div class="hidden justify-self-end lg:block">
                        <div class="relative grid h-36 w-36 place-items-center rounded-full border border-[#D4A017]/35 bg-black/20 text-[#D4A017] shadow-[0_0_70px_rgba(212,160,23,0.22)]">
                            <span class="absolute inset-3 rounded-full border border-dashed border-[#D4A017]/25" aria-hidden="true" />
                            <i class="pi pi-headphones text-6xl" aria-hidden="true" />
                        </div>
                    </div>
                </div>
            </section>

            <div class="mx-auto max-w-[1320px] px-4 py-8 sm:px-6 lg:px-8">
                <section class="flex min-h-[98px] flex-col gap-4 rounded-[22px] border border-[#D4A017]/25 bg-[#FFF8E7] p-5 shadow-[0_16px_45px_rgba(15,23,42,0.06)] md:flex-row md:items-center md:px-7">
                    <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full border border-[#D4A017]/20 bg-[#D4A017]/15 text-[#D4A017]">
                        <i class="pi pi-globe text-2xl" aria-hidden="true" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black leading-8 text-gray-800 md:text-2xl">فروشگاه موتوشهر به‌صورت اینترنتی فعالیت می‌کند</h2>
                        <p class="mt-1 text-sm leading-8 text-gray-600">
                            تمام سفارش‌ها به‌صورت آنلاین ثبت و ارسال می‌شوند و در حال حاضر امکان مراجعه حضوری وجود ندارد.
                        </p>
                    </div>
                </section>

                <section class="mt-6 rounded-[22px] border border-gray-100 bg-white p-4 shadow-sm md:p-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0">
                        <article
                            v-for="(feature, index) in quickFeatures"
                            :key="feature.title"
                            class="feature-item"
                            :class="{ 'lg:border-l': index < quickFeatures.length - 1 }"
                        >
                            <div class="mx-auto grid h-[52px] w-[52px] place-items-center rounded-full border border-[#D4A017]/45 bg-white text-[#111111] shadow-[0_12px_28px_rgba(212,160,23,0.10)]">
                                <i :class="feature.icon" class="text-2xl" aria-hidden="true" />
                            </div>
                            <h3 class="mt-4 text-base font-extrabold text-gray-950">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm leading-7 text-gray-500">{{ feature.text }}</p>
                        </article>
                    </div>
                </section>

                <section class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(320px,.85fr)]">
                    <form
                        ref="contactForm"
                        class="min-h-[620px] rounded-[24px] border border-gray-100 bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.08)] md:p-8 lg:p-10"
                        novalidate
                        @submit.prevent="submitVisualForm"
                    >
                        <div class="mb-7 flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black leading-9 text-gray-950">پیام خود را برای ما ارسال کنید</h2>
                                <p class="mt-2 text-sm leading-8 text-gray-500">
                                    فرم زیر را تکمیل کنید تا کارشناسان موتوشهر در اولین فرصت با شما تماس بگیرند.
                                </p>
                            </div>
                            <div class="hidden h-11 w-11 shrink-0 place-items-center rounded-full bg-[#FFF8E7] text-[#D4A017] sm:grid">
                                <i class="pi pi-send text-xl" aria-hidden="true" />
                            </div>
                        </div>

                        <div v-if="notice" class="mb-5 rounded-xl border border-[#D4A017]/30 bg-[#FFF8E7] px-4 py-3 text-sm font-bold leading-7 text-[#4A3508]">
                            {{ notice }}
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="contact-name" class="contact-label">نام و نام خانوادگی</label>
                                <input id="contact-name" v-model="form.name" type="text" class="contact-field" placeholder="نام و نام خانوادگی *" autocomplete="name" />
                                <p v-if="errors.name" class="contact-error">{{ errors.name }}</p>
                            </div>

                            <div>
                                <label for="contact-mobile" class="contact-label">شماره موبایل</label>
                                <input id="contact-mobile" v-model="form.mobile" type="tel" dir="ltr" class="contact-field text-left" placeholder="شماره موبایل *" autocomplete="tel" />
                                <p v-if="errors.mobile" class="contact-error">{{ errors.mobile }}</p>
                            </div>

                            <div>
                                <label for="contact-email" class="contact-label">ایمیل اختیاری</label>
                                <input id="contact-email" v-model="form.email" type="email" dir="ltr" class="contact-field text-left" placeholder="ایمیل" autocomplete="email" />
                            </div>

                            <div>
                                <label for="contact-subject" class="contact-label">موضوع پیام</label>
                                <select id="contact-subject" v-model="form.subject" class="contact-field">
                                    <option value="">موضوع پیام *</option>
                                    <option v-for="subject in subjects" :key="subject" :value="subject">{{ subject }}</option>
                                </select>
                                <p v-if="errors.subject" class="contact-error">{{ errors.subject }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="contact-message" class="contact-label">متن پیام شما</label>
                            <div class="relative">
                                <textarea
                                    id="contact-message"
                                    v-model="form.message"
                                    maxlength="2000"
                                    class="contact-field min-h-[210px] resize-none py-4 pb-10 leading-8"
                                    placeholder="متن پیام شما *"
                                />
                                <span class="absolute bottom-4 left-4 text-xs font-bold text-gray-500">{{ messageLength }} / 2000</span>
                            </div>
                            <p v-if="errors.message" class="contact-error">{{ errors.message }}</p>
                        </div>

                        <button
                            type="submit"
                            class="mt-6 inline-flex h-[54px] w-full items-center justify-center gap-2 rounded-xl bg-[#D4A017] px-6 text-sm font-black text-black shadow-[0_16px_34px_rgba(212,160,23,0.22)] transition duration-200 hover:bg-[#E4B42A] focus:outline-none focus:ring-4 focus:ring-[#D4A017]/20"
                        >
                            ارسال پیام
                            <i class="pi pi-send" aria-hidden="true" />
                        </button>

                        <p class="mt-5 flex items-center justify-center gap-2 text-center text-xs font-bold leading-7 text-gray-600">
                            <i class="pi pi-lock text-[#111111]" aria-hidden="true" />
                            اطلاعات شما محفوظ و تنها جهت بررسی درخواست استفاده می‌شود.
                        </p>
                    </form>

                    <aside class="rounded-[24px] border border-gray-100 bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.08)] md:p-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-black text-gray-950">راه‌های ارتباطی</h2>
                            <span class="mx-auto mt-3 block h-0.5 w-10 rounded-full bg-[#D4A017]" aria-hidden="true" />
                        </div>

                        <div class="mt-6 space-y-3">
                            <div
                                v-for="item in contactItems"
                                :key="item.label"
                                class="group flex min-h-[88px] items-center gap-4 rounded-xl border border-gray-100 bg-gray-50/60 px-4 transition duration-200 hover:border-[#D4A017]/45"
                            >
                                <div class="grid h-[54px] w-[54px] shrink-0 place-items-center rounded-full border border-[#D4A017]/35 bg-white text-[#D4A017] transition duration-200 group-hover:bg-[#FFF8E7]">
                                    <i :class="item.icon" class="text-2xl" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-500">{{ item.label }}</p>
                                    <p v-if="item.hint" class="mt-1 text-xs leading-5 text-gray-500">{{ item.hint }}</p>
                                    <a v-if="item.href" :href="item.href" class="mt-1 block break-words text-base font-black text-gray-950 transition duration-200 hover:text-[#B88408]">
                                        {{ item.value }}
                                    </a>
                                    <p v-else class="mt-1 whitespace-pre-line text-sm font-black leading-7 text-gray-950">{{ item.value }}</p>
                                </div>
                            </div>
                        </div>

                        <p class="mt-5 rounded-xl border border-[#D4A017]/25 bg-[#FFF8E7] p-3.5 text-xs font-bold leading-7 text-[#4A3508]">
                            موتوشهر یک فروشگاه اینترنتی است و در حال حاضر امکان مراجعه حضوری وجود ندارد.
                        </p>

                        <div class="mt-5 border-t border-gray-100 pt-5 text-center">
                            <h3 class="text-sm font-black text-gray-900">ما را در شبکه‌های اجتماعی دنبال کنید</h3>
                            <div class="mt-4 flex justify-center gap-3">
                                <a href="#" class="social-link" aria-label="Instagram" @click.prevent>
                                    <i class="pi pi-instagram" aria-hidden="true" />
                                </a>
                                <a href="#" class="social-link" aria-label="Telegram" @click.prevent>
                                    <i class="pi pi-send" aria-hidden="true" />
                                </a>
                                <a href="#" class="social-link" aria-label="YouTube" @click.prevent>
                                    <i class="pi pi-youtube" aria-hidden="true" />
                                </a>
                                <a href="#" class="social-link" aria-label="WhatsApp" @click.prevent>
                                    <i class="pi pi-whatsapp" aria-hidden="true" />
                                </a>
                            </div>
                        </div>
                    </aside>
                </section>

                <section class="mt-10">
                    <div class="mb-5 text-center">
                        <h2 class="text-2xl font-black text-gray-950 md:text-3xl">در چه زمینه‌ای می‌توانیم به شما کمک کنیم؟</h2>
                        <span class="mx-auto mt-3 block h-0.5 w-12 rounded-full bg-[#D4A017]" aria-hidden="true" />
                    </div>
                    <div class="grid gap-6 md:grid-cols-3">
                        <article
                            v-for="card in helpCards"
                            :key="card.title"
                            class="flex min-h-[260px] flex-col justify-between rounded-2xl border border-gray-100 bg-white p-7 text-center shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_20px_55px_rgba(15,23,42,0.08)]"
                        >
                            <div>
                                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#FFF8E7] text-[#111111]">
                                    <i :class="card.icon" class="text-3xl" aria-hidden="true" />
                                </div>
                                <h3 class="mt-5 text-xl font-black text-gray-950">{{ card.title }}</h3>
                                <p class="mt-3 text-sm leading-8 text-gray-500">{{ card.text }}</p>
                            </div>
                            <button
                                type="button"
                                class="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-[#D4A017]/45 px-4 text-sm font-black text-gray-900 transition duration-200 hover:border-[#D4A017] hover:bg-[#D4A017] hover:text-black focus:outline-none focus:ring-4 focus:ring-[#D4A017]/20"
                                @click="pickSubject(card.subject)"
                            >
                                {{ card.action }}
                                <i class="pi pi-arrow-left text-xs" aria-hidden="true" />
                            </button>
                        </article>
                    </div>
                </section>

                <section class="mt-12 pb-12">
                    <div class="mb-6 flex items-center gap-4">
                        <span class="h-12 w-1 rounded-full bg-[#D4A017]" aria-hidden="true" />
                        <h2 class="text-2xl font-black text-gray-950 md:text-3xl">پاسخ سریع به سوالات شما</h2>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="(faq, index) in faqs"
                            :key="faq.question"
                            class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                        >
                            <button
                                type="button"
                                class="flex min-h-[70px] w-full items-center justify-between gap-4 px-5 py-4 text-right transition duration-200 hover:text-[#B88408] focus:outline-none focus:ring-4 focus:ring-inset focus:ring-[#D4A017]/15"
                                :aria-expanded="activeFaq === index"
                                @click="activeFaq = activeFaq === index ? null : index"
                            >
                                <span class="text-base font-bold text-gray-950">{{ faq.question }}</span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#FFF8E7] text-[#D4A017]">
                                    <i :class="activeFaq === index ? 'pi pi-minus' : 'pi pi-plus'" class="text-xs" aria-hidden="true" />
                                </span>
                            </button>
                            <transition name="faq">
                                <div v-if="activeFaq === index" class="mx-5 border-t border-gray-100 pb-5 pt-4 text-sm leading-8 text-gray-500">
                                    {{ faq.answer }}
                                </div>
                            </transition>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    </FrontLayout>
</template>

<style scoped>
.contact-hero {
    position: relative;
    min-height: 360px;
    overflow: hidden;
    background: #0b0d10;
}

.contact-hero :deep(.max-w-3xl > span:not(.hero-dynamic-badge)),
.contact-hero :deep(.max-w-3xl > h1:not(.hero-dynamic-title)),
.contact-hero :deep(.max-w-3xl > p:not(.hero-dynamic-subtitle):not(.hero-dynamic-description)) {
    display: none;
}

.feature-item {
    min-height: 164px;
    border-color: #e5e7eb;
    padding: 1.25rem;
    text-align: center;
}

.contact-label {
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
}

.contact-field {
    min-height: 52px;
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 0 1rem;
    color: #111827;
    font-size: 0.875rem;
    font-weight: 700;
    transition: border-color 200ms ease, box-shadow 200ms ease, background-color 200ms ease;
}

.contact-field::placeholder {
    color: #8f96a3;
    font-weight: 600;
}

.contact-field:focus {
    border-color: #d4a017;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(212, 160, 23, 0.10);
    outline: none;
}

.contact-error {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #dc2626;
}

.social-link {
    display: grid;
    height: 46px;
    width: 46px;
    place-items: center;
    border-radius: 999px;
    background: #111111;
    color: #d4a017;
    transition: background-color 200ms ease, color 200ms ease, box-shadow 200ms ease, transform 200ms ease;
}

.social-link:hover {
    background: #d4a017;
    color: #111111;
    box-shadow: 0 14px 28px rgba(212, 160, 23, 0.22);
    transform: translateY(-1px);
}

.faq-enter-active,
.faq-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.faq-enter-from,
.faq-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@media (max-width: 767px) {
    .contact-hero {
        min-height: 280px;
    }

    .feature-item {
        min-height: 150px;
        padding: 1rem;
    }
}
</style>
