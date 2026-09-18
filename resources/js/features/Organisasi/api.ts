import axios from 'axios';

export const apiOrganisasi = axios.create({
  headers: { Accept: 'application/json' },
});
