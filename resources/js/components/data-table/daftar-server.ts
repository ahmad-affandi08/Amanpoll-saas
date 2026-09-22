import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import type { Paginasi } from '@/types/global';

/** Nilai filter daftar yang sedang berlaku, dibaca halaman dari query string. */
export interface FilterDaftar {
  cari?: string;
  urut?: string;
  arah?: 'asc' | 'desc';
  /** Filter faset: nama kolom berisi nilai terpilih dipisah koma. */
  [kolom: string]: string | undefined;
}

export interface DaftarServer {
  meta: Paginasi<unknown>['meta'];
  filter: FilterDaftar;
}

type Parameter = Record<string, string | number | undefined>;

/**
 * Apakah pengguna sedang mempersempit daftar.
 *
 * Urutan tidak dihitung: server selalu mengirim balik urutan yang berlaku, juga
 * saat itu urutan bawaan, sehingga keberadaannya tidak menandakan apa pun.
 */
export function adaPenyaringAktif(filter: FilterDaftar): boolean {
  return Object.keys(filter).some((kunci) => kunci !== 'urut' && kunci !== 'arah');
}

const JEDA_KETIK = 300;

/** Parameter kosong dibuang supaya URL tidak menumpuk sisa filter yang sudah dilepas. */
function kirim(param: Parameter): void {
  const bersih = Object.fromEntries(
    Object.entries(param).filter(([, nilai]) => nilai !== undefined && nilai !== ''),
  );

  router.get(window.location.pathname, bersih, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Menjaga filter daftar tetap ada di URL, bukan hanya di memori komponen.
 *
 * Halaman yang dipaginasi server hanya memegang satu halaman, jadi pencarian,
 * pengurutan, dan penyaringan harus berangkat ke server. Menaruhnya di query
 * string sekaligus membuat hasilnya dapat ditandai dan dibuka ulang, dan
 * membuat tombol kembali peramban berperilaku seperti yang diharapkan.
 *
 * Perubahan dikumpulkan dulu sebelum dikirim. Satu klik dapat mengubah beberapa
 * hal sekaligus -- tombol "Atur Ulang" melepas faset dan mengosongkan pencarian
 * dalam satu penanganan -- dan bila masing-masing berangkat sendiri, permintaan
 * yang belakangan berangkat dari filter lama dan membatalkan yang duluan.
 */
export function useDaftarServer(server: DaftarServer) {
  const [cari, setCariLokal] = useState(server.filter.cari ?? '');
  const tertunda = useRef<Parameter | null>(null);
  const pewaktu = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Filter di URL adalah sumber kebenarannya, kecuali saat ada ketikan yang belum berangkat.
  useEffect(() => {
    if (pewaktu.current === null) {
      setCariLokal(server.filter.cari ?? '');
    }
  }, [server.filter.cari]);

  useEffect(
    () => () => {
      if (pewaktu.current !== null) {
        clearTimeout(pewaktu.current);
      }
    },
    [],
  );

  const jadwalkan = (param: Parameter, jeda: number): void => {
    tertunda.current = { ...(tertunda.current ?? server.filter), ...param };

    if (pewaktu.current !== null) {
      clearTimeout(pewaktu.current);
    }

    pewaktu.current = setTimeout(() => {
      const isi = tertunda.current ?? {};
      tertunda.current = null;
      pewaktu.current = null;
      kirim(isi);
    }, jeda);
  };

  return {
    cari,

    setCari(nilai: string): void {
      setCariLokal(nilai);
      // Mengetik tidak boleh memicu satu permintaan per huruf.
      jadwalkan({ cari: nilai, page: undefined }, JEDA_KETIK);
    },

    /** Halaman dikembalikan ke satu karena hasilnya berubah isi, bukan hanya berpindah. */
    ubahFilter(perubahan: Record<string, string | undefined>): void {
      jadwalkan({ ...perubahan, page: undefined }, 0);
    },

    ubahUrutan(urut: string | undefined, arah: 'asc' | 'desc' | undefined): void {
      jadwalkan({ urut, arah, page: undefined }, 0);
    },

    ubahHalaman(halaman: number): void {
      jadwalkan({ page: halaman }, 0);
    },
  };
}
