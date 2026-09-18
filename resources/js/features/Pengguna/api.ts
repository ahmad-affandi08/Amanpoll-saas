import axios from 'axios';

export const apiPengguna = axios.create({
  headers: { Accept: 'application/json' },
});
