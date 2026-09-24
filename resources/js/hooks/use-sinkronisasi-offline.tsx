import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { http } from '@/lib/http';
import {
  ambilAntrian,
  ambilPaket,
  hapusBasisData,
  hapusMutasi,
  penyimpananTersedia,
  simpanMutasi,
  simpanPaket,
  type KonteksOffline,
} from '@/lib/penyimpanan-offline';
import { bersihkanCache, pasangServiceWorker, terapkanPembaruan, tetapkanKonteksCache } from '@/lib/pwa';
import { ruteOffline } from '@/features/Sinkronisasi/api';
import { unggahFotoAsetTertunda, unggahFotoTertunda } from '@/features/Lapangan/components/teknisi/sesiKerja';
import type {
  AntrianServer,
  KeputusanKonflik,
  MutasiOffline,
  OperasiOffline,
  PaketOffline,
  ResponsPaketOffline,
  StatusSinkronisasi,
} from '@/features/Sinkronisasi/types';
import type { PageProps } from '@/types/global';

const KUNCI_PERANGKAT = 'amanpoll:identitas-perangkat';

/** Mutasi yang sudah tuntas di server tidak perlu lagi disimpan perangkat. */
const STATUS_TUNTAS = ['Selesai', 'Dibatalkan'];

interface PermintaanMutasi {
  Operasi: OperasiOffline;
  EntitasId: string;
  VersiKlien: number | null;
  MuatanData: Record<string, unknown>;
  Label: string;
}

/** Tanda tangan pengantri mutasi offline, dipakai dialog yang menulis draft. */
export type FungsiAntrikan = (permintaan: PermintaanMutasi) => Promise<void>;

interface NilaiSinkronisasi {
  status: StatusSinkronisasi;
  daring: boolean;
  paket: PaketOffline | null;
  antrian: MutasiOffline[];
  jumlahBelumTersinkron: number;
  jumlahKonflik: number;
  memuat: boolean;
  adaPembaruanAplikasi: boolean;
  terapkanPembaruanAplikasi: () => void;
  muatPaket: () => Promise<void>;
  antrikan: FungsiAntrikan;
  dorong: () => Promise<void>;
  selesaikanKonflik: (kunciOperasi: string, keputusan: KeputusanKonflik) => Promise<void>;
  bersihkanDataLokal: () => Promise<void>;
}

const KonteksSinkronisasi = createContext<NilaiSinkronisasi | null>(null);

function identitasPerangkat(): string {
  try {
    const tersimpan = window.localStorage.getItem(KUNCI_PERANGKAT);
    if (tersimpan) return tersimpan;

    const baru = crypto.randomUUID();
    window.localStorage.setItem(KUNCI_PERANGKAT, baru);
    return baru;
  } catch {
    // Penyimpanan lokal diblokir (mode privat): perangkat tetap dapat bekerja
    // online, hanya saja antreannya tidak bertahan antar sesi.
    return crypto.randomUUID();
  }
}

function namaPerangkat(): string {
  return typeof navigator === 'undefined' ? 'Perangkat' : navigator.userAgent.slice(0, 180);
}

function amplopPerangkat() {
  return {
    IdentitasPerangkat: identitasPerangkat(),
    NamaPerangkat: namaPerangkat(),
    Platform: typeof navigator === 'undefined' ? null : navigator.platform,
  };
}

/** Menyatukan status antrean lokal menjadi satu status tampil (DESIGN.md 24). */
function hitungStatus(daring: boolean, mengirim: boolean, antrian: MutasiOffline[]): StatusSinkronisasi {
  if (antrian.some((m) => m.Status === 'Konflik')) return 'Konflik';
  if (!daring) return 'Offline';
  if (mengirim) return 'Menyinkronkan';
  if (antrian.some((m) => m.Status === 'Gagal')) return 'GagalSinkron';
  return 'Online';
}

