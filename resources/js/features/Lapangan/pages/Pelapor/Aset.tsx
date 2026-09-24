import { Link, router, usePage } from '@inertiajs/react';
import { ScanLine, Search } from 'lucide-react';
import { useState } from 'react';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { Banner } from '@/features/Lapangan/components/Banner';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { PemindaiQr } from '@/features/Lapangan/components/PemindaiQr';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { kodeDariPindaian } from '@/features/Lapangan/components/pelapor/pindai';
import { BarisAsetLokasi } from '@/features/Lapangan/components/pelapor/BarisAset';
import { LembarLokasi } from '@/features/Lapangan/components/pelapor/LembarLokasi';
import type { LokasiPelapor, PropsAsetPelapor } from '@/features/Lapangan/types';

/** Aset di lokasi (DESIGN.md 36.7 layar 14): kondisi, laporan terbuka, tombol Lapor per aset. */
export default function AsetPelapor() {
  const { props } = usePage<PropsAsetPelapor>();
  const [pindai, setPindai] = useState(false);
  const lokasi = props.lokasi;
  const induk = lokasi && lokasi.Label !== lokasi.Nama ? lokasi.Label.split(' · ')[0] : null;

  return (
    <KerangkaLapangan
      judulHalaman="Aset"
      varian="appbar"
      mode="Pelapor"
      navAktif="aset"
      navBawah
      kembali={false}
      panjang={props.bolehLihat}
      judul={
        <span className="pl-1 text-[22px] font-extrabold">
          {lokasi ? `Aset ${lokasi.Nama}` : 'Aset di dekatmu'}
        </span>
      }
      subjudul={
        <span className="pl-1">
          {lokasi
            ? [induk, `${props.aset.length} aset di dekatmu`].filter(Boolean).join(' · ')
            : 'Pilih lokasi untuk melihat alat'}
        </span>
      }
      aksiKanan={
        props.bolehLihat ? (
          <TombolAppbar label="Pindai QR alat" ikon={ScanLine} onClick={() => setPindai(true)} />
        ) : undefined
      }
    >
      <IsiAset {...props} />
      {pindai && (
        <PemindaiQr
          judul="Pindai QR alat"
          petunjuk="Arahkan ke stiker QR di badan alat"
          labelKetik="Ketik kode alat"
          onTutup={() => setPindai(false)}
          onHasil={(kode) => {
            setPindai(false);
            router.visit(ruteLapangan.pelapor.laporDengan({ kode: kodeDariPindaian(kode) }));
          }}
        />
      )}
    </KerangkaLapangan>
  );
}

function IsiAset({ bolehLihat, lokasi, pilihanLokasi, aset }: PropsAsetPelapor) {
  const [cari, setCari] = useState('');
  const [bukaLokasi, setBukaLokasi] = useState(false);

  if (!bolehLihat) {
    return (
      <IlustrasiMomen
        jenis="tanpa-izin"
        judul="Daftar aset tidak tersedia"
        teks="Peranmu belum mencakup melihat aset. Kamu tetap bisa melapor kerusakan per lokasi."
        aksi={
          <TombolLapangan asChild>
            <Link href={ruteLapangan.pelapor.lapor}>Laporkan kerusakan</Link>
          </TombolLapangan>
        }
        className="pt-10"
      />
    );
  }

  const kata = cari.trim().toLowerCase();
  const tersaring = kata
    ? aset.filter((satu) =>
        [satu.Nama, satu.KodeAset, satu.Kategori, satu.LokasiNama].some((t) =>
          t?.toLowerCase().includes(kata),
        ),
      )
    : aset;
  const sedangDiperbaiki = aset.find(
    (satu) =>
      satu.Kondisi === 'Rusak' && satu.LaporanTerbuka.some((laporan) => laporan.Status === 'Diproses'),
  );

  const gantiLokasi = (baru: LokasiPelapor) => {
    setBukaLokasi(false);
    router.get(ruteLapangan.pelapor.asetDi(baru.Id), {}, { preserveScroll: true, replace: true });
  };

  return (
    <>
      <KartuApung className="flex items-center gap-3 rounded-2xl px-3.5 py-3">
        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-lapangan-biru-50 text-lapangan-biru-600">
          <Search aria-hidden className="size-[18px]" />
        </span>
        <input
          type="search"
          value={cari}
          onChange={(event) => setCari(event.target.value)}
          aria-label="Cari aset di lokasi ini"
          placeholder={lokasi ? `Cari aset di ${lokasi.Nama}` : 'Cari aset'}
          enterKeyHint="search"
          className="min-w-0 flex-1 bg-transparent text-base font-semibold text-lapangan-teks outline-none placeholder:font-medium placeholder:text-lapangan-teks-3"
        />
        <button
          type="button"
          onClick={() => setBukaLokasi(true)}
          className="-my-2 min-h-11 shrink-0 text-sm font-bold text-lapangan-oranye-teks focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
        >
          {lokasi ? 'Ganti lokasi' : 'Pilih lokasi'}
        </button>
      </KartuApung>

      {!lokasi ? (
        <IlustrasiMomen
          ringkas
          ikon="round_pushpin"
          judul="Pilih lokasimu dulu"
          teks="Alat di sekitarmu tampil setelah lokasi dipilih."
          aksi={
            <TombolLapangan ragam="navy" ukuran="kecil" onClick={() => setBukaLokasi(true)}>
              Pilih lokasi
            </TombolLapangan>
          }
        />
      ) : tersaring.length > 0 ? (
        <Kartu className="overflow-hidden">
          <ul aria-label={`Aset di ${lokasi.Nama}`}>
            {tersaring.map((satu) => (
              <BarisAsetLokasi key={satu.Id} aset={satu} />
            ))}
          </ul>
        </Kartu>
      ) : (
        <IlustrasiMomen
          ringkas
          ikon={kata ? 'magnifying_glass_tilted_left' : 'office_building'}
          judul={kata ? 'Aset tidak ditemukan' : 'Belum ada aset di lokasi ini'}
          teks={
            kata
              ? 'Coba kata lain, mis. jenis alatnya.'
              : 'Bila ada yang rusak, kamu tetap bisa melaporkan lokasinya.'
          }
        />
      )}

      {sedangDiperbaiki && (
        <Banner
          warna="biru"
          ringkas
          ikon="construction"
          judul={`${sedangDiperbaiki.Nama} sedang diperbaiki`}
          teks="Sudah ditangani teknisi. Tidak perlu dilaporkan lagi."
        />
      )}

      <LembarLokasi
        buka={bukaLokasi}
        onBukaBerubah={setBukaLokasi}
        pilihan={pilihanLokasi}
        terpilihId={lokasi?.Id ?? null}
        onPilih={gantiLokasi}
      />
    </>
  );
}
