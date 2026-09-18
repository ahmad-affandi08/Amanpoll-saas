import axios from 'axios';

export const apiPermintaanPenawaran = axios.create({
  headers: { Accept: 'application/json' },
});
