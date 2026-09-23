export const rutePeranIzin = {
  index: '/platform/peran',
  bawaan: '/platform/peran/bawaan',
  daftarIzin: '/platform/izin',
  detail: (id: string) => `/platform/peran/${id}`,
  izin: (id: string) => `/platform/peran/${id}/izin`,
};
