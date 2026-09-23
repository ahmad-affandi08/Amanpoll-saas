export const rutePesananPembelian = {
  index: '/perencanaan-pengadaan/pesanan-pembelian',
  detail: (id: string) => `/perencanaan-pengadaan/pesanan-pembelian/${id}`,
  buatDariPenawaran: (penawaranId: string) =>
    `/perencanaan-pengadaan/penawaran/${penawaranId}/pesanan-pembelian`,
  ajukan: (id: string) => `/perencanaan-pengadaan/pesanan-pembelian/${id}/ajukan`,
  kirim: (id: string) => `/perencanaan-pengadaan/pesanan-pembelian/${id}/kirim`,
  penerimaan: (id: string) => `/perencanaan-pengadaan/pesanan-pembelian/${id}/penerimaan`,
  tagihan: (id: string) => `/perencanaan-pengadaan/pesanan-pembelian/${id}/tagihan`,
  ekspor: '/perencanaan-pengadaan/pesanan-pembelian/ekspor',
};
