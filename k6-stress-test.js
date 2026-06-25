/*
  ╔════════════════════════════════════════════════════════════════╗
  ║         K6 STRESS TEST - LOYALTY POINT REWARDS SYSTEM          ║
  ║                    Laravel Membership Module                   ║
  ╚════════════════════════════════════════════════════════════════╝

  Script untuk testing stress pada sistem Loyalty Point Rewards
  dengan skenario:
  - Normal Load (Ramp up gradually)
  - Race Condition (Concurrent requests from same user)
  - Mixed Load (Various operations)

  Penggunaan:
  k6 run k6-stress-test.js
  k6 run k6-stress-test.js --env SCENARIO=race_condition
  k6 run k6-stress-test.js --env LOAD=100 --env DURATION=2m
*/

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter, Gauge } from 'k6/metrics';

// ================================================================
// CUSTOM METRICS
// ================================================================

// Response Time Distribution (p50, p95, p99)
const addPointsDuration   = new Trend('add_points_duration');
const balanceDuration     = new Trend('balance_duration');
const redeemDuration      = new Trend('redeem_duration');
const raceConditionDuration = new Trend('race_condition_duration');

// Error Rates
const addPointsErrorRate  = new Rate('add_points_errors');
const balanceErrorRate    = new Rate('balance_errors');
const redeemErrorRate     = new Rate('redeem_errors');
const raceConditionErrorRate = new Rate('race_condition_errors');

// Total Request Count
const addPointsCount      = new Counter('add_points_total');
const balanceCount        = new Counter('balance_total');
const redeemCount         = new Counter('redeem_total');
const raceConditionCount  = new Counter('race_condition_total');

// Concurrent VUs
const activeVUs = new Gauge('active_vus', true);

// ================================================================
// CONFIGURATION
// ================================================================
const BASE_URL  = __ENV.BASE_URL  || 'http://localhost:8000/api';
const SCENARIO  = __ENV.SCENARIO  || 'normal'; // normal, race_condition, mixed
const LOAD      = parseInt(__ENV.LOAD || '100');
const DURATION  = __ENV.DURATION  || '1m';
const RAMP_UP   = __ENV.RAMP_UP   || '10s';
const RAMP_DOWN = __ENV.RAMP_DOWN || '10s';

// Demo routes — body: { user_id, points, description }
// GET balance uses path param: /demo/rewards/balance/{userId}
const DEMO = `${BASE_URL}/demo/rewards`;

// Default HTTP params — timeout lebih pendek agar VU tidak blocked lama
const HTTP_PARAMS = {
  headers: { 'Content-Type': 'application/json' },
  timeout: '15s',
};

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
// TEST OPTIONS & THRESHOLDS
// ================================================================
export const options = {
  scenarios: {
    main: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: RAMP_UP,   target: LOAD },
        { duration: DURATION,  target: LOAD },
        { duration: RAMP_DOWN, target: 0    },
      ],
      gracefulRampDown: '10s',
    },
  },

  thresholds: {
    // Threshold realistis untuk Docker di Windows (WSL2 overhead)
    'add_points_duration':    ['p(95)<5000', 'p(99)<10000', 'avg<3000'],
    'redeem_duration':        ['p(95)<5000', 'p(99)<10000', 'avg<3000'],
    'balance_duration':       ['p(95)<3000', 'p(99)<5000',  'avg<2000'],

    // Error = HTTP failure, bukan response time
    'add_points_errors':      ['rate<0.1'],
    'redeem_errors':          ['rate<0.1'],
    'balance_errors':         ['rate<0.05'],

    'http_req_duration':      ['p(95)<10000', 'p(99)<15000'],
    'http_req_failed':        ['rate<0.1'],
  },
};

// ================================================================
// SETUP
// ================================================================
export function setup() {
  console.log('Starting setup...');
  return {
    baseUrl: BASE_URL,
    scenario: SCENARIO,
    startTime: new Date().toISOString(),
  };
}

