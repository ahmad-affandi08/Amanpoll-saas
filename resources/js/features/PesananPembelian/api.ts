import axios from 'axios';

export const apiPesananPembelian = axios.create({
  headers: { Accept: 'application/json' },
});
