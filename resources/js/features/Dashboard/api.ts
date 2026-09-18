import axios from 'axios';

export const apiDashboard = axios.create({
  headers: { Accept: 'application/json' },
});
