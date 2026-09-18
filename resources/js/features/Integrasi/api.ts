import axios from 'axios';

export const apiIntegrasi = axios.create({
  headers: { Accept: 'application/json' },
});
