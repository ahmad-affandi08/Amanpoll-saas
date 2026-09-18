import axios from 'axios';

export const apiGudang = axios.create({
  headers: { Accept: 'application/json' },
});
