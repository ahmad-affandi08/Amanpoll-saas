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
