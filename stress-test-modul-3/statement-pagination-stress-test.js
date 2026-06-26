import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, SCENARIOS } from './shared/config.js';
import { login, getToken, getAuthHeaders } from './shared/auth.js';

const errorRate = new Rate('errors');
const statementDuration = new Trend('statement_duration');

export const options = {
    scenarios: {
        stress: {
            ...SCENARIOS.stress,
            exec: 'stressTest',
        },
    },
    thresholds: {
        'http_req_failed': ['rate<0.02'],
        'statement_duration': ['p(95)<1000'],
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
    const res = http.get(`${BASE_URL}/statement?page=1&limit=20`, params);
    statementDuration.add(res.timings.duration);

    check(res, {
        'statement status 200': (r) => r.status === 200,
    });

    sleep(0.5);
}

export function setup() {
    const res = login();
    return { token: getToken(res) };
}