import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { Banner } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { WadahIkon3D } from '@/components/shared/Ikon3D';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { BarisDaftar, JudulBagian, Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { AsetRingkasTeknisi, PropsAsetTeknisi } from '@/features/Lapangan/types';
import { IsiAsetDitemukan } from '@/features/Lapangan/components/teknisi/IsiAsetDitemukan';
import { usePeringatanOffline } from '@/features/Lapangan/components/teknisi/umum';

const WARNA_KONDISI: Record<string, { label: string; warna: 'hijau' | 'kuning' | 'merah' }> = {
  Baik: { label: 'Baik', warna: 'hijau' },
  PerluPerhatian: { label: 'Perhatian', warna: 'kuning' },
  Rusak: { label: 'Rusak', warna: 'merah' },
};

/** Tab Aset teknisi: aset di tiket yang ditugaskan, pencarian, dan lembar aset dengan aksi cepat. */
export default function AsetTeknisi(props: PropsAsetTeknisi) {
  return (
    <KerangkaLapangan
      judulHalaman="Aset"
      navAktif="aset"
      avatar={false}
      sapaan="Mode Lapangan"
      judul="Aset"
      subjudul="Aset di tiketmu, cari, atau pindai labelnya"
      statusSinkron={false}
    >
      <IsiAset {...props} />
    </KerangkaLapangan>
  );
}

function IsiAset({ aset, cari, asetTerpilih, bolehLihat }: PropsAsetTeknisi) {
  usePeringatanOffline();
  const { daring, paket } = useSinkronisasiOffline();
  const [kata, setKata] = useState(cari);
  const [lembarBuka, setLembarBuka] = useState(Boolean(asetTerpilih));
  const [kataLuring, setKataLuring] = useState<string | null>(null);

  useEffect(() => setLembarBuka(Boolean(asetTerpilih)), [asetTerpilih]);

  if (!bolehLihat) {
    return (
      <KartuApung>
        <IlustrasiMomen
          jenis="tanpa-izin"
          judul="Belum ada akses aset"
          teks="Peranmu belum boleh melihat data aset. Hubungi admin organisasi bila kamu memerlukannya."
        />
      </KartuApung>
    );
  }

  // Tanpa sinyal, pencarian dilakukan di aset paket offline yang tersimpan di HP.
  const dariPaket: AsetRingkasTeknisi[] | null =
    kataLuring !== null && paket
      ? paket.Aset.filter((satu) =>
          `${satu.Nama} ${satu.KodeAset}`.toLowerCase().includes(kataLuring.toLowerCase()),
        ).map((satu) => ({
          Id: satu.Id,
          KodeAset: satu.KodeAset,
          Nama: satu.Nama,
          Kategori: null,
          Kondisi: satu.Kondisi,
          Lokasi: satu.NamaLokasi ? { Nama: satu.NamaLokasi, Induk: null } : null,
        }))
      : null;
  const daftar = dariPaket ?? aset;
  const sedangMencari = Boolean(dariPaket !== null ? kataLuring : cari);

  const kirim = (event: FormEvent) => {
    event.preventDefault();
    if (!daring) {
      setKataLuring(kata.trim());
      return;
    }
    setKataLuring(null);
    router.get(
      kata.trim() ? ruteLapangan.teknisi.asetCari(kata.trim()) : ruteLapangan.teknisi.aset,
      {},
      { preserveState: true },
    );
  };

  const buka = (satu: AsetRingkasTeknisi) => {
    if (!daring) {
      router.visit(ruteLapangan.teknisi.pindaiAset(satu.Id));
      return;
    }
    router.get(
      ruteLapangan.teknisi.asetPilih(satu.Id, cari || undefined),
      {},
      { preserveState: true, preserveScroll: true, only: ['asetTerpilih'] },
    );
  };

  return (
    <>
      <KartuApung pad>
        <form onSubmit={kirim} role="search">
          <IsianTiket
            label="Cari aset"
            ikon={Search}
            kanan={
              kata ? (
                <button
                  type="button"
                  onClick={() => {
                    setKata('');
                    setKataLuring(null);
                    if (cari) router.get(ruteLapangan.teknisi.aset, {}, { preserveState: true });
                  }}
                  aria-label="Kosongkan pencarian"
                  className="flex size-11 items-center justify-center rounded-full text-lapangan-teks-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
                >
                  <X aria-hidden className="size-5" />
                </button>
              ) : undefined
            }
          >
            <MasukanTiket
              value={kata}
              onChange={(event) => setKata(event.target.value)}
              placeholder="Nama atau kode aset"
              enterKeyHint="search"
              autoComplete="off"
            />
          </IsianTiket>
        </form>
      </KartuApung>

      <Banner
        warna="biru"
        ringkas
        judul="Pindai label QR"
        teks="Arahkan kamera ke label aset"
        ikon="magnifying_glass_tilted_left"
        href={ruteLapangan.teknisi.pindai}
      />

      <JudulBagian judul={sedangMencari ? `Hasil pencarian (${daftar.length})` : 'Aset di tiketmu'} />
      {daftar.length === 0 ? (
        <Kartu>
          <IlustrasiMomen
            ringkas
            ikon={sedangMencari ? 'magnifying_glass_tilted_left' : 'toolbox'}
            judul={sedangMencari ? 'Tidak ada yang cocok' : 'Belum ada aset'}
            teks={
              sedangMencari
                ? 'Coba nama atau kode lain.'
                : 'Aset dari tiket yang ditugaskan kepadamu akan tampil di sini.'
            }
          />
        </Kartu>
      ) : (
        <Kartu className="overflow-hidden">
          {daftar.map((satu) => {
            const ikon = ikonKategori(satu.Kategori ?? satu.Nama);
            const kondisi = satu.Kondisi ? WARNA_KONDISI[satu.Kondisi] : undefined;
            return (
              <BarisDaftar
                key={satu.Id}
                ikon={<WadahIkon3D nama={ikon.ikon} tint={ikon.tint} />}
                judul={satu.Nama}
                keterangan={[satu.KodeAset, satu.Lokasi?.Nama].filter(Boolean).join(' · ')}
                kanan={
                  kondisi && kondisi.warna !== 'hijau' ? (
                    <ChipStatus warna={kondisi.warna} ukuran="kecil">
                      {kondisi.label}
                    </ChipStatus>
                  ) : undefined
                }
                onClick={() => buka(satu)}
              />
            );
          })}
        </Kartu>
      )}

      {asetTerpilih && (
        <LembarBawah
          buka={lembarBuka}
          onBukaBerubah={setLembarBuka}
          judul={<span className="sr-only">{asetTerpilih.Nama}</span>}
          tanpaTombolTutup
          className="[&>div:nth-child(2)]:sr-only [&>div:nth-child(3)]:mt-1"
        >
          <IsiAsetDitemukan aset={asetTerpilih} chip={null} />
        </LembarBawah>
      )}
    </>
  );
}
