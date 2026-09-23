/**
 * Jumlah digit desimal yang dipakai sebuah mata uang di Amanpoll.
 *
 * Rupiah ditulis tanpa sen di seluruh aplikasi (ISO 4217 menyebut dua, tetapi
 * tidak ada yang menulis "Rp 1.500.000,00" di rumah sakit); mata uang lain dua.
 */
export function digitDesimalUang(mataUang = 'IDR'): number {
  return mataUang.toUpperCase() === 'IDR' ? 0 : 2;
}

/** Formatter tampilan uang saja. */
export function formatUang(nilai: number | string, mataUang = 'IDR'): string {
  const angka = typeof nilai === 'string' ? Number(nilai) : nilai;
  const digit = digitDesimalUang(mataUang);

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: mataUang,
    minimumFractionDigits: digit,
    maximumFractionDigits: digit,
  }).format(angka);
}

/** Lambang mata uang dalam penulisan Indonesia, mis. `Rp`, `US$`; kode mentah bila tidak dikenal. */
export function lambangUang(mataUang = 'IDR'): string {
  try {
    const bagian = new Intl.NumberFormat('id-ID', { style: 'currency', currency: mataUang }).formatToParts(0);

    return bagian.find((satu) => satu.type === 'currency')?.value ?? mataUang;
  } catch {
    return mataUang;
  }
}

/**
 * Nilai kanonik (`1500000`, `1500000.5`, `-250`) menjadi teks isian bergolongan
 * Indonesia (`1.500.000`, `1.500.000,5`, `-250`).
 *
 * Nilai kanonik adalah yang dikirim ke server: titik sebagai desimal, tanpa
 * pemisah ribuan. Selagi pengguna mengetik ia boleh berakhir dengan titik
 * (`1500000.`) supaya koma yang baru diketik tidak hilang.
 */
export function formatMasukanUang(nilai: string | number | null | undefined): string {
  if (nilai === null || nilai === undefined || nilai === '') {
    return '';
  }

  const teks = String(nilai);
  const negatif = teks.startsWith('-');
  const [bulat, desimal] = teks.replace('-', '').split('.');
  const bergolong = (bulat ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return (negatif ? '-' : '') + bergolong + (desimal !== undefined ? `,${desimal}` : '');
}

/** Digit desimal terbanyak yang diterima isian uang, apa pun mata uangnya. */
const DIGIT_DESIMAL_MASUKAN = 2;

/**
 * Teks isian (ketikan atau tempelan) menjadi nilai kanonik.
 *
 * Dalam penulisan Indonesia titik adalah pemisah ribuan dan koma desimal, jadi
 * titik dibuang dan koma menjadi titik desimal. Satu pengecualian, hanya untuk
 * tempelan dari sistem lain: `1500000.50` (satu titik, diikuti satu atau dua
 * digit di ujung, tanpa koma) dibaca sebagai desimal. Pada ketikan pengecualian
 * itu tidak berlaku, karena menghapus digit dari `15.200` meninggalkan `15.20`
 * yang rupanya sama. Nol di depan dibuang.
 *
 * Desimal diterima sampai dua digit untuk semua mata uang, termasuk rupiah.
 * Menolak koma pada rupiah terdengar lebih rapi, tetapi komanya lalu hilang dan
 * digit yang diketik sesudahnya menempel ke angka bulat: `15.200,50` menjadi
 * `1.520.050`, seratus kali lipat tanpa peringatan.
 */
export function uraiMasukanUang(teks: string, bolehNegatif = false, tempelan = false): string {
  let bersih = teks.trim();
  const negatif = bolehNegatif && bersih.startsWith('-');

  if (tempelan && !bersih.includes(',') && /^[^.]*\.\d{1,2}$/.test(bersih)) {
    bersih = bersih.replace('.', ',');
  }

  bersih = bersih.replace(/[^\d,]/g, '');
  const indeksKoma = bersih.indexOf(',');
  let bulat = indeksKoma === -1 ? bersih : bersih.slice(0, indeksKoma);
  const desimal =
    indeksKoma === -1
      ? null
      : bersih
          .slice(indeksKoma + 1)
          .replace(/,/g, '')
          .slice(0, DIGIT_DESIMAL_MASUKAN);

  bulat = bulat.replace(/^0+(?=\d)/, '');
  if (bulat === '' && desimal !== null) {
    bulat = '0';
  }

  const hasil = desimal === null ? bulat : `${bulat}.${desimal}`;

  if (hasil === '') {
    return negatif ? '-' : '';
  }

  return (negatif ? '-' : '') + hasil;
}
