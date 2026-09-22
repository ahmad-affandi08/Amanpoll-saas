import axios from 'axios';

/** Klien HTTP bersama untuk request data di luar kunjungan Inertia. */
export const http = axios.create({
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});
