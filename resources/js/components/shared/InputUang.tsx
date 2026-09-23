import * as React from 'react';
import { Input } from '@/components/ui/input';
import { digitDesimalUang, formatMasukanUang, lambangUang, uraiMasukanUang } from '@/lib/uang';
import { cn } from '@/lib/utils';

type Properti = Omit<React.ComponentProps<'input'>, 'value' | 'onChange' | 'type' | 'inputMode'> & {
  /** Nilai kanonik yang dikirim ke server (`1500000`, `1500000.5`), atau angka. */
  value: string | number | null | undefined;
  /** Menerima nilai kanonik; string kosong berarti dikosongkan. */
  onChange: (nilai: string) => void;
  /** Kode ISO 4217; menentukan lambang dan jumlah digit desimal. */
  mataUang?: string;
  /** Hanya untuk nominal yang memang boleh negatif, mis. penyesuaian anggaran. */
  bolehNegatif?: boolean;
};

/** Karakter yang bermakna bagi nilai: digit, koma desimal, dan tanda minus. */
const BERMAKNA = /[\d,-]/;

function jumlahBermakna(teks: string): number {
  let jumlah = 0;
  for (const huruf of teks) {
    if (BERMAKNA.test(huruf)) {
      jumlah++;
    }
  }

  return jumlah;
}

/** Posisi kursor di teks baru yang didahului sejumlah karakter bermakna yang sama. */
function posisiSetelah(teks: string, bermakna: number): number {
  if (bermakna <= 0) {
    return 0;
  }

  let hitung = 0;
  for (let indeks = 0; indeks < teks.length; indeks++) {
    if (BERMAKNA.test(teks[indeks])) {
      hitung++;
      if (hitung === bermakna) {
        return indeks + 1;
      }
    }
  }

  return teks.length;
}

/** Nilai dari server (`"1500000.00"`) dirapikan ke jumlah desimal mata uangnya. */
function rapikan(nilai: string | number | null | undefined, mataUang: string): string {
  if (nilai === null || nilai === undefined) {
    return '';
  }

  const teks = String(nilai);

  return digitDesimalUang(mataUang) === 0 ? teks.replace(/\.0*$/, '') : teks;
}

/**
 * Isian nominal uang yang ditulis seperti uang: `Rp 1.500.000`, bukan `1500000`.
 *
 * Pemisah ribuan muncul selagi mengetik dan lambang mata uang tampil di depan.
 * Nilai dari server dirapikan ke kebiasaan mata uangnya (rupiah tanpa `,00`),
 * dan sen tetap boleh diketik bila memang ada. Yang dikirim ke
 * `onChange` tetap nilai kanonik tanpa pemisah, jadi validasi `numeric` di
 * server tidak berubah. Kursor dijaga di antara digit yang sama walau titik
 * pemisah bertambah atau berkurang di depannya.
 */
export function InputUang({
  value,
  onChange,
  mataUang = 'IDR',
  bolehNegatif = false,
  className,
  ...props
}: Properti) {
  const ref = React.useRef<HTMLInputElement>(null);
  const kursorBerikutnya = React.useRef<number | null>(null);
  // Nilai yang baru saja dikirim komponen ini tidak dirapikan: `15200.` dan
  // `15200.0` adalah ketikan yang belum selesai, bukan `,00` dari server.
  const terakhirDikirim = React.useRef<string | null>(null);
  const lambang = lambangUang(mataUang);
  const kanonikSekarang = value === null || value === undefined ? '' : String(value);
  const tampil = formatMasukanUang(
    kanonikSekarang === terakhirDikirim.current ? kanonikSekarang : rapikan(value, mataUang),
  );

  // Tanpa daftar dependensi: kursor dipasang sesudah render mana pun yang
  // mengikuti ketikan, termasuk yang teks tampilnya kebetulan tidak berubah.
  React.useLayoutEffect(() => {
    const posisi = kursorBerikutnya.current;
    if (posisi !== null && ref.current && document.activeElement === ref.current) {
      ref.current.setSelectionRange(posisi, posisi);
    }
    kursorBerikutnya.current = null;
  });

  const ubah = (event: React.ChangeEvent<HTMLInputElement>) => {
    const mentah = event.target.value;
    const kursor = event.target.selectionStart ?? mentah.length;
    const tempelan = (event.nativeEvent as InputEvent).inputType === 'insertFromPaste';
    const kanonik = uraiMasukanUang(mentah, bolehNegatif, tempelan);
    const teksBaru = formatMasukanUang(kanonik);
    // Tempelan boleh memakai titik desimal yang lalu menjadi koma, jadi
    // hitungan karakternya tidak sebanding; kursor ditaruh di ujung saja.
    const posisi = tempelan
      ? teksBaru.length
      : posisiSetelah(teksBaru, jumlahBermakna(mentah.slice(0, kursor)));

    if (kanonik === kanonikSekarang) {
      // Ketikan yang ditolak (huruf, digit desimal berlebih) tidak mengubah
      // nilai, jadi tidak ada render ulang yang memasang kursor. Teksnya
      // dipulihkan di sini, sebelum React memulihkannya dengan kursor di ujung.
      event.target.value = tampil;
      event.target.setSelectionRange(posisi, posisi);

      return;
    }

    kursorBerikutnya.current = posisi;
    terakhirDikirim.current = kanonik;
    onChange(kanonik);
  };

  return (
    <div className="relative">
      <span
        aria-hidden="true"
        className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
      >
        {lambang}
      </span>
      <Input
        {...props}
        ref={ref}
        type="text"
        inputMode="decimal"
        autoComplete="off"
        value={tampil}
        onChange={ubah}
        className={cn('tabular-nums', className)}
        style={{ paddingLeft: `calc(${lambang.length}ch + 1.25rem)` }}
      />
    </div>
  );
}
