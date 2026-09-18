import axios from 'axios';

export const apiUnitOrganisasi = axios.create({
  headers: { Accept: 'application/json' },
});
