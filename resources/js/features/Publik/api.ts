/**
 * Rute situs publik.
 *
 * Seluruhnya relatif terhadap akar host publik sendiri. Halaman pemasaran
 * dilayani penampung `/{jalur}`, jadi yang disebut di sini hanya endpoint yang
 * benar-benar dituju dari kode (MARKETING.md 1).
 */
export const rutePublik = {
  beranda: '/',
  cta: '/cta',
  formulir: (kode: string) => `/formulir/${kode}`,
  toolsKalkulator: '/tools/kalkulator',
  toolsQr: '/tools/qr',
};
