import axios from 'axios';

export const apiDaftarPeriksa = axios.create({
  headers: { Accept: 'application/json' },
});
