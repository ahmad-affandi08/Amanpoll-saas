export const ruteIntegrasi = {
  index: '/integrasi',
  detail: (id: string) => `/integrasi/${id}`,
  status: (id: string) => `/integrasi/${id}/status`,
  ujiKoneksi: (id: string) => `/integrasi/${id}/uji-koneksi`,
  sinkronkan: (id: string) => `/integrasi/${id}/sinkronisasi`,
  pemetaan: (id: string) => `/integrasi/${id}/pemetaan`,
  pemetaanKonflik: (id: string, pemetaanId: string) => `/integrasi/${id}/pemetaan/${pemetaanId}/konflik`,
  pemetaanDetail: (id: string, pemetaanId: string) => `/integrasi/${id}/pemetaan/${pemetaanId}`,
  panggilanBalik: '/integrasi/panggilan-balik',
  panggilanBalikDetail: (id: string) => `/integrasi/panggilan-balik/${id}`,
  panggilanBalikPengiriman: (id: string) => `/integrasi/panggilan-balik/${id}/pengiriman`,
};
