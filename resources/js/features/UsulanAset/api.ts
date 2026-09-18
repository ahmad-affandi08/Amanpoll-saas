import axios from 'axios';

export const apiUsulanAset = axios.create({
  headers: { Accept: 'application/json' },
});
