import axios from 'axios';

export const apiPenyedia = axios.create({
  headers: { Accept: 'application/json' },
});
