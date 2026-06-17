import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const REFERRER_ID = parseInt(__ENV.REFERRER_ID || '1', 10);
const REFEREE_START_ID = parseInt(__ENV.REFEREE_START_ID || '2', 10);
const REFEREE_END_ID = parseInt(__ENV.REFEREE_END_ID || '1000', 10);
const THINK_TIME = parseFloat(__ENV.THINK_TIME || '1');
// seeded = pakai user dari database (1 apply per user, cocok untuk smoke test)
// register = buat user baru tiap iterasi (cocok untuk stress test berulang)
const REFEREE_MODE = (__ENV.REFEREE_MODE || 'register').toLowerCase();

export const options = {
  stages: [
    { duration: '30s', target: 5 },
    { duration: '1m', target: 10 },
    { duration: '2m', target: 25 },
    { duration: '1m', target: 40 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed: ['rate<0.05'],
  },
};

function jsonHeaders() {
  return { 'Content-Type': 'application/json' };
}

function pickSeededRefereeId() {
  const range = Math.max(REFEREE_END_ID - REFEREE_START_ID + 1, 1);
  return REFEREE_START_ID + ((__VU - 1) * 1000 + __ITER) % range;
}

function registerReferee() {
  const suffix = `${__VU}_${__ITER}_${Date.now()}`;
  const res = http.post(
    `${BASE_URL}/api/register`,
    JSON.stringify({
      name: `K6 Referee ${suffix}`,
      email: `k6_${suffix}@test.local`,
      password: 'password',
      password_confirmation: 'password',
    }),
    { headers: jsonHeaders() }
  );

  check(res, {
    'register status is 201': (r) => r.status === 201,
  });

  if (res.status !== 201) {
    return null;
  }

  const body = res.json();
  return body?.data?.user?.id ?? null;
}

function getReferralCode() {
  const res = http.post(
    `${BASE_URL}/api/membership/referrals/generate`,
    JSON.stringify({ user_id: REFERRER_ID }),
    { headers: jsonHeaders() }
  );

  check(res, {
    'generate status is 200': (r) => r.status === 200,
    'generate returns referral_code': (r) => {
      try {
        const body = r.json();
        return body && typeof body.referral_code === 'string' && body.referral_code.length > 0;
      } catch (error) {
        return false;
      }
    },
  });

  if (res.status !== 200) {
    return null;
  }

  return res.json().referral_code;
}

export default function () {
  const referralCode = getReferralCode();
  if (!referralCode) {
    return;
  }

  const refereeId = REFEREE_MODE === 'seeded' ? pickSeededRefereeId() : registerReferee();
  if (!refereeId) {
    return;
  }

  const res = http.post(
    `${BASE_URL}/api/membership/referrals/apply`,
    JSON.stringify({
      user_id: refereeId,
      referral_code: referralCode,
    }),
    { headers: jsonHeaders() }
  );

  check(res, {
    'referral apply status is 200': (r) => r.status === 200,
    'response has referrer and referee': (r) => {
      try {
        const body = r.json();
        return body && body.referrer && body.referee;
      } catch (error) {
        return false;
      }
    },
  });

  sleep(THINK_TIME);
}
