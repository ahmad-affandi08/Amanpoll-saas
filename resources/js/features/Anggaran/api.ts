export const ruteAnggaran = {
  index: '/perencanaan-pengadaan/anggaran',
  detail: (id: string) => `/perencanaan-pengadaan/anggaran/${id}`,
  pos: (id: string) => `/perencanaan-pengadaan/anggaran/${id}/pos`,
  transaksi: (id: string) => `/perencanaan-pengadaan/pos-anggaran/${id}/transaksi`,
};
