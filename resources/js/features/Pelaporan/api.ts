export const rutePelaporan = {
  dasbor: '/',
  laporan: '/pelaporan/laporan',
  laporanDetail: (id: string) => `/pelaporan/laporan/${id}`,
  laporanEkspor: '/pelaporan/laporan/ekspor',
  ekspor: '/pelaporan/ekspor',
  eksporUnduh: (berkasId: string) => `/pelaporan/ekspor/${berkasId}`,
  dasborKustom: '/pelaporan/dasbor',
  dasborKustomEkspor: '/pelaporan/dasbor/ekspor',
  dasborKustomDetail: (id: string) => `/pelaporan/dasbor/${id}`,
};
