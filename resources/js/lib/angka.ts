/** Angka bergolongan tanpa lambang mata uang; untuk nominal ber-Rp pakai formatUang. */
export function formatAngka(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);
}

/** Angka ukur berdesimal (`3.000000` dari kolom DECIMAL) ditulis ringkas: `3`, `12,5`. */
export function formatAngkaUkur(nilai: number | string | null | undefined, kosong = '∞'): string {
  if (nilai === null || nilai === undefined || nilai === '') {
    return kosong;
  }

  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(nilai));
}
