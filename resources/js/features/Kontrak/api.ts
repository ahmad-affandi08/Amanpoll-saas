import axios from 'axios';

export const apiKontrak = axios.create({
  headers: { Accept: 'application/json' },
});
