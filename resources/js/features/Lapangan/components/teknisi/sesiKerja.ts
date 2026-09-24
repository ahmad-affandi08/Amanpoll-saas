import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { http } from '@/lib/http';
import {
  ambilDraf,
  daftarKunciDraf,
  hapusDraf,
  penyimpananTersedia,
  simpanDraf,
  type KonteksOffline,
} from '@/lib/penyimpanan-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import type { JawabanChecklistTeknisi, PropsLapangan } from '@/features/Lapangan/types';

/**
 * Draf pengerjaan satu tiket di perangkat (PRD 8.17: draft catatan dan foto).
 *
 * Disimpan di IndexedDB milik konteks organisasi + pengguna (`lib/penyimpanan-offline`),
 * jadi ikut terhapus saat logout, dan tetap ada bila aplikasi ditutup di tengah pekerjaan.
 */
export interface SesiKerja {
  /** Mulai sesi waktu kerja yang sedang berjalan; `null` saat dijeda. */
  MulaiPada: string | null;
  /** Mulai pertama kali di perangkat ini, untuk rute "Mulai → Selesai". */
  MulaiPertama?: string | null;
  Diagnosis?: {
    KodeMasalahId: string | null;
    AkarMasalah: string;
    TindakanKorektif: string;
    /** Sudah diterima endpoint analisis kegagalan. */
    Tersimpan: boolean;
    /** Sudah diantrikan sebagai catatan lapangan saat offline. */
    Tercatat?: boolean;
  };
  /** Finalisasi checklist sudah diantrikan dari perangkat ini (jangan diantrikan dua kali). */
  ChecklistFinal?: boolean;
  /** Jawaban checklist yang sudah diantrikan dari perangkat ini. */
  Jawaban?: Record<string, JawabanChecklistTeknisi>;
  Kondisi?: string;
  Pengawas?: { Nama: string; Jabatan: string | null };
  /** Terisi setelah "Kirim laporan", untuk layar Selesai tanpa sinyal. */
  Selesai?: { MulaiPada: string | null; SelesaiPada: string; Menit: number; Kondisi: string | null };
}

export interface FotoTertunda {
  Kunci: string;
  PerintahKerjaId: string;
  Kategori: 'FotoSebelum' | 'FotoSesudah' | 'TandaTangan';
  Berkas: Blob;
  NamaBerkas: string;
  Keterangan: string | null;
  DibuatPada: string;
}

const AWALAN_FOTO = 'teknisi:foto:';

export const kunciSesi = (perintahKerjaId: string) => `teknisi:sesi:${perintahKerjaId}`;
const awalanFotoTiket = (perintahKerjaId: string) => `${AWALAN_FOTO}${perintahKerjaId}:`;

/** Konteks penyimpanan lokal pengguna yang sedang masuk. */
export function useKonteksOffline(): KonteksOffline | null {
  const { auth } = usePage<PropsLapangan>().props;
  const organisasiId = auth.pengguna?.OrganisasiId;
  const penggunaId = auth.pengguna?.Id;

  return useMemo(
    () => (organisasiId && penggunaId && penyimpananTersedia() ? { organisasiId, penggunaId } : null),
    [organisasiId, penggunaId],
  );
}

/** Draf sesi kerja satu tiket; `ubah` menggabung lalu menyimpan ke perangkat. */
export function useSesiKerja(perintahKerjaId: string) {
  const konteks = useKonteksOffline();
  const [sesi, setSesi] = useState<SesiKerja | null>(null);
  const [dimuat, setDimuat] = useState(false);
  const terkini = useRef<SesiKerja>({ MulaiPada: null });

  useEffect(() => {
    let batal = false;
    if (!konteks) {
      setDimuat(true);
      return;
    }
    ambilDraf<SesiKerja>(konteks, kunciSesi(perintahKerjaId))
      .then((nilai) => {
        if (batal) return;
        terkini.current = nilai ?? { MulaiPada: null };
        setSesi(terkini.current);
      })
      .catch(() => undefined)
      .finally(() => {
        if (!batal) setDimuat(true);
      });
    return () => {
      batal = true;
    };
  }, [konteks, perintahKerjaId]);

  const ubah = useCallback(
    async (perubahan: Partial<SesiKerja>) => {
      terkini.current = lengkapiSesi({ ...terkini.current, ...perubahan });
      setSesi(terkini.current);
      if (konteks) {
        await simpanDraf(konteks, kunciSesi(perintahKerjaId), terkini.current).catch(() => undefined);
      }
    },
    [konteks, perintahKerjaId],
  );

  return { sesi: sesi ?? terkini.current, dimuat, ubah };
}

