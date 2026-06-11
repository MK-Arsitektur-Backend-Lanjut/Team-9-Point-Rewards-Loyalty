import http from 'k6/http';

export const options = {
  vus: 1000,
  duration: '10s',
};

export default function () {
  let res = http.get('http://localhost:8000/api/rewards');

  if (res.status !== 200) {
    console.log('STATUS:', res.status);
    console.log(res.body);
  }
}