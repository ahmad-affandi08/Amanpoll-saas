/* Pemetaan status ke kelas badge sesuai DESIGN.md 18: berjalan = Teknisi, terjadwal = Info, perhatian = Safety. Teks shade 700 lolos AA di atas tint. */
export const statusPelaksanaanBadge: Record<string, { label: string; kelas: string }> = {
  Draft: { label: 'Draf', kelas: 'border-garis-300 bg-permukaan-100 text-grafit-700' },
  SedangDikerjakan: {
    label: 'Sedang Dikerjakan',
    kelas: 'border-teknisi-200 bg-teknisi-50 text-teknisi-700',
  },
  Selesai: { label: 'Selesai', kelas: 'border-sukses-200 bg-sukses-50 text-sukses-700' },
  Dibatalkan: { label: 'Dibatalkan', kelas: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700' },
};

export const statusInspeksiBadge: Record<string, { label: string; kelas: string }> = {
  Terjadwal: { label: 'Terjadwal', kelas: 'border-info-600/25 bg-info-600/10 text-info-700' },
  SedangDikerjakan: {
    label: 'Sedang Berjalan',
    kelas: 'border-teknisi-200 bg-teknisi-50 text-teknisi-700',
  },
  Selesai: { label: 'Selesai', kelas: 'border-sukses-200 bg-sukses-50 text-sukses-700' },
  Dibatalkan: { label: 'Dibatalkan', kelas: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700' },
};

export const hasilInspeksiBadge: Record<string, { label: string; kelas: string }> = {
  Lolos: { label: 'Lolos', kelas: 'border-sukses-200 bg-sukses-50 text-sukses-700' },
  PerluPerhatian: {
    label: 'Perlu Perhatian',
    kelas: 'border-safety-600/30 bg-safety-500/15 text-safety-700',
  },
  Gagal: { label: 'Gagal / Rusak', kelas: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700' },
};
