export const rutePermintaanPembelian = {
  index: '/perencanaan-pengadaan/permintaan-pembelian',
  detail: (id: string) => `/perencanaan-pengadaan/permintaan-pembelian/${id}`,
  detailItem: (id: string) => `/perencanaan-pengadaan/permintaan-pembelian/${id}/detail`,
  hapusItem: (id: string, detailId: string) =>
    `/perencanaan-pengadaan/permintaan-pembelian/${id}/detail/${detailId}`,
  submit: (id: string) => `/perencanaan-pengadaan/permintaan-pembelian/${id}/submit`,
  ekspor: '/perencanaan-pengadaan/permintaan-pembelian/ekspor',
};
