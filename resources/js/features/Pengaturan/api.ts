import axios from 'axios';

export const apiPengaturan = axios.create({
  headers: { Accept: 'application/json' },
});
