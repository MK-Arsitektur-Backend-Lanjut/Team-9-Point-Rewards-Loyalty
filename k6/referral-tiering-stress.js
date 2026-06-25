/*
  ╔════════════════════════════════════════════════════════════════╗
  ║      K6 STRESS TEST - REFERRAL & TIERING SYSTEM               ║
  ║           Laravel Membership Module (Team 9)                  ║
  ╚════════════════════════════════════════════════════════════════╝

  Script untuk stress testing modul Referral dan Tiering.

  Endpoint yang diuji:
  - POST /api/membership/referrals/generate   → Generate referral code
  - POST /api/membership/referrals/apply      → Apply referral code
  - POST /api/membership/tiers/recalculate    → Recalculate user tier
  - GET  /api/membership/tiers               → List all tiers

  Skenario:
  - normal        : Workflow lengkap (generate → register → apply → recalculate)
  - race_condition: Banyak VU coba apply kode referral yang sama serentak
  - mixed         : Campuran generate, apply, dan tiering

  Penggunaan:
    k6 run k6/referral-tiering-stress.js
    k6 run k6/referral-tiering-stress.js --env SCENARIO=race_condition --env LOAD=100
    k6 run k6/referral-tiering-stress.js --env SCENARIO=mixed --env LOAD=50 --env DURATION=2m
*/

import http from 'k6/http';
import { check, group, sleep, fail } from 'k6';
import { Rate, Trend, Counter, Gauge } from 'k6/metrics';

// ================================================================
// CUSTOM METRICS
// ================================================================

// --- Error Rates ---
const generateReferralErrors  = new Rate('generate_referral_errors');
const applyReferralErrors     = new Rate('apply_referral_errors');
const recalculateTierErrors   = new Rate('recalculate_tier_errors');
const listTiersErrors         = new Rate('list_tiers_errors');
const registerErrors          = new Rate('register_errors');
const raceConditionErrors     = new Rate('race_condition_errors');

// --- Response Time Trends ---
const generateReferralDuration  = new Trend('generate_referral_duration', true);
const applyReferralDuration     = new Trend('apply_referral_duration', true);
const recalculateTierDuration   = new Trend('recalculate_tier_duration', true);
const listTiersDuration         = new Trend('list_tiers_duration', true);
const registerDuration          = new Trend('register_duration', true);
const raceConditionDuration     = new Trend('race_condition_duration', true);

// --- Request Counters ---
const generateReferralTotal  = new Counter('generate_referral_total');
const applyReferralTotal     = new Counter('apply_referral_total');
const recalculateTierTotal   = new Counter('recalculate_tier_total');
const listTiersTotal         = new Counter('list_tiers_total');
const raceConditionTotal     = new Counter('race_condition_total');

// --- Active VUs Gauge ---
const activeVUs = new Gauge('active_vus');

// ================================================================
// CONFIGURATION
// ================================================================
const BASE_URL  = __ENV.BASE_URL  || 'http://localhost:8000';
const SCENARIO  = (__ENV.SCENARIO || 'normal').toLowerCase();
const LOAD      = parseInt(__ENV.LOAD      || '100');
const DURATION  = __ENV.DURATION  || '1m';
const RAMP_UP   = __ENV.RAMP_UP   || '15s';
const RAMP_DOWN = __ENV.RAMP_DOWN || '10s';

// Referrer yang sudah ada di database (dari seeder)
const REFERRER_ID = parseInt(__ENV.REFERRER_ID || '1');

console.log(`
╔════════════════════════════════════════════════════════════════╗
║              REFERRAL & TIERING STRESS TEST                    ║
╠════════════════════════════════════════════════════════════════╣
║  Base URL   : ${BASE_URL.padEnd(46)}║
║  Scenario   : ${SCENARIO.padEnd(46)}║
║  Load (VUs) : ${String(LOAD).padEnd(46)}║
║  Duration   : ${DURATION.padEnd(46)}║
║  Ramp Up    : ${RAMP_UP.padEnd(46)}║
║  Ramp Down  : ${RAMP_DOWN.padEnd(46)}║
╚════════════════════════════════════════════════════════════════╝
`);

