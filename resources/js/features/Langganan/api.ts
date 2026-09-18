import axios from 'axios';

export const apiLangganan = axios.create({
  headers: { Accept: 'application/json' },
});
