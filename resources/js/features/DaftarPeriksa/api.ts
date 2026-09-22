export const ruteDaftarPeriksa = {
  index: '/preventif-inspeksi/templat-daftar-periksa',
  detail: (id: string) => `/preventif-inspeksi/templat-daftar-periksa/${id}`,
  butir: (id: string) => `/preventif-inspeksi/templat-daftar-periksa/${id}/butir`,
  butirDetail: (id1: string, id2: string) => `/preventif-inspeksi/templat-daftar-periksa/${id1}/butir/${id2}`,
  versiBaru: (id: string) => `/preventif-inspeksi/templat-daftar-periksa/${id}/versi-baru`,

  pelaksanaan: '/preventif-inspeksi/pelaksanaan-daftar-periksa',
  pelaksanaanDetail: (id: string) => `/preventif-inspeksi/pelaksanaan-daftar-periksa/${id}`,
};
