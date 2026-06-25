/*
  ╔════════════════════════════════════════════════════════════════╗
  ║         K6 STRESS TEST - LOYALTY POINT REWARDS SYSTEM          ║
  ║              Simplified Test with Available Endpoints          ║
  ╚════════════════════════════════════════════════════════════════╝
*/

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// ================================================================
// METRICS
// ================================================================
const getTiersErrorRate = new Rate('get_tiers_errors');
const triggerActivityErrorRate = new Rate('trigger_activity_errors');
const redeemErrorRate = new Rate('redeem_errors');

const getTiersDuration = new Trend('get_tiers_duration');
const triggerActivityDuration = new Trend('trigger_activity_duration');
const redeemDuration = new Trend('redeem_duration');

const getTiersCount = new Counter('get_tiers_total');
const triggerActivityCount = new Counter('trigger_activity_total');
const redeemCount = new Counter('redeem_total');

// ================================================================
// CONFIGURATION
// ================================================================
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api';
const SCENARIO = __ENV.SCENARIO || 'normal';
const LOAD = parseInt(__ENV.LOAD || '100');
const DURATION = __ENV.DURATION || '60s';
const RAMP_UP = __ENV.RAMP_UP || '10s';
const RAMP_DOWN = __ENV.RAMP_DOWN || '10s';

console.log(`
╔════════════════════════════════════════════════════════════════╗
║                    TEST CONFIGURATION                          ║
╠════════════════════════════════════════════════════════════════╣
║ Base URL:           ${BASE_URL}
║ Scenario:           ${SCENARIO}
║ Concurrent Users:   ${LOAD}
║ Test Duration:      ${DURATION}
║ Ramp Up:            ${RAMP_UP}
║ Ramp Down:          ${RAMP_DOWN}
╚════════════════════════════════════════════════════════════════╝
`);

// ================================================================
// TEST OPTIONS
// ================================================================
export const options = {
  scenarios: {
    main: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: RAMP_UP, target: LOAD },
        { duration: DURATION, target: LOAD },
        { duration: RAMP_DOWN, target: 0 },
      ],
      gracefulRampDown: '10s',
    },
  },
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed': ['rate<0.1'],
    'get_tiers_errors': ['rate<0.05'],
    'trigger_activity_errors': ['rate<0.15'],
    'redeem_errors': ['rate<0.2'],
  },
};

// ================================================================
// MAIN TEST
// ================================================================
export default function () {
  const baseUserId = 1;
  const userId = baseUserId + (__VU % 100);

  if (SCENARIO === 'race_condition') {
    raceConditionScenario(userId);
  } else if (SCENARIO === 'mixed') {
    mixedScenario(userId);
  } else {
    normalScenario(userId);
  }
}

// ================================================================
// NORMAL SCENARIO: Mix of operations
// ================================================================
function normalScenario(userId) {
  // 1. Get all tiers (read-heavy)
  group(`[${SCENARIO}] Get Membership Tiers`, () => {
    const res = http.get(`${BASE_URL}/membership/tiers`);
    
    const isSuccess = check(res, {
      'Status is 200': (r) => r.status === 200,
      'Has data': (r) => r.body.length > 10,
      'Response time < 300ms': (r) => r.timings.duration < 300,
    });

    getTiersDuration.add(res.timings.duration);
    getTiersErrorRate.add(!isSuccess ? 1 : 0);
    getTiersCount.add(1);
  });

  sleep(0.3);

  // 2. Trigger activity (simulates adding points)
  group(`[${SCENARIO}] Trigger Activity - User ${userId}`, () => {
    const payload = JSON.stringify({
      user_id: userId,
      activity_code: 'TRX_PURCHASE',
    });

    const res = http.post(`${BASE_URL}/membership/activity/trigger`, payload, {
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    const isSuccess = check(res, {
      'Status is 200 or 422': (r) => [200, 201, 422, 201].includes(r.status),
      'Response time < 500ms': (r) => r.timings.duration < 500,
    });

    triggerActivityDuration.add(res.timings.duration);
    triggerActivityErrorRate.add(!isSuccess ? 1 : 0);
    triggerActivityCount.add(1);
  });

  sleep(0.5);

  // 3. Try redeem reward
  group(`[${SCENARIO}] Redeem Reward - User ${userId}`, () => {
    const rewardId = Math.floor(Math.random() * 5) + 1;
    const payload = JSON.stringify({
      user_id: userId,
      quantity: 1,
    });

    const res = http.post(
      `${BASE_URL}/membership/rewards/${rewardId}/redeem`,
      payload,
      {
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      }
    );

    const isSuccess = check(res, {
      'Status 200/422/500': (r) => [200, 201, 422, 500].includes(r.status),
      'Response time < 800ms': (r) => r.timings.duration < 800,
    });

    redeemDuration.add(res.timings.duration);
    redeemErrorRate.add(!isSuccess ? 1 : 0);
    redeemCount.add(1);
  });

  sleep(1);
}

// ================================================================
// RACE CONDITION: Concurrent operations from same user
// ================================================================
function raceConditionScenario(userId) {
  // All concurrent operations on same user - tests race conditions
  const payload = JSON.stringify({
    user_id: userId,
    activity_code: 'DAILY_LOGIN',
  });

  // Send 10 rapid requests
  for (let i = 0; i < 10; i++) {
    group(`[RaceCondition] Request ${i + 1} - User ${userId}`, () => {
      const res = http.post(`${BASE_URL}/membership/activity/trigger`, payload, {
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      const isSuccess = check(res, {
        'Status is 200/422': (r) => [200, 201, 422].includes(r.status),
        'Response received': (r) => r.body.length > 0,
      });

      triggerActivityDuration.add(res.timings.duration);
      triggerActivityErrorRate.add(!isSuccess ? 1 : 0);
    });

    sleep(0.05); // Minimal delay between concurrent requests
  }
}

// ================================================================
// MIXED SCENARIO: Realistic traffic mix
// ================================================================
function mixedScenario(userId) {
  const operations = Math.random();

  if (operations < 0.4) {
    // 40% - Get tiers
    const res = http.get(`${BASE_URL}/membership/tiers`);
    check(res, {
      'GET tiers 200': (r) => r.status === 200,
    });
    getTiersDuration.add(res.timings.duration);
    getTiersCount.add(1);

  } else if (operations < 0.7) {
    // 30% - Trigger activity
    const payload = JSON.stringify({
      user_id: userId,
      activity_code: 'DAILY_LOGIN',
    });
    const res = http.post(`${BASE_URL}/membership/activity/trigger`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    check(res, {
      'POST activity done': (r) => r.status < 500,
    });
    triggerActivityDuration.add(res.timings.duration);
    triggerActivityCount.add(1);

  } else {
    // 30% - Redeem
    const rewardId = Math.floor(Math.random() * 5) + 1;
    const payload = JSON.stringify({ user_id: userId, quantity: 1 });
    const res = http.post(`${BASE_URL}/membership/rewards/${rewardId}/redeem`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    check(res, {
      'POST redeem done': (r) => r.status < 500,
    });
    redeemDuration.add(res.timings.duration);
    redeemCount.add(1);
  }

  sleep(0.5);
}
