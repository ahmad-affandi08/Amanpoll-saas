import axios from 'axios';

export const apiInspeksi = axios.create({
  headers: { Accept: 'application/json' },
});
