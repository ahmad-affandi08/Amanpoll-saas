import axios from 'axios';

export const apiKolomKustom = axios.create({
  headers: { Accept: 'application/json' },
});
