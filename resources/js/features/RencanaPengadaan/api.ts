import axios from 'axios';

export const apiRencanaPengadaan = axios.create({
  headers: { Accept: 'application/json' },
});
