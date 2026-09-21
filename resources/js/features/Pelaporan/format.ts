import type { MetrikKpi, SatuanKpi } from './types';

/**
 * Pemformatan nilai KPI.
 *
 * Nilai besar diringkas (1,2 jt) pada kartu angka supaya tidak memaksa kartu
 * melebar, sementara tabel dan tooltip memakai angka penuh — ringkasan hanya
 * boleh dipakai di tempat yang angkanya juga tersedia lengkap di tempat lain.
 */

const LOKAL = 'id-ID';

export function formatNilai(nilai: number, satuan: SatuanKpi, desimal: number): string {
  switch (satuan) {
    case 'Persen':
      return `${nilai.toLocaleString(LOKAL, { maximumFractionDigits: desimal })}%`;
    case 'Uang':
      return nilai.toLocaleString(LOKAL, {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
      });
    case 'Menit':
      return `${nilai.toLocaleString(LOKAL, { maximumFractionDigits: 0 })} mnt`;
    case 'Jam':
      return `${nilai.toLocaleString(LOKAL, { maximumFractionDigits: desimal })} jam`;
    case 'Hari':
      return `${nilai.toLocaleString(LOKAL, { maximumFractionDigits: desimal })} hari`;
    default:
      return nilai.toLocaleString(LOKAL, { maximumFractionDigits: desimal });
  }
}

/** Versi ringkas untuk kartu angka; tetap memakai figur proporsional. */
export function formatRingkas(nilai: number, satuan: SatuanKpi, desimal: number): string {
  if (satuan === 'Uang' && Math.abs(nilai) >= 1_000_000) {
    const juta = nilai / 1_000_000;
    const milyar = nilai / 1_000_000_000;
    return Math.abs(nilai) >= 1_000_000_000
      ? `Rp ${milyar.toLocaleString(LOKAL, { maximumFractionDigits: 1 })} M`
      : `Rp ${juta.toLocaleString(LOKAL, { maximumFractionDigits: 1 })} jt`;
  }

  return formatNilai(nilai, satuan, desimal);
}

export function formatNilaiKpi(kpi: MetrikKpi, ringkas = false): string {
  return ringkas
    ? formatRingkas(kpi.Nilai, kpi.Satuan, kpi.Desimal)
    : formatNilai(kpi.Nilai, kpi.Satuan, kpi.Desimal);
}

/**
 * Label sumbu untuk deret waktu. Kunci berupa `YYYY-MM-DD` atau `YYYY-MM`;
 * keduanya diringkas supaya sumbu tidak penuh.
 */
export function labelPeriode(label: string): string {
  const bagian = label.split('-');
  if (bagian.length === 3) {
    return `${bagian[2]}/${bagian[1]}`;
  }
  if (bagian.length === 2) {
    const bulan = new Date(Number(bagian[0]), Number(bagian[1]) - 1, 1);
    return bulan.toLocaleDateString(LOKAL, { month: 'short', year: '2-digit' });
  }
  return label;
}

export function formatUkuranByte(byte: number | null): string {
  if (byte === null) return '—';
  if (byte < 1024) return `${byte} B`;
  if (byte < 1024 * 1024) return `${(byte / 1024).toFixed(1)} kB`;
  return `${(byte / 1024 / 1024).toFixed(1)} MB`;
}

export function waktuLokal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString(LOKAL) : '—';
}
