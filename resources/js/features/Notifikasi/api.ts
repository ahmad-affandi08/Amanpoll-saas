export const ruteNotifikasi = {
  ringkasan: '/notifikasi/ringkasan',
  bacaSemua: '/notifikasi/baca-semua',
  baca: (id: string) => `/notifikasi/${id}/baca`,
  preferensi: '/notifikasi/preferensi',
  preferensiData: '/notifikasi/preferensi/data',
  templat: '/notifikasi/templat',
  templatDetail: (id: string) => `/notifikasi/templat/${id}`,
};
