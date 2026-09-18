import axios from 'axios';

export const apiSinkronisasi = axios.create({
  headers: { Accept: 'application/json' },
});
