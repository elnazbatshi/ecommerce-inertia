import { toRaw } from 'vue';

const MENU_ITEM_DEFAULTS = Object.freeze({
    id: null,
    title: '',
    title_attribute: '',
    type: 'custom',
    url: '',
    reference_id: null,
    route_name: null,
    route_params: null,
    target: '_self',
    icon: '',
    css_class: '',
    rel: '',
    is_active: true,
    sort_order: 0,
    depth: 0,
});

export function normalizeMenuItems(items = [], seen = new WeakSet()) {
    const rawItems = toRaw(items);

    if (!Array.isArray(rawItems)) {
        return [];
    }

    return rawItems.map((item, index) => cloneMenuItem(item, index, seen));
}

export function cloneMenuItem(item, fallbackSortOrder = 0, seen = new WeakSet()) {
    const raw = toRaw(item) ?? {};
    const isObject = raw !== null && typeof raw === 'object';
    const isCircular = isObject && seen.has(raw);

    if (isObject && !isCircular) {
        seen.add(raw);
    }

    return {
        id: raw.id ?? MENU_ITEM_DEFAULTS.id,
        title: raw.title ?? MENU_ITEM_DEFAULTS.title,
        title_attribute: raw.title_attribute ?? MENU_ITEM_DEFAULTS.title_attribute,
        type: raw.type ?? MENU_ITEM_DEFAULTS.type,
        url: raw.url ?? MENU_ITEM_DEFAULTS.url,
        reference_id: raw.reference_id ?? MENU_ITEM_DEFAULTS.reference_id,
        route_name: raw.route_name ?? MENU_ITEM_DEFAULTS.route_name,
        route_params: normalizePlainValue(raw.route_params ?? MENU_ITEM_DEFAULTS.route_params),
        target: raw.target ?? MENU_ITEM_DEFAULTS.target,
        icon: raw.icon ?? MENU_ITEM_DEFAULTS.icon,
        css_class: raw.css_class ?? MENU_ITEM_DEFAULTS.css_class,
        rel: raw.rel ?? MENU_ITEM_DEFAULTS.rel,
        is_active: raw.is_active ?? MENU_ITEM_DEFAULTS.is_active,
        sort_order: normalizeNumber(raw.sort_order, fallbackSortOrder),
        depth: normalizeNumber(raw.depth, MENU_ITEM_DEFAULTS.depth),
        children: isCircular ? [] : normalizeMenuItems(raw.children ?? [], seen),
    };
}

export function normalizeMenuTreeOrder(items = []) {
    const rawItems = toRaw(items);

    if (!Array.isArray(rawItems)) {
        return [];
    }

    return rawItems.map((item) => {
        const raw = toRaw(item) ?? {};

        return {
            id: raw.id,
            children: normalizeMenuTreeOrder(raw.children ?? []),
        };
    });
}

export function normalizePlainValue(value, seen = new WeakSet()) {
    const raw = toRaw(value);

    if (raw === null || typeof raw === 'undefined') {
        return null;
    }

    if (typeof raw === 'bigint') {
        return raw.toString();
    }

    if (typeof raw !== 'object') {
        return typeof raw === 'function' || typeof raw === 'symbol' ? null : raw;
    }

    if (typeof Node !== 'undefined' && raw instanceof Node) {
        return null;
    }

    if (seen.has(raw)) {
        return null;
    }

    seen.add(raw);

    if (raw instanceof Date) {
        return raw.toISOString();
    }

    if (Array.isArray(raw)) {
        return raw.map((entry) => normalizePlainValue(entry, seen));
    }

    return Object.entries(raw).reduce((result, [key, entry]) => {
        if (typeof entry === 'function' || typeof entry === 'symbol') {
            return result;
        }

        result[key] = normalizePlainValue(entry, seen);

        return result;
    }, {});
}

function normalizeNumber(value, fallback) {
    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
}
