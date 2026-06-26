// statement-pagination-stress-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, SCENARIOS } from './shared/config.js';
import { login, getToken, getAuthHeaders } from './shared/auth.js';

const errorRate = new Rate('errors');
const paginationDuration = new Trend('pagination_duration');

export const options = {
    scenarios: {
        stress: {
            ...SCENARIOS.stress,
            exec: 'stressTest',
        },
    },
    thresholds: {
        'pagination_duration': ['p(95)<1500'],
    },
};

export function stressTest() {
    const loginRes = login();
    const token = getToken(loginRes);
    
    if (!token) {
        errorRate.add(1);
        return;
    }

    const params = getAuthHeaders(token);
    
    // Test different pages
    const pages = [1, 5, 10, 20, 50];
    const page = pages[Math.floor(Math.random() * pages.length)];
    
    const res = http.get(`${BASE_URL}/statement?page=${page}&limit=20`, params);
    paginationDuration.add(res.timings.duration);

    check(res, {
        'pagination status 200': (r) => r.status === 200,
    });

    sleep(0.5);
}

export function setup() {
    const res = login();
    return { token: getToken(res) };
}