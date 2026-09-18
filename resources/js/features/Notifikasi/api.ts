import axios from 'axios';

export const apiNotifikasi = axios.create({
  headers: { Accept: 'application/json' },
});