export function PenyediaSinkronisasiOffline({ children }: { children: ReactNode }) {
  const { auth } = usePage<PageProps>().props;
  const konteks: KonteksOffline | null = auth.pengguna
    ? { organisasiId: auth.pengguna.OrganisasiId, penggunaId: auth.pengguna.Id }
    : null;

  const [daring, setDaring] = useState(() => (typeof navigator === 'undefined' ? true : navigator.onLine));
  const [paket, setPaket] = useState<PaketOffline | null>(null);
  const [antrian, setAntrian] = useState<MutasiOffline[]>([]);
  const [memuat, setMemuat] = useState(false);
  const [mengirim, setMengirim] = useState(false);
  const [adaPembaruanAplikasi, setAdaPembaruanAplikasi] = useState(false);
  const sedangMendorong = useRef(false);

  const aktif = konteks !== null && penyimpananTersedia();

  const segarkanDariPenyimpanan = useCallback(async () => {
    if (!konteks) return;
    const [paketLokal, antrianLokal] = await Promise.all([ambilPaket(konteks), ambilAntrian(konteks)]);
    setPaket(paketLokal ?? null);
    setAntrian(antrianLokal);
  }, [konteks?.organisasiId, konteks?.penggunaId]);

  /** Menyelaraskan antrean lokal dengan status yang baru saja dikembalikan server. */
  const serapAntreanServer = useCallback(
    async (dariServer: AntrianServer[]) => {
      if (!konteks) return;

      const lokal = await ambilAntrian(konteks);
      const peta = new Map(dariServer.map((baris) => [baris.KunciOperasi, baris]));

      await Promise.all(
        lokal.map(async (mutasi) => {
          const baris = peta.get(mutasi.KunciOperasi);
          if (!baris) return;

          if (STATUS_TUNTAS.includes(baris.Status)) {
            await hapusMutasi(konteks, mutasi.KunciOperasi);
            return;
          }

          await simpanMutasi(konteks, {
            ...mutasi,
            Status: baris.Status,
            Konflik: baris.Konflik,
            Percobaan: baris.Percobaan,
          });
        }),
      );

      await segarkanDariPenyimpanan();
    },
    [konteks?.organisasiId, konteks?.penggunaId, segarkanDariPenyimpanan],
  );

  const dorong = useCallback(async () => {
    if (!aktif || !konteks || sedangMendorong.current || !navigator.onLine) return;

    sedangMendorong.current = true;
    // Foto aset dari HP (PRD 8.4) tidak bergantung pada antrean mutasi: dikirim tiap sinyal kembali.
    // Yang ditolak server ditandai di draf dan ditampilkan di layar aset, bukan dibuang diam-diam.
    await unggahFotoAsetTertunda(konteks, null).catch(() => undefined);
    const lokal = await ambilAntrian(konteks).catch(() => [] as MutasiOffline[]);
    // IndexedDB mengurutkan menurut KunciOperasi (UUID acak); server memproses menurut
    // urutan kiriman, jadi urutan pembuatan harus dipulihkan dulu (terima → mulai → selesai).
    const belumTuntas = lokal
      .filter((m) => m.Status === 'Menunggu' || m.Status === 'Diproses')
      .sort((a, b) => a.DibuatPada.localeCompare(b.DibuatPada));
    if (belumTuntas.length === 0) {
      sedangMendorong.current = false;
      return;
    }

    setMengirim(true);
    try {
      // Foto dan tanda tangan penerima yang tersimpan di HP dikirim lebih dulu, supaya
      // konfirmasi penerima sudah tercatat saat mutasi "selesai" tiba (PRD 8.22).
      // Yang gagal diunggah tidak menahan antrean; penolakannya tampil sebagai mutasi Gagal.
      const entitas = belumTuntas.flatMap((m) => (m.EntitasId ? [m.EntitasId] : []));
      await unggahFotoTertunda(konteks, [...new Set(entitas)]).catch(() => undefined);
      const { data } = await http.post<{ Antrean: AntrianServer[] }>(ruteOffline.antrian, {
        ...amplopPerangkat(),
        Mutasi: belumTuntas.map((m) => ({
          KunciOperasi: m.KunciOperasi,
          Operasi: m.Operasi,
          EntitasId: m.EntitasId,
          VersiKlien: m.VersiKlien,
          MuatanData: m.MuatanData,
        })),
      });
      await serapAntreanServer(data.Antrean);
    } catch {
      // Gagal mengirim bukan kegagalan permanen: mutasi tetap tersimpan lokal
      // dan dicoba lagi saat koneksi membaik.
    } finally {
      sedangMendorong.current = false;
      setMengirim(false);
    }
  }, [aktif, konteks?.organisasiId, konteks?.penggunaId, serapAntreanServer]);

  const muatPaket = useCallback(async () => {
    if (!aktif || !konteks || !navigator.onLine) return;

    setMemuat(true);
    try {
      const { data } = await http.post<ResponsPaketOffline>(ruteOffline.paket, amplopPerangkat());
      await simpanPaket(konteks, data.Paket);
      setPaket(data.Paket);
      await serapAntreanServer(data.Antrean);
    } catch {
      toast.error('Gagal memuat paket kerja terbaru. Data lokal tetap dipakai.');
    } finally {
      setMemuat(false);
    }
  }, [aktif, konteks?.organisasiId, konteks?.penggunaId, serapAntreanServer]);

  const antrikan = useCallback(
    async (permintaan: PermintaanMutasi) => {
      if (!aktif || !konteks) {
        toast.error('Perangkat ini tidak mendukung penyimpanan offline.');
        return;
      }

      const mutasi: MutasiOffline = {
        KunciOperasi: crypto.randomUUID(),
        Operasi: permintaan.Operasi,
        EntitasId: permintaan.EntitasId,
        VersiKlien: permintaan.VersiKlien,
        MuatanData: permintaan.MuatanData,
        Status: 'Menunggu',
        Konflik: null,
        Percobaan: 0,
        Label: permintaan.Label,
        DibuatPada: new Date().toISOString(),
      };

      await simpanMutasi(konteks, mutasi);
      await segarkanDariPenyimpanan();
      await dorong();
    },
    [aktif, konteks?.organisasiId, konteks?.penggunaId, dorong, segarkanDariPenyimpanan],
  );

  const selesaikanKonflik = useCallback(
    async (kunciOperasi: string, keputusan: KeputusanKonflik) => {
      if (!konteks) return;

      const { data } = await http.post<{ Antrean: AntrianServer[] }>(
        ruteOffline.antrianStatus,
        amplopPerangkat(),
      );
      const baris = data.Antrean.find((item) => item.KunciOperasi === kunciOperasi);
      if (!baris) {
        await serapAntreanServer(data.Antrean);
        return;
      }

      await http.post(ruteOffline.antrianKonflik(baris.Id), { Keputusan: keputusan });
      const segar = await http.post<{ Antrean: AntrianServer[] }>(
        ruteOffline.antrianStatus,
        amplopPerangkat(),
      );
      await serapAntreanServer(segar.data.Antrean);
      await muatPaket();
    },
    [konteks?.organisasiId, konteks?.penggunaId, muatPaket, serapAntreanServer],
  );

  /** Membersihkan jejak organisasi dari perangkat (20.02). */
  const bersihkanDataLokal = useCallback(async () => {
    bersihkanCache();
    if (!konteks) return;

    try {
      await http.post(ruteOffline.perangkatLepas, amplopPerangkat());
    } catch {
      // Logout tidak boleh gagal hanya karena server tidak terjangkau.
    }

    await hapusBasisData(konteks);
    setPaket(null);
    setAntrian([]);
  }, [konteks?.organisasiId, konteks?.penggunaId]);

  useEffect(() => {
    void pasangServiceWorker(() => setAdaPembaruanAplikasi(true));
  }, []);

  useEffect(() => {
    if (konteks) {
      tetapkanKonteksCache(konteks);
    }
  }, [konteks?.organisasiId, konteks?.penggunaId]);

  useEffect(() => {
    if (!aktif) return;
    void segarkanDariPenyimpanan();
  }, [aktif, segarkanDariPenyimpanan]);

  useEffect(() => {
    const keOnline = () => {
      setDaring(true);
      void dorong();
    };
    const keOffline = () => setDaring(false);

    window.addEventListener('online', keOnline);
    window.addEventListener('offline', keOffline);

    return () => {
      window.removeEventListener('online', keOnline);
      window.removeEventListener('offline', keOffline);
    };
  }, [dorong]);

  const jumlahBelumTersinkron = antrian.filter(
    (m) => m.Status === 'Menunggu' || m.Status === 'Diproses',
  ).length;
  const jumlahKonflik = antrian.filter((m) => m.Status === 'Konflik').length;

  const nilai = useMemo<NilaiSinkronisasi>(
    () => ({
      status: hitungStatus(daring, mengirim, antrian),
      daring,
      paket,
      antrian,
      jumlahBelumTersinkron,
      jumlahKonflik,
      memuat,
      adaPembaruanAplikasi,
      terapkanPembaruanAplikasi: terapkanPembaruan,
      muatPaket,
      antrikan,
      dorong,
      selesaikanKonflik,
      bersihkanDataLokal,
    }),
    [
      daring,
      mengirim,
      paket,
      antrian,
      jumlahBelumTersinkron,
      jumlahKonflik,
      memuat,
      adaPembaruanAplikasi,
      muatPaket,
      antrikan,
      dorong,
      selesaikanKonflik,
      bersihkanDataLokal,
    ],
  );

  return <KonteksSinkronisasi.Provider value={nilai}>{children}</KonteksSinkronisasi.Provider>;
}

export function useSinkronisasiOffline(): NilaiSinkronisasi {
  const konteks = useContext(KonteksSinkronisasi);

  if (!konteks) {
    throw new Error('useSinkronisasiOffline harus dipakai di dalam PenyediaSinkronisasiOffline.');
  }

  return konteks;
}
