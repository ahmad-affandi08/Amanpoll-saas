import { Link, usePage } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useState } from 'react';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { PanelTabPil, TabPil } from '@/features/Lapangan/components/TabPil';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { PitaPelapor } from '@/features/Lapangan/components/pelapor/PitaPelapor';
import { diTabLaporan, type TabLaporan } from '@/features/Lapangan/components/pelapor/status';
import { TiketLaporan } from '@/features/Lapangan/components/pelapor/TiketLaporan';
import type { PropsLaporanPelapor } from '@/features/Lapangan/types';
import type { NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';

const KOSONG: Record<TabLaporan, { ikon: NamaIkon3D; judul: string; teks: string }> = {
  aktif: {
    ikon: 'check_mark_button',
    judul: 'Tidak ada laporan aktif',
    teks: 'Semua laporanmu sudah ditangani. Ada yang rusak lagi? Laporkan dari tombol Lapor.',
  },
  konfirmasi: {
    ikon: 'thumbs_up',
    judul: 'Tidak ada yang perlu dikonfirmasi',
    teks: 'Saat teknisi selesai, laporannya muncul di sini untuk kamu cek.',
  },
  selesai: {
    ikon: 'trophy',
    judul: 'Belum ada laporan selesai',
    teks: 'Laporan yang sudah ditutup akan tersimpan di sini.',
  },
};

/** Laporan Saya (DESIGN.md 36.7 layar 09): tab pil Aktif / Perlu konfirmasi / Selesai. */
export default function LaporanPelapor() {
  const { props } = usePage<PropsLaporanPelapor>();
  const [cariBuka, setCariBuka] = useState(false);

  return (
    <KerangkaLapangan
      judulHalaman="Laporan Saya"
      varian="appbar"
      mode="Pelapor"
      navAktif="laporan"
      navBawah
      kembali={false}
      panjang
      judul={<span className="pl-1 text-[22px] font-extrabold">Laporan Saya</span>}
      subjudul={<span className="pl-1">{props.lokasi?.Label ?? 'Semua laporanmu'}</span>}
      aksiKanan={
        <TombolAppbar
          label={cariBuka ? 'Tutup pencarian' : 'Cari laporan'}
          ikon={cariBuka ? X : Search}
          onClick={() => setCariBuka((buka) => !buka)}
        />
      }
    >
      <IsiLaporan {...props} cariBuka={cariBuka} />
    </KerangkaLapangan>
  );
}

function tabAwal(url: string): TabLaporan {
  const tab = new URLSearchParams(url.split('?')[1] ?? '').get('tab');
  return tab === 'konfirmasi' || tab === 'selesai' ? tab : 'aktif';
}

function IsiLaporan({ laporan, jumlah, cariBuka }: PropsLaporanPelapor & { cariBuka: boolean }) {
  const { url } = usePage();
  const [tab, setTab] = useState<TabLaporan>(() => tabAwal(url));
  const [cari, setCari] = useState('');
  const kata = cari.trim().toLowerCase();
  const tampil = laporan.filter(
    (satu) =>
      diTabLaporan(satu.Status, tab) &&
      (!kata || [satu.Judul, satu.Nomor, satu.Aset?.Nama].some((teks) => teks?.toLowerCase().includes(kata))),
  );
  const kosong = KOSONG[tab];

  return (
    <>
      <TabPil<TabLaporan>
        label="Kelompok laporan"
        idAwalan="laporan-saya"
        aktif={tab}
        onGanti={setTab}
        className="relative z-10 -mt-14 shadow-lapangan-apung [&>[role=tab]]:flex-auto [&>[role=tab]]:px-2.5 [&>[role=tab]]:whitespace-nowrap"
        item={[
          { kunci: 'aktif', label: 'Aktif', jumlah: jumlah.Aktif },
          { kunci: 'konfirmasi', label: 'Perlu konfirmasi', jumlah: jumlah.PerluKonfirmasi },
          { kunci: 'selesai', label: 'Selesai', jumlah: jumlah.Selesai },
        ]}
      />

      {cariBuka && (
        <IsianTiket label="Cari laporan" ikon={Search}>
          <MasukanTiket
            value={cari}
            onChange={(event) => setCari(event.target.value)}
            placeholder="Nomor, alat, atau masalah"
            autoFocus
            enterKeyHint="search"
          />
        </IsianTiket>
      )}

      <PitaPelapor />

      <PanelTabPil idAwalan="laporan-saya" kunci={tab}>
        {tampil.length > 0 ? (
          tampil.map((satu) => <TiketLaporan key={satu.Id} laporan={satu} />)
        ) : (
          <IlustrasiMomen
            ringkas
            ikon={kata ? 'magnifying_glass_tilted_left' : kosong.ikon}
            judul={kata ? 'Laporan tidak ditemukan' : kosong.judul}
            teks={kata ? 'Coba nomor laporan atau nama alatnya.' : kosong.teks}
            aksi={
              !kata && tab === 'aktif' ? (
                <TombolLapangan asChild ukuran="kecil">
                  <Link href={ruteLapangan.pelapor.lapor}>Laporkan kerusakan</Link>
                </TombolLapangan>
              ) : undefined
            }
          />
        )}
      </PanelTabPil>
    </>
  );
}