// ================================================================
// MAIN TEST FUNCTION
// ================================================================
export default function (data) {
  activeVUs.add(__VU);

  // Rotate through users 1-100 (seeder creates 1000 users starting from ID 1)
  const userId = (__VU % 100) + 1;

  try {
    if (SCENARIO === 'race_condition') {
      raceConditionScenario(userId);
    } else if (SCENARIO === 'mixed') {
      mixedScenario(userId);
    } else {
      normalScenario(userId);
    }
  } catch (error) {
    console.error(`Error in VU ${__VU}: ${error}`);
  }
}

// ================================================================
// SCENARIO 1: NORMAL WORKFLOW
// ================================================================
function normalScenario(userId) {
  // -- Add Points --
  group(`[Normal] User ${userId} - Add Points`, () => {
    const res = http.post(
      `${DEMO}/add`,
      JSON.stringify({ user_id: userId, points: 100, description: `Stress test iter ${__ITER}` }),
      Object.assign({}, HTTP_PARAMS, { tags: { name: 'AddPoints' } })
    );

    const ok = check(res, {
      'status 200':              (r) => r.status === 200,
      'body has success:true':   (r) => r.body && r.json('success') === true,
      'response time < 5000ms':  (r) => r.timings.duration < 5000,
    });

    addPointsDuration.add(res.timings.duration);
    addPointsErrorRate.add(res.status !== 200 ? 1 : 0);
    addPointsCount.add(1);
  });

  sleep(0.5);

  // -- Check Balance --
  group(`[Normal] User ${userId} - Check Balance`, () => {
    const res = http.get(
      `${DEMO}/balance/${userId}`,
      Object.assign({}, HTTP_PARAMS, { tags: { name: 'CheckBalance' } })
    );

    const ok = check(res, {
      'status 200':              (r) => r.status === 200,
      'body has balance field':  (r) => r.body && r.json('balance') !== undefined,
      'response time < 3000ms':  (r) => r.timings.duration < 3000,
    });

    balanceDuration.add(res.timings.duration);
    balanceErrorRate.add(res.status !== 200 ? 1 : 0);
    balanceCount.add(1);
  });

  sleep(0.5);

  // -- Redeem Points --
  group(`[Normal] User ${userId} - Redeem Points`, () => {
    const res = http.post(
      `${DEMO}/redeem`,
      JSON.stringify({ user_id: userId, points: 50, description: `Redeem iter ${__ITER}` }),
      Object.assign({}, HTTP_PARAMS, { tags: { name: 'RedeemPoints' } })
    );

    // 200 = success, 422 = insufficient points (valid business logic)
    const ok = check(res, {
      'status 200 or 422':       (r) => r.status === 200 || r.status === 422,
      'body not empty':          (r) => r.body && r.body.length > 0,
      'response time < 500ms':   (r) => r.timings.duration < 500,
    });

    redeemDuration.add(res.timings.duration);
    // 422 = insufficient points (valid), bukan error
    redeemErrorRate.add(res.status !== 200 && res.status !== 422 ? 1 : 0);
    redeemCount.add(1);
  });

  sleep(1);
}

