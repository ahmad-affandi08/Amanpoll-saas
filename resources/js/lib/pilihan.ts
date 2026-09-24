import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';

/** Nilai penanda "tidak memilih apa pun" pada Select, karena Radix melarang item bernilai kosong. */
export const TANPA_PILIHAN = '__tanpa__';

/**
 * Mengubah daftar entitas menjadi opsi Combobox.
 *
 * Kode ikut masuk sebagai keterangan bila ada, karena orang di lapangan lebih
 * sering hafal kode asetnya daripada namanya.
 */
export function opsiDari<T extends { Id: string }>(
  daftar: T[],
  label: (satu: T) => string,
  keterangan?: (satu: T) => string | null | undefined,
): { nilai: string; label: string; keterangan?: string }[] {
  return daftar.map((satu) => {
    const tambahan = keterangan?.(satu);

    return {
      nilai: satu.Id,
      label: label(satu),
      ...(tambahan ? { keterangan: tambahan } : {}),
    };
  });
}

/** Opsi "tidak diisi" di awal daftar, menggantikan SelectItem bernilai TANPA_PILIHAN. */
export function opsiKosong(label = 'Tidak diisi'): { nilai: string; label: string } {
  return { nilai: TANPA_PILIHAN, label };
}

/** Nilai penyaring "Belum ada unit pengelola"; pasangan `OpsiUnitPengelola::TANPA` di server. */
export const TANPA_UNIT_PENGELOLA = 'tanpa';

/**
 * Opsi Combobox unit pengelola (PRD 8.21): nama sebagai label, kode sebagai keterangan.
 *
 * `kosong` menambahkan opsi TANPA_PILIHAN di awal untuk isian yang boleh dikosongkan;
 * ubah kembali ke `null` sebelum dikirim.
 */
export function opsiUnitPengelola(
  daftar: UnitPengelolaRingkas[],
  kosong: string | false = 'Tanpa unit pengelola',
): { nilai: string; label: string; keterangan?: string }[] {
  const opsi = opsiDari(
    daftar,
    (unit) => unit.Nama,
    (unit) => unit.Kode,
  );

  return kosong === false ? opsi : [opsiKosong(kosong), ...opsi];
}