// ================================================================
// TEST OPTIONS & THRESHOLDS
// ================================================================
export const options = {
  scenarios: {
    referral_tiering: {
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
    // Response time targets
    'generate_referral_duration': ['p(95)<500', 'p(99)<1000', 'avg<300'],
    'apply_referral_duration':    ['p(95)<800', 'p(99)<1500', 'avg<500'],
    'recalculate_tier_duration':  ['p(95)<1000', 'p(99)<2000', 'avg<600'],
    'list_tiers_duration':        ['p(95)<300', 'p(99)<600', 'avg<200'],

    // Error rate targets
    'generate_referral_errors': ['rate<0.05'],
    'apply_referral_errors':    ['rate<0.10'],
    'recalculate_tier_errors':  ['rate<0.05'],
    'list_tiers_errors':        ['rate<0.02'],
    'race_condition_errors':    ['rate<0.50'],

    // Overall
    'http_req_duration': ['p(95)<2000', 'p(99)<3000'],
    'http_req_failed':   ['rate<0.10'],
  },
};

// ================================================================
// HELPERS
// ================================================================
function jsonHeaders() {
  return { headers: { 'Content-Type': 'application/json' } };
}

/** Daftarkan user baru agar bisa apply referral (setiap iterasi unik) */
function registerUser() {
  const suffix = `${__VU}_${__ITER}_${Date.now()}`;
  const res = http.post(
    `${BASE_URL}/api/register`,
    JSON.stringify({
      name:                  `K6 User ${suffix}`,
      email:                 `k6_${suffix}@test.local`,
      password:              'password',
      password_confirmation: 'password',
    }),
    jsonHeaders()
  );

  registerDuration.add(res.timings.duration);
  const ok = check(res, {
    'register: status 201': (r) => r.status === 201,
    'register: returns user id': (r) => {
      try { return !!r.json()?.data?.user?.id; } catch { return false; }
    },
  });
  if (!ok) console.error(`Register failed: ${res.status} ${res.body}`);
  registerErrors.add(!ok ? 1 : 0);

  if (res.status !== 201) return null;
  return res.json()?.data?.user?.id ?? null;
}

/** Generate referral code untuk user tertentu */
function generateReferralCode(userId) {
  const res = http.post(
    `${BASE_URL}/api/membership/referrals/generate`,
    JSON.stringify({ user_id: userId }),
    jsonHeaders()
  );

  generateReferralDuration.add(res.timings.duration);
  generateReferralTotal.add(1);

  const ok = check(res, {
    'generate: status 200': (r) => r.status === 200,
    'generate: has referral_code': (r) => {
      try {
        const b = r.json();
        return b && typeof b.referral_code === 'string' && b.referral_code.length > 0;
      } catch { return false; }
    },
  });
  generateReferralErrors.add(!ok ? 1 : 0);

  if (res.status !== 200) return null;
  return res.json()?.referral_code ?? null;
}

/** Apply referral code ke user */
function applyReferral(refereeId, referralCode) {
  const res = http.post(
    `${BASE_URL}/api/membership/referrals/apply`,
    JSON.stringify({ user_id: refereeId, referral_code: referralCode }),
    jsonHeaders()
  );

  applyReferralDuration.add(res.timings.duration);
  applyReferralTotal.add(1);

  const ok = check(res, {
    'apply: status 200 or 422': (r) => r.status === 200 || r.status === 422,
    'apply: has body': (r) => r.body && r.body.length > 0,
    'apply: 200 has referrer+referee': (r) => {
      if (r.status !== 200) return true; // 422 is acceptable
      try { const b = r.json(); return !!(b?.referrer && b?.referee); } catch { return false; }
    },
  });
  if (!ok) console.error(`Apply referral failed: ${res.status} ${res.body}`);
  applyReferralErrors.add(res.status >= 500 || !ok ? 1 : 0);

  return res.status;
}

/** Recalculate tier untuk user */
function recalculateTier(userId) {
  const res = http.post(
    `${BASE_URL}/api/membership/tiers/recalculate`,
    JSON.stringify({ user_id: userId }),
    jsonHeaders()
  );

  recalculateTierDuration.add(res.timings.duration);
  recalculateTierTotal.add(1);

  const ok = check(res, {
    'recalculate: status 200': (r) => r.status === 200,
    'recalculate: has tier field': (r) => {
      try {
        const b = r.json();
        return b && Object.prototype.hasOwnProperty.call(b, 'tier');
      } catch { return false; }
    },
  });
  recalculateTierErrors.add(!ok ? 1 : 0);

  return res;
}

/** List semua tier */
function listTiers() {
  const res = http.get(
    `${BASE_URL}/api/membership/tiers`,
    { tags: { name: 'ListTiers' } }
  );

  listTiersDuration.add(res.timings.duration);
  listTiersTotal.add(1);

  const ok = check(res, {
    'list tiers: status 200': (r) => r.status === 200,
    'list tiers: has data': (r) => {
      try { return Array.isArray(r.json()); } catch { return false; }
    },
  });
  if (!ok) console.error(`List tiers failed: ${res.status} ${res.body}`);
  listTiersErrors.add(!ok ? 1 : 0);

  return res;
}

// ================================================================
// SETUP
// ================================================================
export function setup() {
  console.log('🚀 Setup dimulai...');
  console.log(`   Scenario : ${SCENARIO}`);
  console.log(`   Load     : ${LOAD} VUs`);
  console.log(`   Referrer : User ID ${REFERRER_ID}`);

  return {
    baseUrl:   BASE_URL,
    scenario:  SCENARIO,
    startTime: new Date().toISOString(),
  };
}

// ================================================================
// MAIN TEST FUNCTION
// ================================================================
export default function (data) {
  activeVUs.add(__VU);

  try {
    switch (SCENARIO) {
      case 'race_condition':
        raceConditionScenario();
        break;
      case 'mixed':
        mixedScenario();
        break;
      default:
        normalScenario();
    }
  } catch (err) {
    console.error(`❌ Error di VU ${__VU} iter ${__ITER}: ${err}`);
  }
}

// ================================================================
// SCENARIO 1: NORMAL — full workflow
// ================================================================
function normalScenario() {
  // Step 1 – Generate referral code untuk referrer tetap
  let referralCode;
  group('Step 1: Generate Referral Code', () => {
    referralCode = generateReferralCode(REFERRER_ID);
  });

  if (!referralCode) {
    sleep(1);
    return;
  }

  sleep(0.3);

  // Step 2 – Daftarkan referee baru
  let refereeId;
  group('Step 2: Register Referee', () => {
    refereeId = registerUser();
  });

  if (!refereeId) {
    sleep(1);
    return;
  }

  sleep(0.3);

  // Step 3 – Apply referral
  group('Step 3: Apply Referral Code', () => {
    applyReferral(refereeId, referralCode);
  });

  sleep(0.3);

  // Step 4 – Recalculate tier untuk referrer (mendapat poin referral)
  group('Step 4: Recalculate Tier (Referrer)', () => {
    recalculateTier(REFERRER_ID);
  });

  sleep(0.3);

  // Step 5 – Recalculate tier untuk referee (tier baru)
  group('Step 5: Recalculate Tier (Referee)', () => {
    recalculateTier(refereeId);
  });

  sleep(0.5);

  // Step 6 – List tier (baca ringan)
  group('Step 6: List All Tiers', () => {
    listTiers();
  });

  sleep(1);
}

// ================================================================
// SCENARIO 2: RACE CONDITION — banyak VU apply kode yang sama
// ================================================================
function raceConditionScenario() {
  // Semua VU menggunakan kode referral dari referrer yang sama
  // (kode yang sama di-apply oleh banyak user serentak)

  let referralCode;
  group('[RaceCondition] Get Shared Referral Code', () => {
    referralCode = generateReferralCode(REFERRER_ID);
  });

  if (!referralCode) {
    sleep(1);
    return;
  }

  sleep(0.1);

  // Daftarkan beberapa referee baru sekaligus (batch)
  group('[RaceCondition] Register Multiple Referees & Apply', () => {
    // Buat 3 referee serentak menggunakan http.batch
    const registerPayloads = [0, 1, 2].map((i) => {
      const suffix = `rc_${__VU}_${__ITER}_${i}_${Date.now()}`;
      return {
        method: 'POST',
        url:    `${BASE_URL}/api/register`,
        body:   JSON.stringify({
          name:                  `RC User ${suffix}`,
          email:                 `rc_${suffix}@test.local`,
          password:              'password',
          password_confirmation: 'password',
        }),
        params: jsonHeaders(),
      };
    });

    const registerResponses = http.batch(registerPayloads);

    // Ambil IDs yang berhasil terdaftar
    const refereeIds = registerResponses
      .filter((r) => r.status === 201)
      .map((r) => {
        try { return r.json()?.data?.user?.id; } catch { return null; }
      })
      .filter(Boolean);

    if (refereeIds.length === 0) {
      sleep(1);
      return;
    }

    // Langsung apply referral code secara bersamaan (race condition)
    const applyRequests = refereeIds.map((id) => ({
      method: 'POST',
      url:    `${BASE_URL}/api/membership/referrals/apply`,
      body:   JSON.stringify({ user_id: id, referral_code: referralCode }),
      params: jsonHeaders(),
    }));

    const applyResponses = http.batch(applyRequests);

    applyResponses.forEach((res) => {
      raceConditionDuration.add(res.timings.duration);
      raceConditionTotal.add(1);

      const ok = check(res, {
        'race: valid response (200 or 422)': (r) => r.status === 200 || r.status === 422,
        'race: no server error': (r) => r.status < 500,
      });
      raceConditionErrors.add(!ok ? 1 : 0);
    });
  });

  sleep(0.5);

  // Verifikasi tier referrer setelah referral diterima
  group('[RaceCondition] Recalculate Referrer Tier', () => {
    recalculateTier(REFERRER_ID);
  });

  sleep(1);
}

// ================================================================
// SCENARIO 3: MIXED — proporsi beban berbeda
// ================================================================
function mixedScenario() {
  const rand = Math.random();

  if (rand < 0.35) {
    // 35% — full normal workflow
    normalScenario();

  } else if (rand < 0.60) {
    // 25% — hanya recalculate tier (operasi paling sering di prod)
    group('[Mixed] Recalculate Tier Only', () => {
      const userId = REFERRER_ID + (__VU % 100);
      recalculateTier(userId);
    });
    sleep(0.5);

  } else if (rand < 0.80) {
    // 20% — generate referral code saja
    group('[Mixed] Generate Referral Code Only', () => {
      const userId = REFERRER_ID + (__VU % 50);
      generateReferralCode(userId);
    });
    sleep(0.5);

  } else if (rand < 0.95) {
    // 15% — list tiers (read-only, paling ringan)
    group('[Mixed] List Tiers Only', () => {
      listTiers();
    });
    sleep(0.3);

  } else {
    // 5% — race condition mini
    group('[Mixed] Mini Race Condition', () => {
      const refCode = generateReferralCode(REFERRER_ID);
      if (!refCode) return;

      const suffix   = `mix_${__VU}_${__ITER}_${Date.now()}`;
      const regRes   = http.post(
        `${BASE_URL}/api/register`,
        JSON.stringify({
          name:                  `Mix User ${suffix}`,
          email:                 `mix_${suffix}@test.local`,
          password:              'password',
          password_confirmation: 'password',
        }),
        jsonHeaders()
      );
      if (regRes.status !== 201) return;

      const uid = regRes.json()?.data?.user?.id;
      if (!uid) return;

      applyReferral(uid, refCode);
      recalculateTier(uid);
    });
    sleep(1);
  }

  sleep(Math.random() * 1.5);
}

// ================================================================
// TEARDOWN
// ================================================================
export function teardown(data) {
  console.log(`
╔════════════════════════════════════════════════════════════════╗
║                    TEST SELESAI ✅                              ║
╠════════════════════════════════════════════════════════════════╣
║  Start : ${data.startTime.padEnd(53)}║
║  End   : ${new Date().toISOString().padEnd(53)}║
║  VUs   : ${String(LOAD).padEnd(53)}║
║  Skenario: ${SCENARIO.padEnd(51)}║
╚════════════════════════════════════════════════════════════════╝
  `);
}
