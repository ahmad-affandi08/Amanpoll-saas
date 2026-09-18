import axios from 'axios';

export const apiPenghapusanAset = axios.create({
  headers: { Accept: 'application/json' },
});
