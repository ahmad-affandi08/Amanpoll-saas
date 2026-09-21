export const rutePelaporan = {
  dasbor: '/',
  laporan: '/pelaporan/laporan',
  laporanDetail: (id: string) => `/pelaporan/laporan/${id}`,
  ekspor: '/pelaporan/ekspor',
  eksporUnduh: (berkasId: string) => `/pelaporan/ekspor/${berkasId}`,
  dasborKustom: '/pelaporan/dasbor',
  dasborKustomDetail: (id: string) => `/pelaporan/dasbor/${id}`,
};
