export const ruteTagihanPenyedia = {
  index: '/perencanaan-pengadaan/tagihan-penyedia',
  detail: (id: string) => `/perencanaan-pengadaan/tagihan-penyedia/${id}`,
  bayar: (id: string) => `/perencanaan-pengadaan/tagihan-penyedia/${id}/pembayaran`,
  simpanDariPesanan: (pesananId: string) => `/perencanaan-pengadaan/pesanan-pembelian/${pesananId}/tagihan`,
  ekspor: '/perencanaan-pengadaan/tagihan-penyedia/ekspor',
};
