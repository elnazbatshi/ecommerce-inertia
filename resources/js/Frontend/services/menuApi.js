import axios from 'axios';

const FALLBACK_MENU = {
    location: 'header',
    items: [
        { id: 'fallback-home', slug: '', title: 'صفحه اصلی', url: '/', type: 'custom', target: '_self', children: [] }
    ],
    popular_brands: [],
    popular_vehicles: [],
    quick_links: []
};

const FALLBACK_SUGGESTIONS = {
    popular_terms: ['روغن موتور', 'فیلتر هوا', 'لنت ترمز'],
    products: [],
    categories: [],
    brands: [],
    posts: []
};

const isCanceled = (error) => axios.isCancel(error)
    || error?.code === 'ERR_CANCELED'
    || error?.message === 'Request aborted'
    || error?.name === 'CanceledError';

export async function getMenu(options = {}) {
    try {
        const location = options.location || 'header';
        const requestOptions = { ...options };
        delete requestOptions.location;

        const response = await axios.get(`/api/menus/${location}`, requestOptions);
        return response.data ?? FALLBACK_MENU;
    } catch (error) {
        if (!isCanceled(error)) {
            console.warn('Frontend menu API failed; using fallback menu data.', error);
        }

        return FALLBACK_MENU;
    }
}

export async function getSearchSuggestions(query = '', options = {}) {
    try {
        const q = query.trim();
        if (q === '') {
            const response = await axios.get('/api/search/suggestions', options);
            const suggestions = response.data?.suggestions ?? [];

            return {
                popular_terms: suggestions.map((item) => item.keyword || item.title).filter(Boolean).slice(0, 8),
                products: [],
                categories: [],
                brands: [],
                posts: []
            };
        }

        const response = await axios.get('/api/search', {
            params: { q },
            ...options
        });

        return {
            popular_terms: [],
            products: response.data?.products ?? [],
            categories: response.data?.categories ?? [],
            brands: response.data?.brands ?? [],
            posts: []
        };
    } catch (error) {
        if (!isCanceled(error)) {
            console.warn('Search suggestions API failed; using fallback suggestions.', error);
        }

        return FALLBACK_SUGGESTIONS;
    }
}

export { isCanceled };
