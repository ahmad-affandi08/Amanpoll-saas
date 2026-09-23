/**
 * Kelas badge mengikuti DESIGN.md 18: tint tipis + teks shade 700 agar teks
 * lolos WCAG AA (>= 4,5:1) di atas tint-nya; label selalu menyertai warna.
 */
export function statusKalibrasiBadge(status?: string): { label: string; className: string } {
  switch (status) {
    case 'Valid':
      return {
        label: 'Valid',
        className: 'border-sukses-200 bg-sukses-50 text-sukses-700',
      };
    case 'SegeraJatuhTempo':
      return {
        label: 'Segera Jatuh Tempo',
        className: 'border-safety-600/30 bg-safety-500/15 text-safety-700',
      };
    case 'Terlambat':
      return {
        label: 'Terlambat',
        className: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
      };
    case 'TidakAktif':
    default:
      return {
        label: 'Tidak Aktif',
        className: 'border-garis-300 bg-permukaan-100 text-grafit-700',
      };
  }
}

export function hasilKalibrasiBadge(hasil?: string | null): { label: string; className: string } {
  switch (hasil) {
    case 'Lolos':
      return {
        label: 'Lolos',
        className: 'border-sukses-200 bg-sukses-50 text-sukses-700',
      };
    case 'Gagal':
      return {
        label: 'Gagal',
        className: 'border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700',
      };
    case 'LolosDenganCatatan':
      return {
        label: 'Lolos dgn Catatan',
        className: 'border-safety-600/30 bg-safety-500/15 text-safety-700',
      };
    case 'Terjadwal':
      return {
        label: 'Terjadwal',
        className: 'border-info-600/25 bg-info-600/10 text-info-700',
      };
    case 'BelumDiuji':
    default:
      return {
        label: 'Belum Diuji',
        className: 'border-garis-300 bg-permukaan-100 text-grafit-700',
      };
  }
}
