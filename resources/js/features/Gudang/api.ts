export const ruteGudang = {
  index: '/gudang',
  detail: (id: string) => `/gudang/${id}`,
  lokasi: (id: string) => `/gudang/${id}/lokasi`,
  lokasiDetail: (lokasiId: string) => `/lokasi-gudang/${lokasiId}`,
};
