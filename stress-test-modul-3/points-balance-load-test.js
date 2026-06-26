import http from 'k6/http';
import { check } from 'k6';
import { getToken } from './shared/auth.js';
import { BASE_URL } from './shared/config.js';

export const options = {
    vus: 100,
    duration: '1m',
};

export function setup() {
    return {
        token: getToken(),
    };
}

export default function (data) {
    const res = http.get(
        `${BASE_URL}/api/points/balance`,
        {
            headers: {
                Authorization: `Bearer ${data.token}`,
                Accept: 'application/json',
            },
        }
    );

    check(res, {
        'balance success': (r) => r.status === 200,
    });
}