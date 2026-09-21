export const ruteInspeksi = {
  index: '/preventif-inspeksi/inspeksi',
  detail: (id: string) => `/preventif-inspeksi/inspeksi/${id}`,
  buatPerintahKerja: (id: string) => `/preventif-inspeksi/inspeksi/${id}/buat-perintah-kerja`,
  laksanakan: (id: string) => `/preventif-inspeksi/inspeksi/${id}/laksanakan`,
};
