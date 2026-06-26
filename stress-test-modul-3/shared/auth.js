import http from 'k6/http';
import { check } from 'k6';
import { BASE_URL, TEST_USER } from './config.js';

export function login(email = TEST_USER.email, password = TEST_USER.password) {
    const payload = JSON.stringify({
        email: email,
        password: password,
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    const res = http.post(`${BASE_URL}/login`, payload, params);
    
    check(res, {
        'login status is 200': (r) => r.status === 200,
        'login has token': (r) => r.json('data.token') !== undefined,
    });

    return res;
}

export function getToken(res) {
    return res.json('data.token');
}

export function getAuthHeaders(token) {
    return {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
        },
    };
}

export function getTestUser() {
    return TEST_USER;
}