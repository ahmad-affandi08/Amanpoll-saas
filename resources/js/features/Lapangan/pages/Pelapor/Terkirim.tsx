import { Link, usePage } from '@inertiajs/react';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { LayarTerkirim } from '@/features/Lapangan/components/pelapor/LayarTerkirim';
import { namaDepan } from '@/features/Lapangan/components/pelapor/status';
import { ikonLaporan } from '@/features/Lapangan/components/pelapor/TiketLaporan';
import type { PropsLaporanTunggal } from '@/features/Lapangan/types';

/** Laporan terkirim (DESIGN.md 36.7 layar 08). */
export default function TerkirimPelapor() {
  const { props } = usePage<PropsLaporanTunggal>();
  const { laporan } = props;

  return (
    <KerangkaLapangan
      judulHalaman={`Laporan ${laporan.Nomor} terkirim`}
      varian="polos"
      latar="putih"
      bilahAksi={
        <>
          <TombolLapangan asChild ragam="garis" className="px-[18px]">
            <Link href={ruteLapangan.pelapor.beranda}>Beranda</Link>
          </TombolLapangan>
          <TombolLapangan asChild className="flex-1">
            <Link href={ruteLapangan.pelapor.laporanDetail(laporan.Id)}>Lacak Laporan</Link>
          </TombolLapangan>
        </>
      }
    >
      <LayarTerkirim
        nama={namaDepan(props.auth.pengguna?.Nama) || 'kamu'}
        nomor={laporan.Nomor}
        dikirimPada={laporan.DilaporkanPada}
        targetDitinjau={laporan.BatasResponsPada}
        subjek={laporan.Aset?.Nama ?? laporan.LokasiNama ?? 'Lokasi'}
        ikonSubjek={laporan.Aset ? ikonLaporan(laporan).ikon : 'round_pushpin'}
      />
    </KerangkaLapangan>
  );
}
