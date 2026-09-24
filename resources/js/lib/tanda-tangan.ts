import { http } from '@/lib/http';

/**
 * Tanda tangan tersimpan di profil pengguna yang sedang masuk (PRD 8.22). Dipakai profil
 * dasbor, Akun Mode Lapangan, dan layar konfirmasi penerima ("gambar sekali lalu tersimpan").
 */
export const ruteTandaTangan = {
  /** Gambar tanda tangan milik sendiri; `versi` memaksa peramban memuat ulang setelah diganti. */
  lihat: (versi?: string | number) => (versi ? `/profil/tanda-tangan?v=${versi}` : '/profil/tanda-tangan'),
  simpan: '/profil/tanda-tangan',
  hapus: '/profil/tanda-tangan',
};

export async function simpanTandaTanganProfil(gambar: Blob): Promise<void> {
  const data = new FormData();
  data.append('TandaTangan', gambar, 'tanda-tangan.png');
  await http.post(ruteTandaTangan.simpan, data);
}

export async function hapusTandaTanganProfil(): Promise<void> {
  await http.delete(ruteTandaTangan.hapus);
}
