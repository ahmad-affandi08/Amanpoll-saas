export const ruteSertifikasi = {
  index: '/kepatuhan/sertifikasi',
  ekspor: '/kepatuhan/sertifikasi/ekspor',
  simpan: '/kepatuhan/sertifikasi',
  detail: (id: string) => `/kepatuhan/sertifikasi/${id}`,
  cabut: (id: string) => `/kepatuhan/sertifikasi/${id}/cabut`,
};
