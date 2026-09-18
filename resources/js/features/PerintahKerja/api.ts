import axios from 'axios';

export const apiPerintahKerja = axios.create({
  headers: { Accept: 'application/json' },
});
