import axios from 'axios';

export const apiKepatuhan = axios.create({
  headers: { Accept: 'application/json' },
});
