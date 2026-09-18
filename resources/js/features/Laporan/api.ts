import axios from 'axios';

export const apiLaporan = axios.create({
  headers: { Accept: 'application/json' },
});
