import axios from 'axios';

export const apiTag = axios.create({
  headers: { Accept: 'application/json' },
});
