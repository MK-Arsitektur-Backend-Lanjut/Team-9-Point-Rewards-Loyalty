// statement-spike-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, SCENARIOS } from './shared/config.js';
import { login, getToken, getAuthHeaders } from './shared/auth.js';

const errorRate = new Rate('errors');
const spikeDuration = new Trend('spike_duration');

export const options = {
    scenarios: {
        spike: {
            ...SCENARIOS.spike,
            exec: 'spikeTest',
        },
    },
    thresholds: {
        'spike_duration': ['p(95)<2000'],
        'http_req_failed': ['rate<0.05'],
    },
};

export function spikeTest() {
    const loginRes = login();
    const token = getToken(loginRes);
    
    if (!token) {
        errorRate.add(1);
        return;
    }

    const params = getAuthHeaders(token);
    
    // Multiple requests in spike
    for (let i = 0; i < 5; i++) {
        const res = http.get(`${BASE_URL}/statement?page=${i+1}&limit=20`, params);
        spikeDuration.add(res.timings.duration);
        
        check(res, {
            'spike status 200': (r) => r.status === 200,
        });
    }

    sleep(0.2);
}

export function setup() {
    const res = login();
    return { token: getToken(res) };
}