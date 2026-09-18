import axios from 'axios';

export const apiTingkatLayanan = axios.create({
  headers: { Accept: 'application/json' },
});
