const AKAR = '/notifikasi/email-whatsapp';

/** Rute halaman Email & WhatsApp milik organisasi (PRD 8.23). */
export const rutePengirimNotifikasi = {
  index: AKAR,
  simpan: (kategori: string, kode: string) => `${AKAR}/${kategori}/${kode}`,
  hapus: (kategori: string, kode: string) => `${AKAR}/${kategori}/${kode}`,
  uji: (kategori: string, kode: string) => `${AKAR}/${kategori}/${kode}/uji`,
  kirimUji: (kategori: string, kode: string) => `${AKAR}/${kategori}/${kode}/kirim-uji`,
  profilOrganisasi: '/platform/organisasi',
  profilSaya: '/platform/profil',
  langganan: '/langganan',
};
