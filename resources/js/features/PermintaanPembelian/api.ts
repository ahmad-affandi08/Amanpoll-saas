import axios from 'axios';

export const apiPermintaanPembelian = axios.create({
  headers: { Accept: 'application/json' },
});
