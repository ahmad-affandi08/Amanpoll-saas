export const ruteOffline = {
  paket: '/offline/paket',
  ringkasan: '/offline/ringkasan',
  perangkatLepas: '/offline/perangkat/lepas',
  antrian: '/offline/antrian',
  antrianStatus: '/offline/antrian/status',
  antrianKonflik: (id: string) => `/offline/antrian/${id}/konflik`,
};
