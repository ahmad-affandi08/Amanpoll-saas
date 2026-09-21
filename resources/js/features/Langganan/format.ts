const UANG = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const JUMLAH = new Intl.NumberFormat('id-ID');

export function rupiah(nilai: number, mataUang = 'IDR'): string {
  return `${mataUang} ${UANG.format(nilai)}`;
}

export function angka(nilai: number): string {
  return JUMLAH.format(nilai);
}

export function tanggal(nilai: string | null): string {
  if (!nilai) return '—';

  const tanggalTerurai = new Date(nilai);
  if (Number.isNaN(tanggalTerurai.getTime())) return nilai;

  return tanggalTerurai.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

/**
 * Batas null berarti tanpa batas. Dibedakan dari nol, yang berarti tidak boleh
 * sama sekali — perbedaan yang hilang kalau keduanya ditulis sebagai "0".
 */
export function labelBatas(batas: number | null, satuan: string | null): string {
  if (batas === null) return 'Tanpa batas';

  return `${JUMLAH.format(batas)}${satuan ? ` ${satuan}` : ''}`;
}

export function persenPemakaian(terpakai: number, batas: number | null): number | null {
  if (batas === null || batas <= 0) return null;

  return Math.min(100, Math.round((terpakai / batas) * 100));
}
