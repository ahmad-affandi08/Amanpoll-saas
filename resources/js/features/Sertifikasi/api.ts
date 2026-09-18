import axios from 'axios';

export const apiSertifikasi = axios.create({
  headers: { Accept: 'application/json' },
});
