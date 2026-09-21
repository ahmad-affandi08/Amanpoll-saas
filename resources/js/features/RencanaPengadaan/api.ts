export const ruteRencanaPengadaan = {
  index: '/perencanaan-pengadaan/rencana-pengadaan',
  detail: (id: string) => `/perencanaan-pengadaan/rencana-pengadaan/${id}`,
  detail2: (id: string) => `/perencanaan-pengadaan/rencana-pengadaan/${id}/detail`,
  detailDetail: (id1: string, id2: string) => `/perencanaan-pengadaan/rencana-pengadaan/${id1}/detail/${id2}`,
  finalisasi: (id: string) => `/perencanaan-pengadaan/rencana-pengadaan/${id}/finalisasi`,
};
