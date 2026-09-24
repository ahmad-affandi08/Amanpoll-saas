import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ambilDraf, simpanDraf } from '@/lib/penyimpanan-offline';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import type { PaketOffline } from '@/features/Sinkronisasi/types';
import { ruteLapangan } from '@/features/Lapangan/api';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { PemindaiQr } from '@/features/Lapangan/components/PemindaiQr';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { AsetDitemukanTeknisi, PropsPindaiTeknisi, TiketTeknisi } from '@/features/Lapangan/types';
import { waktuRelatif } from '@/features/Lapangan/waktu';
import { IsiAsetDitemukan } from '@/features/Lapangan/components/teknisi/IsiAsetDitemukan';
import { useKonteksOffline } from '@/features/Lapangan/components/teknisi/sesiKerja';
import { usePeringatanOffline } from '@/features/Lapangan/components/teknisi/umum';

const KUNCI_TERAKHIR = 'teknisi:terakhir-dipindai';

interface TerakhirDipindai {
  Id: string;
  KodeAset: string;
  Nama: string;
  Kategori: string | null;
  Pada: string;
}

/** Label QR lama dapat berisi URL resolver (`/aset/pindai/<kode>`); ambil kodenya saja. */
function kodeDariPindaian(teks: string): string {
  const cocok = teks.match(/\/aset\/pindai\/([^/?#]+)/);
  return cocok ? decodeURIComponent(cocok[1]) : teks.trim();
}

/**
 * Aset dari paket offline FASE 20 saat tanpa sinyal: dicocokkan dengan kode aset atau
 * kode label, dan tiket teknisi di aset itu diambil dari penugasan paket.
 */
function asetDariPaket(paket: PaketOffline | null, kode: string): AsetDitemukanTeknisi | null {
  const aset = paket?.Aset.find((satu) => [satu.KodeAset, satu.KodeQr, satu.KodeBatang].includes(kode));
  if (!paket || !aset) return null;

  const penugasan = paket.Penugasan.find((satu) => satu.AsetId.includes(aset.Id));
  const tiket: TiketTeknisi | null = penugasan
    ? {
        Id: penugasan.Id,
        Nomor: penugasan.Nomor,
        Judul: penugasan.Judul,
        Jenis: penugasan.Jenis,
        Status: penugasan.Status,
        Prioritas: penugasan.Prioritas,
        Versi: penugasan.Versi,
        DariKeluhan: false,
        DilaporkanPada: null,
        DijadwalkanMulaiPada: penugasan.DijadwalkanMulaiPada,
        BatasPada: penugasan.BatasPenyelesaianPada,
        DimulaiPada: null,
        DiperbaruiPada: null,
        PenugasanId: null,
        PerluRespons: penugasan.PerluResponsPenugasan,
        DitugaskanPada: null,
        DapatDibuka: true,
        Aset: null,
        Lokasi: penugasan.NamaLokasi ? { Nama: penugasan.NamaLokasi, Induk: null } : null,
      }
    : null;

  return {
    Id: aset.Id,
    KodeAset: aset.KodeAset,
    Nama: aset.Nama,
    Kategori: null,
    Kondisi: aset.Kondisi,
    Lokasi: aset.NamaLokasi ? { Nama: aset.NamaLokasi, Induk: null } : null,
    Status: aset.Status,
    TingkatKritis: aset.TingkatKritis,
    MerekTipe: null,
    GaransiBerakhirPada: null,
    ServisTerakhirPada: null,
    TiketSaya: tiket,
    Inspeksi: null,
    BolehLapor: true,
    BolehLihatRiwayat: true,
  };
}

/** Kamera pindai dan lembar "Aset ditemukan" (DESIGN §36.6 layar 13–14). */
export default function PindaiTeknisi(props: PropsPindaiTeknisi) {
  return (
    <KerangkaLapangan varian="polos" judulHalaman="Pindai aset" navBawah={false}>
      <IsiPindai {...props} />
    </KerangkaLapangan>
  );
}

function IsiPindai({ asetDitemukan, galatPindai, tanpaIzin }: PropsPindaiTeknisi) {
  usePeringatanOffline();
  const { paket } = useSinkronisasiOffline();
  const konteks = useKonteksOffline();
  const [lokal, setLokal] = useState<{ aset: AsetDitemukanTeknisi | null; galat: string | null } | null>(
    null,
  );
  const [ditutup, setDitutup] = useState(false);
  const [terakhir, setTerakhir] = useState<TerakhirDipindai | null>(null);

  const aset = lokal ? lokal.aset : asetDitemukan;
  const galat = lokal ? lokal.galat : galatPindai;
  const lembarBuka = !ditutup && Boolean(aset || galat);

  useEffect(() => {
    setDitutup(false);
    setLokal(null);
  }, [asetDitemukan, galatPindai]);

  useEffect(() => {
    if (!konteks) return;
    ambilDraf<TerakhirDipindai>(konteks, KUNCI_TERAKHIR)
      .then((nilai) => setTerakhir(nilai ?? null))
      .catch(() => undefined);
  }, [konteks]);

  useEffect(() => {
    if (!konteks || !asetDitemukan) return;
    const baru: TerakhirDipindai = {
      Id: asetDitemukan.Id,
      KodeAset: asetDitemukan.KodeAset,
      Nama: asetDitemukan.Nama,
      Kategori: asetDitemukan.Kategori,
      Pada: new Date().toISOString(),
    };
    void simpanDraf(konteks, KUNCI_TERAKHIR, baru).catch(() => undefined);
  }, [konteks, asetDitemukan?.Id]);

  const tanganiHasil = (teks: string) => {
    const kode = kodeDariPindaian(teks);
    if (navigator.onLine) {
      router.visit(ruteLapangan.teknisi.pindaiKode(kode), { preserveState: true, replace: true });
      return;
    }
    const dariPaket = asetDariPaket(paket, kode);
    setDitutup(false);
    setLokal(
      dariPaket
        ? { aset: dariPaket, galat: null }
        : { aset: null, galat: `Kode ${kode} belum ada di data offline HP. Coba lagi saat ada sinyal.` },
    );
  };

  const kartuTerakhir =
    terakhir && !lembarBuka ? (
      <button
        type="button"
        onClick={() =>
          router.visit(ruteLapangan.teknisi.pindaiAset(terakhir.Id), { preserveState: true, replace: true })
        }
        className="flex items-center gap-2.5 rounded-2xl bg-white/12 px-3 py-2.5 text-left text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
      >
        <Ikon3D nama={ikonKategori(terakhir.Kategori ?? terakhir.Nama).ikon} ukuran={32} />
        <span className="min-w-0 flex-1">
          <small className="block text-xs font-semibold text-white/75">
            Terakhir dipindai · {waktuRelatif(terakhir.Pada)}
          </small>
          <b className="block truncate text-sm font-bold">
            {terakhir.KodeAset} · {terakhir.Nama}
          </b>
        </span>
      </button>
    ) : undefined;

  return (
    <PemindaiQr
      onHasil={tanganiHasil}
      onTutup={() => router.visit(ruteLapangan.teknisi.beranda)}
      aktif={!lembarBuka}
      bawah={kartuTerakhir}
    >
      <LembarBawah
        buka={lembarBuka}
        onBukaBerubah={(buka) => {
          if (!buka) setDitutup(true);
        }}
        judul={
          aset ? (
            <span className="sr-only">{aset.Nama}</span>
          ) : tanpaIzin ? (
            'Tanpa izin'
          ) : (
            'Aset tidak ditemukan'
          )
        }
        tanpaTombolTutup={Boolean(aset)}
        // Nama aset sudah menjadi kepala isi lembar; baris judul bawaan hanya untuk pembaca layar.
        className={aset ? '[&>div:nth-child(2)]:sr-only [&>div:nth-child(3)]:mt-1' : undefined}
      >
        {aset ? (
          <IsiAsetDitemukan aset={aset} luring={lokal !== null} />
        ) : (
          <IlustrasiMomen
            ringkas
            jenis={tanpaIzin ? 'tanpa-izin' : 'galat'}
            ikon={tanpaIzin ? 'shield' : 'magnifying_glass_tilted_left'}
            judul={tanpaIzin ? 'Aset ini di luar aksesmu' : 'Kode tidak dikenal'}
            teks={galat ?? undefined}
            aksi={
              <TombolLapangan penuh onClick={() => setDitutup(true)}>
                Pindai lagi
              </TombolLapangan>
            }
          />
        )}
      </LembarBawah>
    </PemindaiQr>
  );
}
