export const ruteUsulanAset = {
  index: '/perencanaan-pengadaan/usulan-aset',
  detail: (id: string) => `/perencanaan-pengadaan/usulan-aset/${id}`,
  ajukanPersetujuan: (id: string) => `/perencanaan-pengadaan/usulan-aset/${id}/ajukan-persetujuan`,
  penilaian: (id: string) => `/perencanaan-pengadaan/usulan-aset/${id}/penilaian`,
  submit: (id: string) => `/perencanaan-pengadaan/usulan-aset/${id}/submit`,
};
