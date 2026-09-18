import axios from 'axios';

export const apiPemeliharaan = axios.create({
  headers: { Accept: 'application/json' },
});
