import axios from 'axios';

export const apiStok = axios.create({
  headers: { Accept: 'application/json' },
});
