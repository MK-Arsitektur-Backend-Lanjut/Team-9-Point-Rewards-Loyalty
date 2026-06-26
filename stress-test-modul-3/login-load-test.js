import http from 'k6/http';
import { check } from 'k6';
import { BASE_URL, USER } from './shared/config.js';

export const options = {
    vus: 50,
    duration: '1m',
};

export default function () {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify(USER),
        {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }
    );

    check(res, {
        'login success': (r) => r.status === 200,
    });
}