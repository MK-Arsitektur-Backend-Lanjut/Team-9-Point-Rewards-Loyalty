import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, SCENARIOS } from './shared/config.js';
import { login, getToken, getAuthHeaders } from './shared/auth.js';

const errorRate = new Rate('errors');
const filterDuration = new Trend('filter_duration');

export const options = {
    scenarios: {
        stress: {
            ...SCENARIOS.stress,
            exec: 'stressTest',
        },
    },
    thresholds: {
        'filter_duration': ['p(95)<1500'],
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
    
    // Test different filters
    const filters = [
        '?start_date=2026-01-01&end_date=2026-06-30',
        '?activity_code=TRX_PURCHASE',
        '?point_status=active',
        '?start_date=2026-01-01&end_date=2026-03-31&activity_code=DAILY_LOGIN',
    ];
    
    const filter = filters[Math.floor(Math.random() * filters.length)];
    
    const res = http.get(`${BASE_URL}/statement${filter}`, params);
    filterDuration.add(res.timings.duration);

    check(res, {
        'filter status 200': (r) => r.status === 200,
    });

    sleep(0.5);
}

export function setup() {
    const res = login();
    return { token: getToken(res) };
}