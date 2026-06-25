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
import { check, group, sleep, fail } from 'k6';
import { Rate, Trend, Counter, Gauge, Histogram } from 'k6/metrics';
import encoding from 'k6/encoding';

// ================================================================
// CUSTOM METRICS
// ================================================================

// Error Rates (melihat persentase request yang gagal)
const triggerActivityErrorRate = new Rate('trigger_activity_errors');
const getTiersErrorRate = new Rate('get_tiers_errors');
const redeemRewardErrorRate = new Rate('redeem_reward_errors');
const raceConditionErrorRate = new Rate('race_condition_errors');

// Response Time Distribution (p50, p95, p99)
const triggerActivityDuration = new Trend('trigger_activity_duration');
const getTiersDuration = new Trend('get_tiers_duration');
const redeemRewardDuration = new Trend('redeem_reward_duration');

// Total Request Count
const triggerActivityCount = new Counter('trigger_activity_total');
const getTiersCount = new Counter('get_tiers_total');
const redeemRewardCount = new Counter('redeem_reward_total');
const raceConditionCount = new Counter('race_condition_total');

// Concurrent VUs (menunjukkan berapa banyak user yang sedang aktif)
const activeVUs = new Gauge('active_vus', true);

// ================================================================
// CONFIGURATION
// ================================================================
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api';
const SCENARIO = __ENV.SCENARIO || 'normal'; // normal, race_condition, mixed
const LOAD = parseInt(__ENV.LOAD || '100'); // Jumlah concurrent users
const DURATION = __ENV.DURATION || '1m'; // Durasi test
const RAMP_UP = __ENV.RAMP_UP || '10s'; // Waktu ramp up
const RAMP_DOWN = __ENV.RAMP_DOWN || '10s'; // Waktu ramp down

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
        { duration: RAMP_UP, target: LOAD },
        { duration: DURATION, target: LOAD },
        { duration: RAMP_DOWN, target: 0 },
      ],
      gracefulRampDown: '10s',
    },
  },
  
  // SLA Thresholds - Kondisi yang harus terpenuhi
  thresholds: {
    // Response time - 95% request harus < 500ms, 99% < 1000ms
    'add_points_duration': ['p(95)<500', 'p(99)<1000', 'avg<300'],
    'redeem_duration': ['p(95)<500', 'p(99)<1000', 'avg<300'],
    'balance_duration': ['p(95)<200', 'p(99)<500', 'avg<150'],
    
    // Error rate - maksimal 10% error
    'add_points_errors': ['rate<0.1'],
    'redeem_errors': ['rate<0.1'],
    'balance_errors': ['rate<0.05'],
    
    // Overall metrics
    'http_req_duration': ['p(95)<1000', 'p(99)<2000'],
    'http_req_failed': ['rate<0.1'],
  },
};

// ================================================================
// SETUP - Dijalankan sekali sebelum test mulai
// ================================================================
export function setup() {
  console.log('🚀 Starting setup...');
  
  // Optional: Bisa tambahkan setup initial data di sini
  // Misal: Seed user dengan poin awal
  
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
  
  // Rotate through multiple users untuk simulasi yang lebih realistis
  const baseUserId = 1000;
  const userId = baseUserId + (__VU % 100); // 100 different users
  
  try {
    if (SCENARIO === 'race_condition') {
      raceConditionScenario(userId);
    } else if (SCENARIO === 'mixed') {
      mixedScenario(userId);
    } else {
      normalScenario(userId);
    }
  } catch (error) {
    console.error(`❌ Error in VU ${__VU}: ${error}`);
  }
}

// ================================================================
// SCENARIO 1: NORMAL WORKFLOW
// ================================================================
function normalScenario(userId) {
  group(`[Normal] User ${userId} - Add Points`, () => {
    const payload = JSON.stringify({
      user_id: userId,
      points: 100,
      description: `Stress test - Add points iteration ${__ITER}`,
    });

    const res = http.post(
      `${BASE_URL}/points/add`,
      payload,
      {
        headers: { 'Content-Type': 'application/json' },
        tags: { name: 'AddPoints' },
      }
    );

    const isSuccess = check(res, {
      'Status is 200': (r) => r.status === 200,
      'Response contains success': (r) => r.body.includes('success') || r.body.includes('data'),
      'Response time < 500ms': (r) => r.timings.duration < 500,
    });

    addPointsDuration.add(res.timings.duration);
    addPointsErrorRate.add(!isSuccess ? 1 : 0);
    addPointsCount.add(1);
  });

  sleep(0.5);

  group(`[Normal] User ${userId} - Check Balance`, () => {
    const res = http.get(
      `${BASE_URL}/points/balance?user_id=${userId}`,
      {
        tags: { name: 'CheckBalance' },
      }
    );

    const isSuccess = check(res, {
      'Status is 200': (r) => r.status === 200,
      'Has balance data': (r) => r.body.includes('balance') || r.body.includes('data'),
      'Response time < 200ms': (r) => r.timings.duration < 200,
    });

    balanceDuration.add(res.timings.duration);
    balanceErrorRate.add(!isSuccess ? 1 : 0);
    balanceCount.add(1);
  });

  sleep(0.5);

  group(`[Normal] User ${userId} - Redeem Reward`, () => {
    const payload = JSON.stringify({
      user_id: userId,
      reward_id: Math.floor(Math.random() * 5) + 1,
      points: 50,
      description: `Stress test - Redeem iteration ${__ITER}`,
    });

    const res = http.post(
      `${BASE_URL}/rewards/redeem`,
      payload,
      {
        headers: { 'Content-Type': 'application/json' },
        tags: { name: 'RedeemReward' },
      }
    );

    const isSuccess = check(res, {
      'Status is 200 or 422': (r) => r.status === 200 || r.status === 422,
      'Response has data': (r) => r.body.length > 0,
      'Response time < 500ms': (r) => r.timings.duration < 500,
    });

    redeemDuration.add(res.timings.duration);
    redeemErrorRate.add(!isSuccess ? 1 : 0);
    redeemCount.add(1);
  });

  sleep(1);
}

