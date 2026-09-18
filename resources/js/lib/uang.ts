/**
 * Formatter tampilan uang saja. Backend (App\Shared\Domain\ValueObjects\Uang)
 * tetap menjadi sumber kebenaran nilai dan kalkulasi.
 */
export function formatUang(nilai: number | string, mataUang = 'IDR'): string {
  const angka = typeof nilai === 'string' ? Number(nilai) : nilai;

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: mataUang,
    minimumFractionDigits: mataUang === 'IDR' ? 0 : 2,
    maximumFractionDigits: mataUang === 'IDR' ? 0 : 2,
  }).format(angka);
}
