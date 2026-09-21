export const ruteAnggaran = {
  index: '/perencanaan-pengadaan/anggaran',
  detail: (id: string) => `/perencanaan-pengadaan/anggaran/${id}`,
  ajukan: (id: string) => `/perencanaan-pengadaan/anggaran/${id}/ajukan`,
  pos: (id: string) => `/perencanaan-pengadaan/anggaran/${id}/pos`,
  posDetail: (id: string) => `/perencanaan-pengadaan/pos-anggaran/${id}`,
  posTransaksi: (id: string) => `/perencanaan-pengadaan/pos-anggaran/${id}/transaksi`,
};
