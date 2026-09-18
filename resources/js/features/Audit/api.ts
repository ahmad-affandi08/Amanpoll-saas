import axios from 'axios';

export const apiAudit = axios.create({
  headers: { Accept: 'application/json' },
});
