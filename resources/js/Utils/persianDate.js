import jalaali from 'jalaali-js';

const {
    isValidJalaaliDate,
    jalaaliMonthLength,
    toGregorian,
    toJalaali
} = jalaali;

export const PERSIAN_MONTHS = [
    'فروردین',
    'اردیبهشت',
    'خرداد',
    'تیر',
    'مرداد',
    'شهریور',
    'مهر',
    'آبان',
    'آذر',
    'دی',
    'بهمن',
    'اسفند'
];

export const PERSIAN_WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

export const pad = (value) => String(value).padStart(2, '0');

export const toPersianDigits = (value) =>
    String(value ?? '').replace(/\d/g, (digit) => persianDigits[Number(digit)]);

export const normalizeDigits = (value) =>
    String(value ?? '')
        .replace(/[۰-۹]/g, (digit) => String(persianDigits.indexOf(digit)))
        .replace(/[٠-٩]/g, (digit) => String(arabicDigits.indexOf(digit)));

export const parseGregorianValue = (value) => {
    if (!value) return null;
    if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;

    const normalized = normalizeDigits(value).trim();
    const match = normalized.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/);

    if (match) {
        const [, year, month, day, hour = '0', minute = '0', second = '0'] = match;
        const date = new Date(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute), Number(second));

        return Number.isNaN(date.getTime()) ? null : date;
    }

    const date = new Date(normalized);

    return Number.isNaN(date.getTime()) ? null : date;
};

export const parseJalaliValue = (value) => {
    if (!value) return null;

    const normalized = normalizeDigits(value).trim();
    const match = normalized.match(/^(\d{4})[/-](\d{1,2})[/-](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);

    if (!match) return null;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const hour = Number(match[4] ?? 0);
    const minute = Number(match[5] ?? 0);
    const second = Number(match[6] ?? 0);

    if (!isValidJalaaliDate(year, month, day)) return null;

    const gregorian = toGregorian(year, month, day);
    const date = new Date(gregorian.gy, gregorian.gm - 1, gregorian.gd, hour, minute, second);

    return Number.isNaN(date.getTime()) ? null : date;
};

export const toBackendDate = (date) => {
    if (!date) return '';

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

export const toBackendDateTime = (date) => {
    if (!date) return '';

    return `${toBackendDate(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
};

export const gregorianToJalaliParts = (value) => {
    const date = parseGregorianValue(value);
    if (!date) return null;

    const jalali = toJalaali(date.getFullYear(), date.getMonth() + 1, date.getDate());

    return {
        year: jalali.jy,
        month: jalali.jm,
        day: jalali.jd,
        hour: date.getHours(),
        minute: date.getMinutes(),
        second: date.getSeconds()
    };
};

export const jalaliToGregorianDate = (year, month, day, hour = 0, minute = 0, second = 0) => {
    if (!isValidJalaaliDate(year, month, day)) return null;

    const gregorian = toGregorian(year, month, day);

    return new Date(gregorian.gy, gregorian.gm - 1, gregorian.gd, hour, minute, second);
};

export const formatJalaliDate = (value, fallback = '-') => {
    const parts = gregorianToJalaliParts(value);
    if (!parts) return fallback;

    return toPersianDigits(`${parts.year}/${pad(parts.month)}/${pad(parts.day)}`);
};

export const formatJalaliDateTime = (value, fallback = '-') => {
    const parts = gregorianToJalaliParts(value);
    if (!parts) return fallback;

    return toPersianDigits(`${parts.year}/${pad(parts.month)}/${pad(parts.day)} ${pad(parts.hour)}:${pad(parts.minute)}`);
};

export const jalaliMonthDays = (year, month) => jalaaliMonthLength(year, month);

export const startOfJalaliMonthWeekday = (year, month) => {
    const date = jalaliToGregorianDate(year, month, 1);

    return date ? (date.getDay() + 1) % 7 : 0;
};

export const currentJalaliParts = () => {
    const today = new Date();
    const jalali = toJalaali(today.getFullYear(), today.getMonth() + 1, today.getDate());

    return {
        year: jalali.jy,
        month: jalali.jm,
        day: jalali.jd,
        hour: today.getHours(),
        minute: today.getMinutes(),
        second: today.getSeconds()
    };
};

export const coerceJalaliDisplayToBackend = (value, withTime = false) => {
    const date = parseJalaliValue(value);

    return withTime ? toBackendDateTime(date) : toBackendDate(date);
};
