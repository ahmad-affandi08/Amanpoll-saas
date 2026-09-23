export const statusPelaksanaanBadge: Record<string, { label: string; kelas: string }> = {
  Draft: { label: 'Draf', kelas: 'bg-garis-200 text-grafit-700 border-garis-300' },
  SedangDikerjakan: {
    label: 'Sedang Dikerjakan',
    kelas: 'bg-teknisi-50 text-teknisi-700 border-teknisi-200',
  },
  Selesai: { label: 'Selesai', kelas: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  Dibatalkan: { label: 'Dibatalkan', kelas: 'bg-rose-50 text-rose-700 border-rose-200' },
};

export const statusInspeksiBadge: Record<string, { label: string; kelas: string }> = {
  Terjadwal: { label: 'Terjadwal', kelas: 'bg-sky-50 text-sky-700 border-sky-200' },
  SedangDikerjakan: { label: 'Sedang Berjalan', kelas: 'bg-amber-50 text-amber-700 border-amber-200' },
  Selesai: { label: 'Selesai', kelas: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  Dibatalkan: { label: 'Dibatalkan', kelas: 'bg-rose-50 text-rose-700 border-rose-200' },
};

export const hasilInspeksiBadge: Record<string, { label: string; kelas: string }> = {
  Lolos: { label: 'Lolos', kelas: 'bg-emerald-50 text-emerald-700 border-emerald-300' },
  PerluPerhatian: { label: 'Perlu Perhatian', kelas: 'bg-amber-50 text-amber-700 border-amber-300' },
  Gagal: { label: 'Gagal / Rusak', kelas: 'bg-rose-50 text-rose-700 border-rose-300' },
};
