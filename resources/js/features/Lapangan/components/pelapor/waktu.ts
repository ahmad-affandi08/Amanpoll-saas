import { jamPendek, kelompokHari, tanggalPendek } from '@/features/Lapangan/waktu';

/** "09.41" untuk hari ini, "kemarin 16.05", selain itu "23 Sep, 16.05". */
export function waktuSingkat(nilai: string | null | undefined): string {
  if (!nilai) return '—';
  const hari = kelompokHari(nilai);
  if (hari === 'Hari ini') return jamPendek(nilai);
  if (hari === 'Kemarin') return `kemarin ${jamPendek(nilai)}`;
  return `${tanggalPendek(nilai)}, ${jamPendek(nilai)}`;
}

/** "Hari ini, 09.41" / "Kemarin, 16.05" / "23 Sep, 16.05". */
export function waktuLengkap(nilai: string | Date | null | undefined): string {
  if (!nilai) return '—';
  return `${kelompokHari(nilai)}, ${jamPendek(nilai)}`;
}

/** Label kecil di bawah jam rute: "Dilaporkan" hari ini, "Dilapor, 23 Sep" bila hari lain. */
export function labelHari(label: string, nilai: string | null | undefined, labelSingkat = label): string {
  if (!nilai || kelompokHari(nilai) === 'Hari ini') return label;
  return `${labelSingkat}, ${tanggalPendek(nilai)}`;
}