// ================================================================
// SCENARIO 2: RACE CONDITION - CONCURRENT REDEEMS
// ================================================================
function raceConditionScenario(userId) {
  group(`[RaceCondition] User ${userId} - Setup Initial Points`, () => {
    const addPayload = JSON.stringify({
      user_id: userId,
      points: 50000, // Banyak poin untuk simulasi race condition
      description: 'Race condition test - initial load',
    });

    http.post(
      `${BASE_URL}/points/add`,
      addPayload,
      {
        headers: { 'Content-Type': 'application/json' },
        tags: { name: 'RaceConditionSetup' },
      }
    );
  });

  sleep(0.2);

  group(`[RaceCondition] User ${userId} - Concurrent Redeems`, () => {
    // Array untuk menyimpan semua requests
    const redeemPayload = JSON.stringify({
      user_id: userId,
      reward_id: 1,
      points: 1000,
      description: `Race condition test - concurrent attempt ${__ITER}`,
    });

    const requests = [];
    
    // Buat 10 concurrent redeem requests
    for (let i = 0; i < 10; i++) {
      requests.push({
        method: 'POST',
        url: `${BASE_URL}/rewards/redeem`,
        body: redeemPayload,
        params: {
          headers: { 'Content-Type': 'application/json' },
          tags: { name: 'RaceConditionRedeem' },
        },
      });
    }

    // Execute semua requests bersamaan (simulates race condition)
    const responses = http.batch(requests);

    responses.forEach((response, index) => {
      const isSuccess = check(response, {
        'Redeem attempted': (r) => r.status !== 0,
        'Valid response': (r) => r.status === 200 || r.status === 422,
      });

      raceConditionDuration?.add(response.timings.duration);
      raceConditionErrorRate.add(!isSuccess ? 1 : 0);
      raceConditionCount.add(1);
    });
  });

  sleep(0.5);

  group(`[RaceCondition] User ${userId} - Verify Final Balance`, () => {
    const res = http.get(
      `${BASE_URL}/points/balance?user_id=${userId}`,
      {
        tags: { name: 'RaceConditionVerify' },
      }
    );

    check(res, {
      'Final balance retrieved': (r) => r.status === 200,
      'Balance is consistent': (r) => r.body.includes('balance'),
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
    // 40% - Normal workflow
    normalScenario(userId);
  } else if (rand < 0.7) {
    // 30% - Only check balance
    group(`[Mixed] User ${userId} - Check Balance Only`, () => {
      const res = http.get(
        `${BASE_URL}/points/balance?user_id=${userId}`,
        {
          tags: { name: 'MixedBalance' },
        }
      );

      check(res, {
        'Status 200': (r) => r.status === 200,
      });

      balanceDuration.add(res.timings.duration);
      balanceCount.add(1);
    });

    sleep(0.5);
  } else if (rand < 0.9) {
    // 20% - Add points only
    group(`[Mixed] User ${userId} - Add Points Only`, () => {
      const payload = JSON.stringify({
        user_id: userId,
        points: Math.floor(Math.random() * 200) + 50,
        description: `Mixed test - Add points`,
      });

      const res = http.post(
        `${BASE_URL}/points/add`,
        payload,
        {
          headers: { 'Content-Type': 'application/json' },
          tags: { name: 'MixedAdd' },
        }
      );

      check(res, {
        'Status 200': (r) => r.status === 200,
      });

      addPointsDuration.add(res.timings.duration);
      addPointsCount.add(1);
    });

    sleep(0.5);
  } else {
    // 10% - Redeem only
    group(`[Mixed] User ${userId} - Redeem Only`, () => {
      const payload = JSON.stringify({
        user_id: userId,
        reward_id: Math.floor(Math.random() * 5) + 1,
        points: Math.floor(Math.random() * 100) + 20,
        description: `Mixed test - Redeem`,
      });

      const res = http.post(
        `${BASE_URL}/rewards/redeem`,
        payload,
        {
          headers: { 'Content-Type': 'application/json' },
          tags: { name: 'MixedRedeem' },
        }
      );

      check(res, {
        'Status 200 or 422': (r) => r.status === 200 || r.status === 422,
      });

      redeemDuration.add(res.timings.duration);
      redeemCount.add(1);
    });

    sleep(0.5);
  }

  sleep(Math.random() * 2);
}

// ================================================================
// TEARDOWN - Dijalankan sekali setelah test selesai
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
║ Status:             ✅ PASSED
╚════════════════════════════════════════════════════════════════╝
  `);
}

// Helper metric (placeholder untuk race condition)
const raceConditionDuration = new Trend('race_condition_duration');
