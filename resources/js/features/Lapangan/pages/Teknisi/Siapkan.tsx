import { router, usePage } from '@inertiajs/react';
import { Check, LoaderCircle, RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { http } from '@/lib/http';
import { ambilPaket } from '@/lib/penyimpanan-offline';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { PropsSiapkanTeknisi } from '@/features/Lapangan/types';
import { useKonteksOffline } from '@/features/Lapangan/components/teknisi/sesiKerja';
import { KUNCI_SIAPKAN_DILEWATI } from '@/features/Lapangan/components/teknisi/umum';

type KunciBagian = 'tiket' | 'aset' | 'checklist' | 'katalog';
type KeadaanBagian = 'menunggu' | 'berjalan' | 'selesai' | 'gagal';

interface Bagian {
  kunci: KunciBagian;
  judul: string;
  ikon: NamaIkon3D;
}

const BAGIAN: Bagian[] = [
  { kunci: 'tiket', judul: 'Tiket kerja', ikon: 'ticket' },
  { kunci: 'aset', judul: 'Data aset', ikon: 'office_building' },
  { kunci: 'checklist', judul: 'Checklist & formulir', ikon: 'clipboard' },
  { kunci: 'katalog', judul: 'Katalog suku cadang', ikon: 'nut_and_bolt' },
];

/** Modul halaman lapangan; memuatnya sekali membuat berkas JS-nya tersimpan untuk dipakai offline. */
const HALAMAN_TEKNISI = import.meta.glob('./*.tsx');
const HALAMAN_BERSAMA = import.meta.glob('../*.tsx');

function lewati(): void {
  try {
    window.sessionStorage.setItem(KUNCI_SIAPKAN_DILEWATI, '1');
  } catch {
    // Penyimpanan sesi diblokir: beranda mungkin menawarkan persiapan lagi, tidak apa-apa.
  }
  router.visit(ruteLapangan.teknisi.beranda, { replace: true });
}

function angka(nilai: number): string {
  return nilai.toLocaleString('id-ID');
}

/**
 * Menyiapkan Mode Lapangan (DESIGN §36.6 layar 02): paket offline FASE 20 diunduh ke
 * IndexedDB, lalu layar-layar teknisi dibuka sekali di latar supaya service worker
 * menyimpannya. Setelah itu tiket, aset, dan checklist tetap terbuka tanpa sinyal.
 */
export default function SiapkanTeknisi(props: PropsSiapkanTeknisi) {
  return (
    <KerangkaLapangan varian="polos" latar="putih" judulHalaman="Menyiapkan Mode Lapangan" navBawah={false}>
      <IsiSiapkan {...props} />
    </KerangkaLapangan>
  );
}

function IsiSiapkan({ tiketId, asetId, jumlah }: PropsSiapkanTeknisi) {
  const { version } = usePage();
  const { muatPaket, daring } = useSinkronisasiOffline();
  const konteks = useKonteksOffline();
  const [keadaan, setKeadaan] = useState<Record<KunciBagian, KeadaanBagian>>({
    tiket: 'menunggu',
    aset: 'menunggu',
    checklist: 'menunggu',
    katalog: 'menunggu',
  });
  const [persenBagian, setPersenBagian] = useState<Record<KunciBagian, number>>({
    tiket: 0,
    aset: 0,
    checklist: 0,
    katalog: 0,
  });
  const [percobaan, setPercobaan] = useState(0);
  const berjalan = useRef(false);

  /** Buka satu layar sebagai kunjungan Inertia dan sebagai halaman utuh, supaya keduanya tersimpan. */
  const simpanLayar = async (url: string) => {
    await Promise.all([
      http.get(url, {
        headers: {
          'X-Inertia': 'true',
          'X-Inertia-Version': version ?? '',
          Accept: 'text/html, application/xhtml+xml',
        },
      }),
      http.get(url, { headers: { Accept: 'text/html', 'X-Amanpoll-Simpan': 'halaman' } }),
    ]);
  };

  const jalankanBagian = async (kunci: KunciBagian, tugas: (() => Promise<unknown>)[]) => {
    setKeadaan((lama) => ({ ...lama, [kunci]: 'berjalan' }));
    let selesai = 0;
    for (const satu of tugas) {
      await satu();
      selesai++;
      setPersenBagian((lama) => ({ ...lama, [kunci]: Math.round((selesai / tugas.length) * 100) }));
    }
    setKeadaan((lama) => ({ ...lama, [kunci]: 'selesai' }));
  };

  useEffect(() => {
    if (!daring || !konteks || berjalan.current) return;
    berjalan.current = true;
    let urutan: KunciBagian = 'tiket';

    (async () => {
      try {
        urutan = 'tiket';
        await jalankanBagian('tiket', [
          async () => {
            await muatPaket();
            if (!(await ambilPaket(konteks))) throw new Error('Paket offline tidak tersimpan.');
          },
          () => simpanLayar(ruteLapangan.teknisi.beranda),
          () => simpanLayar(ruteLapangan.teknisi.tugas),
          ...tiketId.map((id) => () => simpanLayar(ruteLapangan.teknisi.tugasDetail(id))),
        ]);
        urutan = 'aset';
        await jalankanBagian('aset', [
          () => simpanLayar(ruteLapangan.teknisi.aset),
          () => simpanLayar(ruteLapangan.teknisi.pindai),
          ...asetId.slice(0, 30).map((id) => () => simpanLayar(ruteLapangan.teknisi.riwayatAset(id))),
        ]);
        urutan = 'checklist';
        await jalankanBagian('checklist', [
          ...tiketId.map((id) => () => simpanLayar(ruteLapangan.teknisi.kerjakan(id))),
          () => Promise.all(Object.values(HALAMAN_TEKNISI).map((muat) => muat())),
          () => Promise.all(Object.values(HALAMAN_BERSAMA).map((muat) => muat())),
        ]);
        urutan = 'katalog';
        await jalankanBagian('katalog', [
          () => simpanLayar(ruteLapangan.teknisi.sukuCadang),
          () => simpanLayar(ruteLapangan.akun),
          () => simpanLayar(ruteLapangan.notifikasi),
        ]);
        window.setTimeout(() => router.visit(ruteLapangan.teknisi.beranda, { replace: true }), 900);
      } catch {
        setKeadaan((lama) => ({ ...lama, [urutan]: 'gagal' }));
        berjalan.current = false;
      }
    })();
  }, [daring, konteks, percobaan]);

  const bobot: Record<KunciBagian, number> = { tiket: 0.35, aset: 0.25, checklist: 0.3, katalog: 0.1 };
  const persen = Math.round(
    BAGIAN.reduce(
      (total, satu) =>
        total + (keadaan[satu.kunci] === 'selesai' ? 100 : persenBagian[satu.kunci]) * bobot[satu.kunci],
      0,
    ),
  );
  const gagal = BAGIAN.some((satu) => keadaan[satu.kunci] === 'gagal');
  const sisaBagian = BAGIAN.filter((satu) => keadaan[satu.kunci] !== 'selesai').length;

  const keterangan: Record<KunciBagian, string> = {
    tiket: `${angka(jumlah.Tiket)} tiket ditugaskan`,
    aset: `${angka(jumlah.Aset)} aset di ${angka(jumlah.Lokasi)} lokasi`,
    checklist: `${angka(jumlah.Templat)} templat`,
    katalog: `${angka(jumlah.SukuCadang)} item`,
  };

  if (!daring && persen === 0) {
    return (
      <div className="flex flex-1 flex-col justify-center">
        <IlustrasiMomen
          jenis="offline"
          judul="Butuh sinyal sekali saja"
          teks="Sambungkan ke internet untuk mengunduh pekerjaanmu. Setelah itu Mode Lapangan tetap jalan tanpa sinyal."
          aksi={
            <TombolLapangan ragam="garis" penuh onClick={lewati}>
              Lewati dulu
            </TombolLapangan>
          }
        />
      </div>
    );
  }

  return (
    <div className="flex flex-col px-1 pt-10">
      <div className="relative mx-auto">
        <div className="gradien-ilustrasi-lapangan flex size-[150px] items-center justify-center rounded-full shadow-[0_20px_40px_rgb(15_42_68_/_0.1)]">
          <Ikon3D nama="mobile_phone" ukuran={96} segera />
        </div>
        <Ikon3D nama="cloud" ukuran={64} className="absolute -top-2 left-[calc(50%+34px)]" segera />
      </div>
      <h1 className="mt-6 text-center text-2xl font-bold tracking-[-0.01em]">Menyiapkan Mode Lapangan</h1>
      <p className="mt-1.5 px-2 text-center text-[15px] text-lapangan-teks-3">
        Pekerjaanmu diunduh ke HP supaya tetap bisa bekerja tanpa sinyal.
      </p>

      <div className="mt-6">
        <div className="flex items-end justify-between gap-3">
          <strong
            aria-live="polite"
            className="text-[40px] leading-none font-bold tracking-[-0.03em] tabular-nums"
          >
            {persen}%
          </strong>
          <span className="pb-1 text-[13px] font-semibold text-lapangan-teks-3">
            {BAGIAN.length - sisaBagian} dari {BAGIAN.length} bagian
          </span>
        </div>
        <div
          role="progressbar"
          aria-label="Kemajuan unduhan"
          aria-valuenow={persen}
          aria-valuemin={0}
          aria-valuemax={100}
          className="mt-3 h-2.5 overflow-hidden rounded-full bg-lapangan-garis-2"
        >
          <i
            className="gradien-fab-lapangan block h-full rounded-full transition-[width] duration-500"
            style={{ width: `${persen}%` }}
          />
        </div>
      </div>

      <ul className="mt-5 overflow-hidden rounded-[20px] bg-white shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]">
        {BAGIAN.map((satu) => {
          const kini = keadaan[satu.kunci];
          return (
            <li
              key={satu.kunci}
              className="flex items-center gap-3 px-3.5 py-3 [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2"
            >
              <Ikon3D nama={satu.ikon} ukuran={34} />
              <div className="min-w-0 flex-1">
                <b className="block text-[15px] font-bold">{satu.judul}</b>
                <span className="block text-[13px] text-lapangan-teks-3">{keterangan[satu.kunci]}</span>
              </div>
              {kini === 'selesai' && (
                <ChipStatus warna="hijau" ikon={Check}>
                  Selesai
                </ChipStatus>
              )}
              {kini === 'berjalan' && (
                <span className="inline-flex items-center gap-1.5 text-sm font-bold text-lapangan-oranye-teks tabular-nums">
                  <LoaderCircle aria-hidden className="size-[18px] animate-spin text-lapangan-oranye-600" />
                  {persenBagian[satu.kunci]}%
                </span>
              )}
              {kini === 'menunggu' && (
                <span className="text-[13px] font-semibold text-lapangan-teks-3">Menunggu</span>
              )}
              {kini === 'gagal' && (
                <ChipStatus warna="merah" ukuran="kecil">
                  Gagal
                </ChipStatus>
              )}
            </li>
          );
        })}
      </ul>

      {gagal ? (
        <div className="mt-5 flex flex-col gap-2.5">
          <p role="alert" className="text-center text-sm font-semibold text-lapangan-merah-700">
            Unduhan terputus. Pastikan sinyal stabil lalu coba lagi.
          </p>
          <TombolLapangan penuh onClick={() => setPercobaan((n) => n + 1)}>
            <RotateCcw aria-hidden />
            Coba lagi
          </TombolLapangan>
          <TombolLapangan ragam="garis" penuh onClick={lewati}>
            Lewati dulu
          </TombolLapangan>
        </div>
      ) : (
        <p className="mt-[18px] text-center text-[13px] text-lapangan-teks-3">
          {persen >= 100
            ? 'Siap! Membuka beranda…'
            : 'Biarkan aplikasi tetap terbuka, sebentar lagi selesai.'}
        </p>
      )}
    </div>
  );
}
