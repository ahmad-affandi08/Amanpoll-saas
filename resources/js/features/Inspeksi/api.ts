export const ruteInspeksi = {
  index: '/preventif-inspeksi/inspeksi',
  templat: '/preventif-inspeksi/templat-inspeksi',
  templatDetail: (id: string) => `/preventif-inspeksi/templat-inspeksi/${id}`,
  detail: (id: string) => `/preventif-inspeksi/inspeksi/${id}`,
  buatPerintahKerja: (id: string) => `/preventif-inspeksi/inspeksi/${id}/buat-perintah-kerja`,
  laksanakan: (id: string) => `/preventif-inspeksi/inspeksi/${id}/laksanakan`,
  ekspor: '/preventif-inspeksi/inspeksi/ekspor',
};
