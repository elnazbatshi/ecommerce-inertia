<script setup>
import { computed } from 'vue';
import { useSiteSettings } from '../Composables/useSiteSettings';
import SiteSettingIcon from './SiteSettingIcon.vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const { settings, footerLinks, serviceFeatures } = useSiteSettings();

const siteName = computed(() => settings.value.general?.site_name || 'MotoPart');
const logoUrl = computed(() => settings.value.general?.logo_url || null);
const footerDescription = computed(() => (
    settings.value.footer?.description
    || settings.value.general?.site_description
    || 'فروشگاه تخصصی قطعات و روغن موتور سیکلت با تمرکز بر اصالت کالا، سرعت تامین و تجربه خرید حرفه‌ای.'
));

const visibleLinks = computed(() => {
    if (footerLinks.value.length) {
        return footerLinks.value.map((link, index) => ({
            id: link.id || `settings-link-${index}`,
            title: link.title,
            url: link.url || '#',
            target: link.target || '_self',
            rel: link.rel || null,
        }));
    }

    if (props.items.length) {
        return props.items;
    }

    return [
        { id: 'fallback-blog', title: 'وبلاگ', url: '/blog', target: '_self', rel: null },
        { id: 'fallback-contact', title: 'تماس با ما', url: '/page/contact-expert', target: '_self', rel: null },
    ];
});

const contact = computed(() => settings.value.contact || {});
const social = computed(() => settings.value.social || {});

const socialLinks = computed(() => [
    { key: 'instagram', label: 'Instagram', icon: 'pi pi-instagram', url: social.value.instagram },
    { key: 'telegram', label: 'Telegram', icon: 'pi pi-send', url: social.value.telegram },
    { key: 'whatsapp', label: 'WhatsApp', icon: 'pi pi-whatsapp', url: social.value.whatsapp },
    { key: 'linkedin', label: 'LinkedIn', icon: 'pi pi-linkedin', url: social.value.linkedin },
].filter((item) => item.url));

const copyrightText = computed(() => (
    settings.value.footer?.copyright
    || `© ${new Date().getFullYear()} ${siteName.value}. تمام حقوق محفوظ است.`
));
</script>

<template>
    <footer class="footer-premium">
        <div class="site-container py-14">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr_1fr]">
                <div>
                    <img v-if="logoUrl" :src="logoUrl" :alt="siteName" class="h-14 max-w-[180px] object-contain" />
                    <h3 v-else class="text-3xl font-black text-[#111111]">{{ siteName }}</h3>
                    <p class="mt-3 text-sm leading-7 text-[#666666]">
                        {{ footerDescription }}
                    </p>

                    <div v-if="socialLinks.length" class="mt-5 flex flex-wrap gap-2">
                        <a
                            v-for="item in socialLinks"
                            :key="item.key"
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="item.label"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[#E5E5E5] bg-white text-[#111111] transition hover:border-[#D4A017] hover:text-[#D4A017]"
                        >
                            <i :class="item.icon"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="footer-title">مزایای خرید</h4>
                    <ul class="footer-list">
                        <li v-for="feature in serviceFeatures" :key="`${feature.title}-${feature.sort_order}`" class="flex items-center gap-2">
                            <SiteSettingIcon :name="feature.icon" />
                            <span>{{ feature.title }}</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-title">لینک های مهم</h4>
                    <nav class="footer-links">
                        <a
                            v-for="item in visibleLinks"
                            :key="item.id || item.url"
                            :href="item.url"
                            :target="item.target || '_self'"
                            :rel="item.rel"
                        >
                            {{ item.title }}
                        </a>
                    </nav>
                </div>

                <div>
                    <h4 class="footer-title">اطلاعات تماس</h4>
                    <ul class="footer-list">
                        <li v-if="contact.phone">
                            <a :href="`tel:${contact.phone}`" dir="ltr">{{ contact.phone }}</a>
                        </li>
                        <li v-if="contact.mobile">
                            <a :href="`tel:${contact.mobile}`" dir="ltr">{{ contact.mobile }}</a>
                        </li>
                        <li v-if="contact.email">
                            <a :href="`mailto:${contact.email}`" dir="ltr">{{ contact.email }}</a>
                        </li>
                        <li v-if="contact.address">{{ contact.address }}</li>
                        <li v-if="contact.working_hours">{{ contact.working_hours }}</li>
                        <li v-if="!contact.phone && !contact.mobile && !contact.email && !contact.address && !contact.working_hours">
                            اطلاعات تماس به‌زودی تکمیل می‌شود.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-[#E5E5E5] pt-5 text-center text-xs text-[#777777]">
                {{ copyrightText }}
            </div>
        </div>
    </footer>
</template>
