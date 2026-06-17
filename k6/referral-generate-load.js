import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const USER_ID_START = parseInt(__ENV.USER_ID_START || '1', 10);
const USER_ID_END = parseInt(__ENV.USER_ID_END || '1000', 10);
const THINK_TIME = parseFloat(__ENV.THINK_TIME || '1');

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
  tags: {
    test_type: 'referral_generate',
  },
};

function pickUserId() {
  const range = Math.max(USER_ID_END - USER_ID_START + 1, 1);
  const slot = ((__VU - 1) + __ITER * 40) % range;
  return USER_ID_START + slot;
}

export default function () {
  const userId = pickUserId();

  const res = http.post(
    `${BASE_URL}/api/membership/referrals/generate`,
    JSON.stringify({ user_id: userId }),
    {
      headers: {
        'Content-Type': 'application/json',
      },
    }
  );

  check(res, {
    'generate status is 200': (r) => r.status === 200,
    'response has referral_code': (r) => {
      try {
        const body = r.json();
        return body && typeof body.referral_code === 'string' && body.referral_code.length > 0;
      } catch (error) {
        return false;
      }
    },
  });

  sleep(THINK_TIME);
}
