export const rutePenyedia = {
  index: '/penyedia',
  kategori: '/penyedia/kategori',
  kategoriDetail: (id: string) => `/penyedia/kategori/${id}`,
  kontakDetail: (id: string) => `/penyedia/kontak/${id}`,
  detail: (id: string) => `/penyedia/${id}`,
  kategori2: (id: string) => `/penyedia/${id}/kategori`,
  kategoriDetail2: (id1: string, id2: string) => `/penyedia/${id1}/kategori/${id2}`,
  kontak: (id: string) => `/penyedia/${id}/kontak`,
  penilaian: (id: string) => `/penyedia/${id}/penilaian`,
  riwayatPengadaan: (id: string) => `/penyedia/${id}/riwayat-pengadaan`,
  riwayatLayanan: (id: string) => `/penyedia/${id}/riwayat-layanan`,
};
