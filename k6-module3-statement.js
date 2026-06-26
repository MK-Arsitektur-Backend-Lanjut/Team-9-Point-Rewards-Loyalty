// k6-module3-statement.js
// Stress Test khusus E-Statement dengan 35.000+ data

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = 'http://localhost:8002/api';
const ADMIN_EMAIL = 'admin2@example.com';
const ADMIN_PASSWORD = 'password';

const errorRate = new Rate('errors');
const statementTrend = new Trend('statement_duration');

export const options = {
  scenarios: {
    // 1. Normal Load - 50 users
    normal: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 50 },
        { duration: '2m', target: 50 },
        { duration: '30s', target: 0 },
      ],
      exec: 'statementTest',
      tags: { test_type: 'normal' },
    },
    // 2. Heavy Load - 100 users
    heavy: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 100 },
        { duration: '2m', target: 100 },
        { duration: '30s', target: 0 },
      ],
      exec: 'statementTest',
      tags: { test_type: 'heavy' },
      startTime: '3m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    statement_duration: ['p(95)<1000'],
    'statement_duration{test_type:normal}': ['p(95)<500'],
    'statement_duration{test_type:heavy}': ['p(95)<1500'],
  },
};

export function setup() {
  const loginPayload = JSON.stringify({
    email: ADMIN_EMAIL,
    password: ADMIN_PASSWORD,
  });

  const res = http.post(`${BASE_URL}/login`, loginPayload, {
    headers: { 'Content-Type': 'application/json' },
  });

  const token = res.json('access_token');
  return { token };
}

export function statementTest(data) {
  const { token } = data;
  
  const params = {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  };

  // Test berbagai skenario statement
  const scenarios = [
    // 1. Statement dengan pagination
    { url: `${BASE_URL}/statement?page=1&limit=20`, name: 'page1' },
    { url: `${BASE_URL}/statement?page=5&limit=20`, name: 'page5' },
    { url: `${BASE_URL}/statement?page=10&limit=20`, name: 'page10' },
    
    // 2. Statement dengan filter tanggal
    { url: `${BASE_URL}/statement?from=2026-01-01&to=2026-06-30`, name: 'date_filter' },
    { url: `${BASE_URL}/statement?from=2026-01-01&to=2026-03-31`, name: 'quarter1' },
    
    // 3. Statement dengan filter tipe transaksi
    { url: `${BASE_URL}/statement?type=earn`, name: 'type_earn' },
    { url: `${BASE_URL}/statement?type=redeem`, name: 'type_redeem' },
    
    // 4. Statement dengan sorting
    { url: `${BASE_URL}/statement?sort=earned_at&order=desc`, name: 'sort_desc' },
    { url: `${BASE_URL}/statement?sort=points_earned&order=asc`, name: 'sort_asc' },
    
    // 5. Statement dengan limit besar
    { url: `${BASE_URL}/statement?page=1&limit=100`, name: 'limit_100' },
    { url: `${BASE_URL}/statement?page=1&limit=500`, name: 'limit_500' },
  ];

  // Random pilih 3-5 skenario per iterasi
  const selectedScenarios = scenarios
    .sort(() => Math.random() - 0.5)
    .slice(0, Math.floor(Math.random() * 3) + 3);

  for (const scenario of selectedScenarios) {
    const res = http.get(scenario.url, params);
    statementTrend.add(res.timings.duration);
    
    const success = check(res, {
      [`statement ${scenario.name} status 200`]: (r) => r.status === 200,
    });
    
    errorRate.add(!success);
  }

  sleep(Math.random() * 2 + 1);
}