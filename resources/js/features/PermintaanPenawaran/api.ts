export const rutePermintaanPenawaran = {
  index: '/perencanaan-pengadaan/permintaan-penawaran',
  detail: (id: string) => `/perencanaan-pengadaan/permintaan-penawaran/${id}`,
  buka: (id: string) => `/perencanaan-pengadaan/permintaan-penawaran/${id}/buka`,
  penawaran: (id: string) => `/perencanaan-pengadaan/permintaan-penawaran/${id}/penawaran`,
  pilihPenawaran: (id: string, penawaranId: string) =>
    `/perencanaan-pengadaan/permintaan-penawaran/${id}/penawaran/${penawaranId}/pilih`,
  ekspor: '/perencanaan-pengadaan/permintaan-penawaran/ekspor',
};
