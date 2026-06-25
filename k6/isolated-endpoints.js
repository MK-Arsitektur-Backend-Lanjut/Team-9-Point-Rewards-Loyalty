import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const ENDPOINT = __ENV.ENDPOINT || 'list_tiers'; 
const REFERRER_ID = parseInt(__ENV.REFERRER_ID || '1');

// Metrics
const durationMetric = new Trend('endpoint_duration', true);
const errorMetric = new Rate('endpoint_errors');

export const options = {
    scenarios: {
        isolated: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '10s', target: parseInt(__ENV.LOAD || '10') },
                { duration: '30s', target: parseInt(__ENV.LOAD || '10') },
                { duration: '10s', target: 0 },
            ],
            gracefulRampDown: '5s',
        },
    },
    thresholds: {
        'endpoint_duration': ['p(95)<1000'],
        'endpoint_errors': ['rate<0.05'],
    }
};

function jsonHeaders() {
    return { headers: { 'Content-Type': 'application/json' } };
}

export function setup() {
    console.log(`[SETUP] Testing Endpoint: ${ENDPOINT} | Load: ${__ENV.LOAD} VUs`);
    
    // Generate a referral code for the apply test
    let refCode = null;
    if (ENDPOINT === 'apply_referral') {
        const res = http.post(`${BASE_URL}/api/membership/referrals/generate`, JSON.stringify({ user_id: REFERRER_ID }), jsonHeaders());
        if (res.status === 200) {
            refCode = res.json().referral_code;
        }
    }
    return { referralCode: refCode };
}

export default function (data) {
    let res;
    
    if (ENDPOINT === 'list_tiers') {
        res = http.get(`${BASE_URL}/api/membership/tiers`);
        
        durationMetric.add(res.timings.duration);
        const ok = check(res, { 'status is 200': (r) => r.status === 200 });
        errorMetric.add(!ok);

    } else if (ENDPOINT === 'generate_referral') {
        // Use random user ID to avoid duplicate/same user cache if needed, 
        // but for generation, same user is fine
        res = http.post(`${BASE_URL}/api/membership/referrals/generate`, JSON.stringify({ user_id: REFERRER_ID }), jsonHeaders());
        
        durationMetric.add(res.timings.duration);
        const ok = check(res, { 'status is 200': (r) => r.status === 200 });
        errorMetric.add(!ok);

    } else if (ENDPOINT === 'apply_referral') {
        if (!data.referralCode) return;

        // Register new dummy user
        const suffix = `iso_${__VU}_${__ITER}_${Date.now()}`;
        const reg = http.post(`${BASE_URL}/api/register`, JSON.stringify({
            name: `Iso User ${suffix}`,
            email: `${suffix}@test.local`,
            password: 'password',
            password_confirmation: 'password',
        }), jsonHeaders());

        if (reg.status !== 201) return;
        const refereeId = reg.json().data.user.id;

        // Apply
        res = http.post(`${BASE_URL}/api/membership/referrals/apply`, JSON.stringify({
            user_id: refereeId,
            referral_code: data.referralCode
        }), jsonHeaders());

        durationMetric.add(res.timings.duration);
        const ok = check(res, { 'status 200/422': (r) => r.status === 200 || r.status === 422 });
        errorMetric.add(!ok);

    } else if (ENDPOINT === 'recalculate_tier') {
        res = http.post(`${BASE_URL}/api/membership/tiers/recalculate`, JSON.stringify({ user_id: REFERRER_ID }), jsonHeaders());
        
        durationMetric.add(res.timings.duration);
        const ok = check(res, { 'status is 200': (r) => r.status === 200 });
        errorMetric.add(!ok);
    }

    sleep(1);
}