/** Sesi kerja tanpa hook, mis. untuk tombol "Mulai" di beranda. */
export async function ubahSesiKerja(
  konteks: KonteksOffline | null,
  perintahKerjaId: string,
  perubahan: Partial<SesiKerja>,
): Promise<void> {
  if (!konteks) return;
  const lama = (await ambilDraf<SesiKerja>(konteks, kunciSesi(perintahKerjaId)).catch(() => undefined)) ?? {
    MulaiPada: null,
  };
  const baru = { ...lama, ...perubahan };
  // Sesi yang sedang berjalan tidak dimulai ulang (mis. "Lanjutkan" ditekan dua kali).
  if (lama.MulaiPada && perubahan.MulaiPada) baru.MulaiPada = lama.MulaiPada;
  await simpanDraf(konteks, kunciSesi(perintahKerjaId), lengkapiSesi(baru)).catch(() => undefined);
}

/** Mulai pertama tercatat sekali, saat sesi waktu kerja pertama dimulai. */
function lengkapiSesi(sesi: SesiKerja): SesiKerja {
  return !sesi.MulaiPertama && sesi.MulaiPada ? { ...sesi, MulaiPertama: sesi.MulaiPada } : sesi;
}

async function unggahSatu(foto: FotoTertunda): Promise<void> {
  const data = new FormData();
  data.append('Berkas', new File([foto.Berkas], foto.NamaBerkas, { type: foto.Berkas.type || 'image/jpeg' }));
  data.append('JenisEntitas', 'PerintahKerja');
  data.append('EntitasId', foto.PerintahKerjaId);
  data.append('Kategori', foto.Kategori);
  if (foto.Keterangan) data.append('Keterangan', foto.Keterangan);
  await http.post(ruteLapangan.teknisi.berkas, data);
}

/** Unggahan berjalan berantai, jadi layar kerja dan pengirim antrean tidak mengunggah draf yang sama dua kali. */
let rantaiUnggahan: Promise<unknown> = Promise.resolve();

/**
 * Mengunggah foto dan tanda tangan yang tersimpan di perangkat ke lampiran Kolaborasi tiket;
 * yang gagal tetap di perangkat untuk dicoba lagi.
 *
 * Dipanggil juga oleh `useSinkronisasiOffline` tepat sebelum antrean dikirim: tanda tangan
 * penerima harus sudah menjadi lampiran ketika mutasi "selesai" tiba, karena organisasi yang
 * mewajibkannya menolak penyelesaian tanpa lampiran itu (TASK 39.10).
 *
 * @param perintahKerjaId satu tiket, beberapa tiket, atau `null` untuk semua draf
 */
export function unggahFotoTertunda(
  konteks: KonteksOffline,
  perintahKerjaId: string | string[] | null,
): Promise<{ berhasil: number; gagal: number }> {
  const jalan = rantaiUnggahan.then(async () => {
    let berhasil = 0;
    let gagal = 0;
    if (typeof navigator !== 'undefined' && !navigator.onLine) return { berhasil, gagal };
    const daftarTiket = perintahKerjaId === null ? null : ([] as string[]).concat(perintahKerjaId);
    const awalan = daftarTiket === null ? [AWALAN_FOTO] : daftarTiket.map(awalanFotoTiket);
    for (const satuAwalan of awalan) {
      const kunci = await daftarKunciDraf(konteks, satuAwalan).catch(() => [] as string[]);
      for (const satu of kunci) {
        // Dibaca ulang di dalam rantai: draf yang sudah diunggah pemanggil sebelumnya sudah terhapus.
        const nilai = await ambilDraf<FotoTertunda>(konteks, satu).catch(() => undefined);
        if (!nilai) continue;
        try {
          await unggahSatu(nilai);
          await hapusDraf(konteks, satu);
          berhasil++;
        } catch {
          gagal++;
        }
      }
    }
    return { berhasil, gagal };
  });
  rantaiUnggahan = jalan.catch(() => undefined);
  return jalan;
}

