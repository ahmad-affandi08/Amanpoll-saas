import axios from 'axios';

export const apiPaketLangganan = axios.create({
  headers: { Accept: 'application/json' },
});
