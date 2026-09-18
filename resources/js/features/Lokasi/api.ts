import axios from 'axios';

export const apiLokasi = axios.create({
  headers: { Accept: 'application/json' },
});
