export const ruteKontrak = {
  index: '/kontrak',
  detail: (id: string) => `/kontrak/${id}`,
  batalkan: (id: string) => `/kontrak/${id}/batalkan`,
  aset: (id: string) => `/kontrak/${id}/aset`,
  asetDetail: (id: string, kontrakAsetId: string) => `/kontrak/${id}/aset/${kontrakAsetId}`,
  layanan: (id: string) => `/kontrak/${id}/layanan`,
  layananDetail: (id: string, layananId: string) => `/kontrak/${id}/layanan/${layananId}`,
  layananPemakaian: (id: string, layananId: string) => `/kontrak/${id}/layanan/${layananId}/pemakaian`,
  ekspor: '/kontrak/ekspor',
};
