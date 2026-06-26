import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { SCENARIOS } from './shared/config.js';
import { login, getTestUser } from './shared/auth.js';

const errorRate = new Rate('errors');
const loginDuration = new Trend('login_duration');

export const options = {
    scenarios: {
        stress: {
            ...SCENARIOS.stress,
            exec: 'stressTest',
        },
        spike: {
            ...SCENARIOS.spike,
            exec: 'spikeTest',
            startTime: '3m30s',
        },
    },
    thresholds: {
        'http_req_failed': ['rate<0.02'],
        'login_duration': ['p(95)<1000'],
    },
};

export function stressTest() {
    const user = getTestUser();
    const start = new Date().getTime();
    
    const res = login(user.email, user.password);
    const end = new Date().getTime();
    loginDuration.add(end - start);

    const success = check(res, {
        'login status 200': (r) => r.status === 200,
    });

    errorRate.add(!success);
    sleep(0.5);
}

export function spikeTest() {
    const user = getTestUser();
    const res = login(user.email, user.password);
    
    check(res, {
        'login status 200': (r) => r.status === 200,
    });
    
    sleep(0.2);
}

export function setup() {
    const res = login();
    return { token: res.json('data.token') };
}

export function teardown() {
    console.log('Login Stress Test Complete');
}