import http from 'k6/http';
import { check, sleep, group } from 'k6';

const token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDEvYXBpL2xvZ2luIiwiaWF0IjoxNzgxMTM4MzIzLCJleHAiOjE3ODExNDE5MjMsIm5iZiI6MTc4MTEzODMyMywianRpIjoidXprcFBSeENnWXVGeGJnUCIsInN1YiI6IjEwMDUiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.Li3Jt0PQODhAAFcYavb6_nJeqitJkQA6NfcR6LHk73A";

export const options = {
    vus: 10,
    duration: '30s',
    thresholds: {
        'http_req_duration': ['p(95)<2000'],
        'http_req_failed': ['rate<0.05'],
    },
};

export default function () {
    const host = 'http://localhost:8001';
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
    };
    
    group('Modul 3 - E-Statement Testing', function () {
        // Test 1: Get Statement tanpa filter
        let res1 = http.get(`${host}/api/statement`, { headers });
        check(res1, { 
            'Statement tanpa filter - status 200': (r) => r.status === 200 
        });
        
        // Test 2: Get Statement dengan filter tanggal (uji index database)
        let res2 = http.get(`${host}/api/statement?start_date=2025-01-01&end_date=2025-12-31`, { headers });
        check(res2, { 
            'Statement dengan filter tanggal - status 200': (r) => r.status === 200 
        });
        
        // Test 3: Points Balance (endpoint yang benar: api/points/balance)
        let res3 = http.get(`${host}/api/points/balance`, { headers });
        check(res3, { 
            'Points Balance - status 200': (r) => r.status === 200 
        });
        
        // Test 4: Statement dengan pagination
        let res4 = http.get(`${host}/api/statement?per_page=20&page=2`, { headers });
        check(res4, { 
            'Statement pagination - status 200': (r) => r.status === 200 
        });
    });
    
    sleep(1);
}