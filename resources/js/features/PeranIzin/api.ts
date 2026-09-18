import axios from 'axios';

export const apiPeranIzin = axios.create({
  headers: { Accept: 'application/json' },
});
