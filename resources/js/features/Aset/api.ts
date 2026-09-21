export const ruteAset = {
  index: '/aset',
  garansiDetail: (id: string) => `/aset/garansi/${id}`,
  meterPembacaan: (id: string) => `/aset/meter/${id}/pembacaan`,
  relasiDetail: (id: string) => `/aset/relasi/${id}`,
  detail: (id: string) => `/aset/${id}`,
  garansi: (id: string) => `/aset/${id}/garansi`,
  meter: (id: string) => `/aset/${id}/meter`,
  nilai: (id: string) => `/aset/${id}/nilai`,
  nilaiPratinjau: (id: string) => `/aset/${id}/nilai/pratinjau`,
  penanggungJawab: (id: string) => `/aset/${id}/penanggung-jawab`,
  relasi: (id: string) => `/aset/${id}/relasi`,
  riwayatLokasi: (id: string) => `/aset/${id}/riwayat-lokasi`,
};
