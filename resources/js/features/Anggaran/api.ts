import axios from 'axios';

export const apiAnggaran = axios.create({
  headers: { Accept: 'application/json' },
});
