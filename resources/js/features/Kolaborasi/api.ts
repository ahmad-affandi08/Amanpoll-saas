import axios from 'axios';

export const apiKolaborasi = axios.create({
  headers: { Accept: 'application/json' },
});
