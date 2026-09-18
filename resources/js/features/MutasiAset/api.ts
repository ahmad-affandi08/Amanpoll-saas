import axios from 'axios';

export const apiMutasiAset = axios.create({
  headers: { Accept: 'application/json' },
});
