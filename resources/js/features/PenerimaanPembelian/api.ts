import axios from 'axios';

export const apiPenerimaanPembelian = axios.create({
  headers: { Accept: 'application/json' },
});
