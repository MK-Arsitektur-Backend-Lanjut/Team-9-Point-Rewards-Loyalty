export const BASE_URL = 'http://localhost:8002/api';
export const TEST_USER = {
    email: 'testmodul3@example.com',
    password: 'password123'
};

// Thresholds
export const THRESHOLDS = {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
    login_duration: ['p(95)<300'],
    statement_duration: ['p(95)<500'],
};

export const SCENARIOS = {
    smoke: {
        executor: 'constant-vus',
        vus: 1,
        duration: '30s',
    },
    load: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 50 },
            { duration: '2m', target: 50 },
            { duration: '30s', target: 0 },
        ],
    },
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 100 },
            { duration: '1m', target: 100 },
            { duration: '30s', target: 200 },
            { duration: '1m', target: 200 },
            { duration: '30s', target: 0 },
        ],
    },
    spike: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '10s', target: 300 },
            { duration: '30s', target: 300 },
            { duration: '10s', target: 0 },
        ],
    },
};