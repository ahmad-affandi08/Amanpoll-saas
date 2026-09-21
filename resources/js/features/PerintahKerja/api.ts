export const rutePerintahKerja = {
  index: '/pemeliharaan/perintah-kerja',
  detail: (id: string) => `/pemeliharaan/perintah-kerja/${id}`,
  analisisKegagalan: (id: string) => `/pemeliharaan/perintah-kerja/${id}/analisis-kegagalan`,
  biaya: (id: string) => `/pemeliharaan/perintah-kerja/${id}/biaya`,
  penugasan: (id: string) => `/pemeliharaan/perintah-kerja/${id}/penugasan`,
  penugasanRespons: (id1: string, id2: string) =>
    `/pemeliharaan/perintah-kerja/${id1}/penugasan/${id2}/respons`,
  reservasiSukuCadang: (id: string) => `/pemeliharaan/perintah-kerja/${id}/reservasi-suku-cadang`,
  status: (id: string) => `/pemeliharaan/perintah-kerja/${id}/status`,
  sukuCadang: (id: string) => `/pemeliharaan/perintah-kerja/${id}/suku-cadang`,
  waktuHenti: (id: string) => `/pemeliharaan/perintah-kerja/${id}/waktu-henti`,
  waktuKerja: (id: string) => `/pemeliharaan/perintah-kerja/${id}/waktu-kerja`,
};
