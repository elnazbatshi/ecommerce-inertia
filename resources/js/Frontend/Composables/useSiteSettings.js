import axios from 'axios';
import { computed, ref } from 'vue';

const fallbackSettings = {
    general: {
        site_name: 'MotoPart',
        site_description: 'فروشگاه آنلاین روغن موتور و قطعات',
        logo: null,
        logo_url: null,
        logo_media: null,
    },
    topbar: {
        items: [
            { title: 'ارسال سریع به سراسر کشور', description: null, icon: 'truck', is_active: true, sort_order: 1 },
            { title: '۷ روز ضمانت بازگشت کالا', description: null, icon: 'rotate', is_active: true, sort_order: 2 },
            { title: 'ضمانت اصالت کالا', description: null, icon: 'shield', is_active: true, sort_order: 3 },
            { title: 'خرید امن', description: null, icon: 'credit-card', is_active: true, sort_order: 4 },
        ],
    },
    contact: {
        phone: null,
        mobile: null,
        email: null,
        address: null,
        working_hours: null,
    },
    footer: {
        description: 'فروشگاه تخصصی قطعات و روغن موتور سیکلت با تمرکز بر اصالت کالا، سرعت تامین و تجربه خرید حرفه‌ای.',
        copyright: null,
        links: [],
    },
    social: {
        instagram: null,
        telegram: null,
        whatsapp: null,
        linkedin: null,
    },
    service_features: {
        items: [
            { title: 'ضمانت اصالت', description: 'کالاهای تامین‌شده با امکان بررسی اصالت.', icon: 'shield', is_active: true, sort_order: 1 },
            { title: 'انتخاب بر اساس وسیله', description: 'جست‌وجوی قطعه متناسب با خودرو یا موتورسیکلت.', icon: 'motorcycle', is_active: true, sort_order: 2 },
            { title: 'پشتیبانی تخصصی', description: 'راهنمایی تخصصی برای انتخاب قطعه مناسب.', icon: 'headset', is_active: true, sort_order: 3 },
        ],
    },
};

const clone = (value) => JSON.parse(JSON.stringify(value));

const settings = ref(clone(fallbackSettings));
const loading = ref(false);
const error = ref(null);
const loaded = ref(false);
let pendingRequest = null;

const isPlainObject = (value) => value && typeof value === 'object' && !Array.isArray(value);

const mergeSettings = (base, incoming) => {
    const result = clone(base);

    Object.entries(incoming || {}).forEach(([group, values]) => {
        if (!isPlainObject(values)) {
            return;
        }

        result[group] = {
            ...(result[group] || {}),
            ...values,
        };
    });

    return result;
};

const sortedActiveItems = (items) => (Array.isArray(items) ? items : [])
    .filter((item) => item?.is_active !== false)
    .sort((a, b) => Number(a?.sort_order ?? 0) - Number(b?.sort_order ?? 0));

export function useSiteSettings() {
    const loadSettings = async () => {
        if (loaded.value) {
            return settings.value;
        }

        if (pendingRequest) {
            return pendingRequest;
        }

        loading.value = true;
        error.value = null;

        pendingRequest = axios.get('/api/frontend/site-settings')
            .then(({ data }) => {
                settings.value = mergeSettings(fallbackSettings, data?.data || {});
                loaded.value = true;

                return settings.value;
            })
            .catch((exception) => {
                error.value = exception;
                console.error('Failed to load site settings.', exception);

                return settings.value;
            })
            .finally(() => {
                loading.value = false;
                pendingRequest = null;
            });

        return pendingRequest;
    };

    const getSetting = (group, key, fallback = null) => settings.value?.[group]?.[key] ?? fallback;

    return {
        settings,
        loading,
        error,
        fallbackSettings,
        sortedActiveItems,
        topbarItems: computed(() => sortedActiveItems(settings.value.topbar?.items)),
        serviceFeatures: computed(() => sortedActiveItems(settings.value.service_features?.items)),
        footerLinks: computed(() => sortedActiveItems(settings.value.footer?.links)),
        loadSettings,
        getSetting,
    };
}
