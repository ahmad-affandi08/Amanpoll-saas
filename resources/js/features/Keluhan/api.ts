import axios from 'axios';

export const apiKeluhan = axios.create({
  headers: { Accept: 'application/json' },
});
