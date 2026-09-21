import axios from 'axios';

/**
 * Klien HTTP bersama untuk request data di luar kunjungan Inertia (mis. panel
 * dan tab yang memuat isinya sendiri). Navigasi dan mutasi tetap memakai
 * `router`/`useForm` Inertia supaya session, flash, dan CSRF ditangani framework.
 */
export const http = axios.create({
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});
