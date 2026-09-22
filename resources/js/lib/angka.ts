/** Angka bergolongan tanpa lambang mata uang; untuk nominal ber-Rp pakai formatUang. */
export function formatAngka(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);
}
