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
    { duration: '1m', target: 50 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<1500'],
    http_req_failed: ['rate<0.05'],
  },
  tags: {
    test_type: 'tiering',
  },
};

function pickUserId() {
  const range = Math.max(USER_ID_END - USER_ID_START + 1, 1);
  return USER_ID_START + ((__VU - 1) * 1000 + __ITER) % range;
}

export default function () {
  const userId = pickUserId();

  const res = http.post(
    `${BASE_URL}/api/membership/tiers/recalculate`,
    JSON.stringify({ user_id: userId }),
    {
      headers: {
        'Content-Type': 'application/json',
      },
    }
  );

  check(res, {
    'tier recalculate status is 200': (r) => r.status === 200,
    'response has tier field': (r) => {
      try {
        const body = r.json();
        return body && Object.prototype.hasOwnProperty.call(body, 'tier');
      } catch (error) {
        return false;
      }
    },
  });

  sleep(THINK_TIME);
}