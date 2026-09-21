export const ruteAlurPersetujuan = {
  index: '/persetujuan/alur',
  detail: (id: string) => `/persetujuan/alur/${id}`,
  aktifkan: (id: string) => `/persetujuan/alur/${id}/aktifkan`,
  nonaktifkan: (id: string) => `/persetujuan/alur/${id}/nonaktifkan`,
  tahap: (id: string) => `/persetujuan/alur/${id}/tahap`,
};
