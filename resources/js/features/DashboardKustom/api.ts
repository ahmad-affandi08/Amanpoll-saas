import axios from 'axios';

export const apiDashboardKustom = axios.create({
  headers: { Accept: 'application/json' },
});
