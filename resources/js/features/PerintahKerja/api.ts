export const rutePerintahKerja = {
  index: '/pemeliharaan/perintah-kerja',
  ekspor: '/pemeliharaan/perintah-kerja/ekspor',
  detail: (id: string) => `/pemeliharaan/perintah-kerja/${id}`,
  analisisKegagalan: (id: string) => `/pemeliharaan/perintah-kerja/${id}/analisis-kegagalan`,
  biaya: (id: string) => `/pemeliharaan/perintah-kerja/${id}/biaya`,
  penugasan: (id: string) => `/pemeliharaan/perintah-kerja/${id}/penugasan`,
  penugasanRespons: (id1: string, id2: string) =>
    `/pemeliharaan/perintah-kerja/${id1}/penugasan/${id2}/respons`,
  reservasiSukuCadang: (id: string) => `/pemeliharaan/perintah-kerja/${id}/reservasi-suku-cadang`,
  status: (id: string) => `/pemeliharaan/perintah-kerja/${id}/status`,
  sukuCadang: (id: string) => `/pemeliharaan/perintah-kerja/${id}/suku-cadang`,
  unitPengelola: (id: string) => `/pemeliharaan/perintah-kerja/${id}/unit-pengelola`,
  waktuHenti: (id: string) => `/pemeliharaan/perintah-kerja/${id}/waktu-henti`,
  waktuKerja: (id: string) => `/pemeliharaan/perintah-kerja/${id}/waktu-kerja`,
};