// ================================================================
// SCENARIO 2: RACE CONDITION - CONCURRENT REDEEMS
// ================================================================
function raceConditionScenario(userId) {
  // Give user plenty of points first
  group(`[RaceCondition] User ${userId} - Load Points`, () => {
    http.post(
      `${DEMO}/add`,
      JSON.stringify({ user_id: userId, points: 50000, description: 'Race condition setup' }),
      { headers: { 'Content-Type': 'application/json' }, tags: { name: 'RaceSetup' } }
    );
  });

  sleep(0.2);

  // Fire 10 concurrent redeem requests for the same user
  group(`[RaceCondition] User ${userId} - Concurrent Redeems`, () => {
    const redeemBody = JSON.stringify({ user_id: userId, points: 1000, description: `Race iter ${__ITER}` });
    const requests = Array.from({ length: 10 }, () => ({
      method: 'POST',
      url: `${DEMO}/redeem`,
      body: redeemBody,
      params: { headers: { 'Content-Type': 'application/json' }, tags: { name: 'RaceRedeem' } },
    }));

    const responses = http.batch(requests);
    responses.forEach((r) => {
      const ok = check(r, {
        'status 200 or 422': (r) => r.status === 200 || r.status === 422,
        'no server error':   (r) => r.status !== 500,
      });
      raceConditionDuration.add(r.timings.duration);
      raceConditionErrorRate.add(ok ? 0 : 1);
      raceConditionCount.add(1);
    });
  });

  sleep(0.5);

  // Verify balance is consistent (no negative)
  group(`[RaceCondition] User ${userId} - Verify Balance`, () => {
    const res = http.get(
      `${DEMO}/balance/${userId}`,
      { tags: { name: 'RaceVerify' } }
    );
    check(res, {
      'balance retrieved':       (r) => r.status === 200,
      'balance non-negative':    (r) => (r.json('balance') ?? 0) >= 0,
    });
  });

  sleep(1);
}

// ================================================================
// SCENARIO 3: MIXED OPERATIONS
// ================================================================
function mixedScenario(userId) {
  const rand = Math.random();

  if (rand < 0.4) {
    normalScenario(userId);
  } else if (rand < 0.7) {
    // 30% - balance check only
    group(`[Mixed] User ${userId} - Balance Only`, () => {
      const res = http.get(`${DEMO}/balance/${userId}`, { tags: { name: 'MixedBalance' } });
      const ok = check(res, { 'status 200': (r) => r.status === 200 });
      balanceDuration.add(res.timings.duration);
      balanceErrorRate.add(ok ? 0 : 1);
      balanceCount.add(1);
    });
    sleep(0.5);
  } else if (rand < 0.9) {
    // 20% - add points only
    group(`[Mixed] User ${userId} - Add Points Only`, () => {
      const res = http.post(
        `${DEMO}/add`,
        JSON.stringify({ user_id: userId, points: Math.floor(Math.random() * 200) + 50, description: 'Mixed add' }),
        { headers: { 'Content-Type': 'application/json' }, tags: { name: 'MixedAdd' } }
      );
      const ok = check(res, { 'status 200': (r) => r.status === 200 });
      addPointsDuration.add(res.timings.duration);
      addPointsErrorRate.add(ok ? 0 : 1);
      addPointsCount.add(1);
    });
    sleep(0.5);
  } else {
    // 10% - redeem only
    group(`[Mixed] User ${userId} - Redeem Only`, () => {
      const res = http.post(
        `${DEMO}/redeem`,
        JSON.stringify({ user_id: userId, points: Math.floor(Math.random() * 100) + 20, description: 'Mixed redeem' }),
        { headers: { 'Content-Type': 'application/json' }, tags: { name: 'MixedRedeem' } }
      );
      const ok = check(res, { 'status 200 or 422': (r) => r.status === 200 || r.status === 422 });
      redeemDuration.add(res.timings.duration);
      redeemErrorRate.add(ok ? 0 : 1);
      redeemCount.add(1);
    });
    sleep(0.5);
  }

  sleep(Math.random() * 2);
}

// ================================================================
// TEARDOWN
// ================================================================
export function teardown(data) {
  console.log(`
╔════════════════════════════════════════════════════════════════╗
║                    TEST COMPLETED                              ║
╠════════════════════════════════════════════════════════════════╣
║ Start Time:         ${data.startTime}
║ End Time:           ${new Date().toISOString()}
║ Total VUs:          ${LOAD}
║ Scenario:           ${SCENARIO}
╚════════════════════════════════════════════════════════════════╝
  `);
}
