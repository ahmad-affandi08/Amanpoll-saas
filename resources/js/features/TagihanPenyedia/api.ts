import axios from 'axios';

export const apiTagihanPenyedia = axios.create({
  headers: { Accept: 'application/json' },
});
