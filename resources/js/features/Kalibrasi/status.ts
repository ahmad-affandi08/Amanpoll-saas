export function statusKalibrasiBadge(status?: string): { label: string; className: string } {
  switch (status) {
    case 'Valid':
      return {
        label: 'Valid',
        className: 'bg-sukses-50 text-sukses-600 border-sukses-200',
      };
    case 'SegeraJatuhTempo':
      return {
        label: 'Segera Jatuh Tempo',
        className: 'bg-amber-50 text-safety-600 border-amber-200',
      };
    case 'Terlambat':
      return {
        label: 'Terlambat',
        className: 'bg-rose-50 text-bahaya-600 border-rose-200',
      };
    case 'TidakAktif':
    default:
      return {
        label: 'Tidak Aktif',
        className: 'bg-permukaan-100 text-grafit-500 border-garis-300',
      };
  }
}

export function hasilKalibrasiBadge(hasil?: string | null): { label: string; className: string } {
  switch (hasil) {
    case 'Lolos':
      return {
        label: 'Lolos',
        className: 'bg-sukses-50 text-sukses-600 border-sukses-200',
      };
    case 'Gagal':
      return {
        label: 'Gagal',
        className: 'bg-rose-50 text-bahaya-600 border-rose-200',
      };
    case 'LolosDenganCatatan':
      return {
        label: 'Lolos dgn Catatan',
        className: 'bg-amber-50 text-safety-600 border-amber-200',
      };
    case 'Terjadwal':
      return {
        label: 'Terjadwal',
        className: 'bg-blue-50 text-info-600 border-blue-200',
      };
    case 'BelumDiuji':
    default:
      return {
        label: 'Belum Diuji',
        className: 'bg-permukaan-100 text-grafit-500 border-garis-300',
      };
  }
}