/**
 * Foto dan tanda tangan yang menunggu diunggah ke lampiran Kolaborasi tiket.
 * Tersimpan sebagai `Blob` di perangkat; diunggah begitu ada sinyal.
 */
export function useFotoTertunda(perintahKerjaId: string | null) {
  const konteks = useKonteksOffline();
  const [foto, setFoto] = useState<FotoTertunda[]>([]);

  const muat = useCallback(async () => {
    if (!konteks) return;
    const awalan = perintahKerjaId ? awalanFotoTiket(perintahKerjaId) : AWALAN_FOTO;
    const kunci = await daftarKunciDraf(konteks, awalan).catch(() => [] as string[]);
    const isi = await Promise.all(
      kunci.map((satu) => ambilDraf<FotoTertunda>(konteks, satu).catch(() => undefined)),
    );
    setFoto(
      isi
        .filter((satu): satu is FotoTertunda => satu !== undefined)
        .sort((a, b) => a.DibuatPada.localeCompare(b.DibuatPada)),
    );
  }, [konteks, perintahKerjaId]);

  useEffect(() => {
    void muat();
  }, [muat]);

  const tambah = useCallback(
    async (
      perintahKerjaIdFoto: string,
      kategori: FotoTertunda['Kategori'],
      berkas: Blob,
      keterangan: string | null = null,
    ) => {
      if (!konteks) return;
      const id = crypto.randomUUID();
      const ekstensi = berkas.type === 'image/png' ? 'png' : 'jpg';
      const nilai: FotoTertunda = {
        Kunci: `${awalanFotoTiket(perintahKerjaIdFoto)}${id}`,
        PerintahKerjaId: perintahKerjaIdFoto,
        Kategori: kategori,
        Berkas: berkas,
        NamaBerkas: `${kategori}-${new Date().toISOString().replace(/[:.]/g, '-')}.${ekstensi}`,
        Keterangan: keterangan,
        DibuatPada: new Date().toISOString(),
      };
      await simpanDraf(konteks, nilai.Kunci, nilai);
      await muat();
    },
    [konteks, muat],
  );

  const hapus = useCallback(
    async (kunci: string) => {
      if (!konteks) return;
      await hapusDraf(konteks, kunci).catch(() => undefined);
      await muat();
    },
    [konteks, muat],
  );

  /** Mengunggah semua yang tertunda; yang gagal tetap di perangkat untuk dicoba lagi. */
  const unggahSemua = useCallback(async (): Promise<{ berhasil: number; gagal: number }> => {
    if (!konteks || !navigator.onLine) return { berhasil: 0, gagal: 0 };
    const hasil = await unggahFotoTertunda(konteks, perintahKerjaId).finally(() => muat());
    if (hasil.berhasil > 0 && perintahKerjaId) {
      router.reload({ only: ['foto'] });
    }
    return hasil;
  }, [konteks, perintahKerjaId, muat]);

  return { foto, tambah, hapus, unggahSemua, muatUlang: muat };
}

/** URL pratinjau untuk Blob lokal; dicabut saat komponen dilepas. */
export function useUrlBlob(berkas: Blob | null | undefined): string | null {
  const [url, setUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!berkas) {
      setUrl(null);
      return;
    }
    const baru = URL.createObjectURL(berkas);
    setUrl(baru);
    return () => URL.revokeObjectURL(baru);
  }, [berkas]);

  return url;
}
