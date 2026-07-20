import axios from 'axios';

export async function requestCustomerOtp(phone) {
    const { data } = await axios.post('/customer/auth/otp', { phone });

    return data;
}

export async function verifyCustomerOtp({ phone, code, name = null }) {
    const { data } = await axios.post('/customer/auth/verify', {
        phone,
        code,
        name,
    });

    return data;
}

export async function syncCustomerCart(items) {
    if (!items?.length) {
        return null;
    }

    const { data } = await axios.post('/cart/sync', { items });

    return data;
}
