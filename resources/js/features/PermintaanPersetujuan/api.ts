export const rutePermintaanPersetujuan = {
  inbox: '/persetujuan/permintaan/inbox',
  milikSaya: '/persetujuan/permintaan/milik-saya',
  detail: (id: string) => `/persetujuan/permintaan/${id}`,
  detail2: (id1: string, id2: string) => `/persetujuan/permintaan/${id1}/${id2}`,
};
