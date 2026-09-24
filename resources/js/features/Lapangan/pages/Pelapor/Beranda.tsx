import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { JudulBagian, KartuApung } from '@/features/Lapangan/components/Kartu';
import { MenuGrid3D, type ItemMenu3D } from '@/features/Lapangan/components/MenuGrid3D';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { PitaPelapor } from '@/features/Lapangan/components/pelapor/PitaPelapor';
import { namaDepan } from '@/features/Lapangan/components/pelapor/status';
import { TiketLaporan } from '@/features/Lapangan/components/pelapor/TiketLaporan';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { KategoriLaporan, PropsBerandaPelapor } from '@/features/Lapangan/types';

/** Kategori di grid kartu apung (papan: 8 ikon, yang terakhir "Lainnya"). */
const JUMLAH_GRID = 8;

function itemKategori(semua: KategoriLaporan[]): ItemMenu3D[] {
  // Kategori bernama "Lainnya" selalu di ujung, seperti papan acuan.
  const kategori = [...semua].sort((a, b) => Number(/^lain/i.test(a.Nama)) - Number(/^lain/i.test(b.Nama)));
  const penuh = kategori.length > JUMLAH_GRID;
  const tampil = penuh ? kategori.slice(0, JUMLAH_GRID - 1) : kategori;
  const item: ItemMenu3D[] = tampil.map((satu) => ({
    label: satu.Nama,
    ...ikonKategori(satu.Nama),
    href: ruteLapangan.pelapor.laporDengan({ kategori: satu.Id }),
  }));

  if (penuh) {
    item.push({ label: 'Lainnya', ikon: 'toolbox', tint: 'merah', href: ruteLapangan.pelapor.lapor });
  }

  return item;
}

/** Beranda Pelapor (DESIGN.md 36.7 layar 02): satu aksi besar, jenis masalah, laporan aktif. */
export default function BerandaPelapor() {
  const { props } = usePage<PropsBerandaPelapor>();
  const nama = namaDepan(props.auth.pengguna?.Nama) || 'kamu';

  return (
    <KerangkaLapangan
      judulHalaman="Beranda"
      navAktif="beranda"
      sapaan={null}
      statusSinkron={false}
      judul={
        <span className="flex items-center gap-1.5">
          Halo, {nama}
          <Ikon3D nama="waving_hand" ukuran={24} segera />
        </span>
      }
      subjudul={
        props.lokasi ? (
          <span className="flex items-center gap-1">
            <MapPin aria-hidden className="size-3.5 shrink-0" />
            <span className="truncate">{props.lokasi.Label}</span>
          </span>
        ) : undefined
      }
    >
      <IsiBeranda {...props} />
    </KerangkaLapangan>
  );
}

function IsiBeranda({ kategori, laporanAktif, jumlahAktif }: PropsBerandaPelapor) {
  return (
    <>
      <KartuApung className="overflow-hidden">
        <Link
          href={ruteLapangan.pelapor.lapor}
          className="flex items-center gap-3.5 border-b-[1.5px] border-lapangan-garis-2 bg-[linear-gradient(120deg,var(--color-lapangan-oranye-50),white_75%)] px-4 py-3.5 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50 focus-visible:ring-inset"
        >
          <span className="flex size-16 shrink-0 items-center justify-center rounded-[20px] bg-white shadow-[0_4px_12px_rgb(194_83_10_/_0.15)]">
            <Ikon3D nama="megaphone" ukuran={46} segera />
          </span>
          <span className="min-w-0 flex-1">
            <b className="block text-lg leading-tight font-bold tracking-[-0.01em]">Laporkan Kerusakan</b>
            <span className="mt-0.5 block text-[13px] leading-[1.35] font-medium text-lapangan-teks-3">
              Pindai QR di alat atau pilih dari daftar
            </span>
          </span>
          <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lapangan-oranye-700 text-white shadow-lapangan-oranye">
            <ArrowRight aria-hidden className="size-5" />
          </span>
        </Link>
        {kategori.length > 0 && (
          <>
            <p className="px-4 pt-3 text-sm font-bold text-lapangan-teks-2">Atau pilih jenis masalah</p>
            <MenuGrid3D
              item={itemKategori(kategori)}
              ukuran="ringkas"
              label="Jenis masalah"
              className="-mt-1"
            />
          </>
        )}
      </KartuApung>

      <PitaPelapor />

      <JudulBagian
        judul="Laporan aktif"
        tautan={jumlahAktif > 0 ? { label: 'Lihat semua', href: ruteLapangan.pelapor.laporan } : undefined}
      />
      {laporanAktif.length > 0 ? (
        laporanAktif.map((laporan) => <TiketLaporan key={laporan.Id} laporan={laporan} ragam="beranda" />)
      ) : (
        <IlustrasiMomen
          ringkas
          ikon="check_mark_button"
          judul="Tidak ada laporan aktif"
          teks="Semua aman. Bila ada yang rusak, laporkan dari sini, kami pantau sampai beres."
          aksi={
            <TombolLapangan asChild ragam="garis" ukuran="kecil">
              <Link href={ruteLapangan.pelapor.laporan}>Lihat laporan sebelumnya</Link>
            </TombolLapangan>
          }
        />
      )}
    </>
  );
}
