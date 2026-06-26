import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = 'http://localhost:8002';

const USER = {
    email: 'testmodul3@example.com',
    password: 'password123'
};

export const options = {
    stages: [
        { duration: '30s', target: 50 },   // ramp up
        { duration: '1m', target: 200 },   // stress load
        { duration: '30s', target: 500 },  // peak stress
        { duration: '1m', target: 500 },   // hold
        { duration: '30s', target: 0 },    // ramp down
    ],

    thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<3000'],
    },
};

function getToken() {
    const loginRes = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify(USER),
        {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }
    );

    check(loginRes, {
        'login status 200': (r) => r.status === 200,
    });

    const body = loginRes.json();

    return (
        body.access_token ||
        body.token ||
        body.data?.access_token ||
        body.data?.token
    );
}

export default function () {
    const token = getToken();

    if (!token) {
        console.error('JWT Token tidak ditemukan');
        return;
    }

    const res = http.get(
        `${BASE_URL}/api/points/balance`,
        {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        }
    );

    check(res, {
        'balance status 200': (r) => r.status === 200,
        'response time < 3s': (r) => r.timings.duration < 3000,
    });

    sleep(1);
}