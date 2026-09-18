import axios from 'axios';

export const apiBerkas = axios.create({
  headers: { Accept: 'application/json' },
});
