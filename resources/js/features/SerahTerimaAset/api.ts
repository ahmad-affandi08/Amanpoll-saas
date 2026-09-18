import axios from 'axios';

export const apiSerahTerimaAset = axios.create({
  headers: { Accept: 'application/json' },
});
