import axios from 'axios';

export const apiAset = axios.create({
  headers: { Accept: 'application/json' },
});
