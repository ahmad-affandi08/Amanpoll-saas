import axios from 'axios';

export const apiKalibrasi = axios.create({
  headers: { Accept: 'application/json' },
});
