import axios from 'axios';

export const apiPersetujuan = axios.create({
  headers: { Accept: 'application/json' },
});
