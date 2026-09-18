import axios from 'axios';

export const apiSukuCadang = axios.create({
  headers: { Accept: 'application/json' },
});
