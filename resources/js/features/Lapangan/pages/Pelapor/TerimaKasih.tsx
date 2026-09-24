import { Link, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D, WadahIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { durasiRingkas, namaDepan } from '@/features/Lapangan/components/pelapor/status';
import { ikonLaporan } from '@/features/Lapangan/components/pelapor/TiketLaporan';
import { labelHari } from '@/features/Lapangan/components/pelapor/waktu';
import type { PropsLaporanTunggal } from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';

/** Terima kasih (DESIGN.md 36.7 layar 13): penutup dengan ringkasan perjalanan laporan. */
export default function TerimaKasihPelapor() {
  const { props } = usePage<PropsLaporanTunggal>();
  const { laporan } = props;
  const ikon = ikonLaporan(laporan);
  const beresPada = laporan.DiresolusikanPada ?? laporan.DitutupPada;

  return (
    <KerangkaLapangan
      judulHalaman="Terima kasih"
      varian="polos"
      latar="putih"
      mode="Pelapor"
      bilahAksi={
        <TombolLapangan asChild penuh>
          <Link href={ruteLapangan.pelapor.beranda}>Kembali ke Beranda</Link>
        </TombolLapangan>
      }
    >
      <div className="flex flex-col px-2 pt-10 text-center">
        <div className="relative flex justify-center">
          <div className="flex size-[150px] items-center justify-center rounded-full bg-[radial-gradient(circle_at_50%_40%,white_0%,var(--color-lapangan-kuning-50)_70%)] shadow-[0_20px_40px_rgb(15_42_68_/_0.1)]">
            <Ikon3D nama="star" ukuran={104} segera />
          </div>
          <span className="absolute top-[-8px] left-[calc(50%+46px)]">
            <Ikon3D nama="sparkles" ukuran={54} segera />
          </span>
        </div>
        <h1 className="mt-7 text-[25px] font-extrabold tracking-[-0.01em]">
          Terima kasih, {namaDepan(props.auth.pengguna?.Nama) || 'kamu'}!
        </h1>
        <p className="mt-2 text-[15px] text-lapangan-teks-3">
          Laporan sudah ditutup. Penilaianmu membantu tim teknik bekerja lebih baik.
        </p>

        <article className="mt-7 rounded-[20px] bg-white text-left ring-[1.5px] ring-lapangan-garis ring-inset">
          <div className="flex items-center gap-3 px-4 pt-4 pb-3">
            <WadahIkon3D nama={ikon.ikon} tint={ikon.tint} className="size-11 rounded-[14px]" />
            <div className="min-w-0 flex-1">
              <h2 className="text-[15px] leading-[1.3] font-bold">{laporan.Judul}</h2>
              <span className="text-[13px] font-semibold text-lapangan-teks-3">{laporan.Nomor}</span>
            </div>
            <ChipStatus warna="hijau" ikon={Check}>
              Ditutup
            </ChipStatus>
          </div>
          <Sobekan latarLekuk="putih" bergaris />
          <div className="px-4 pt-3 pb-4">
            <RuteJam
              kiri={{
                jam: jamPendek(laporan.DilaporkanPada),
                label: labelHari('Dilaporkan', laporan.DilaporkanPada, 'Dilapor'),
              }}
              kanan={{ jam: jamPendek(beresPada), label: labelHari('Beres', beresPada) }}
              ikon="hourglass_done"
              label={durasiRingkas(laporan.DilaporkanPada, beresPada) ?? undefined}
            />
          </div>
        </article>

        {laporan.Rating !== null && (
          <div className="mt-3.5 rounded-[20px] px-4 py-3.5 text-left ring-[1.5px] ring-lapangan-garis ring-inset">
            <div className="flex items-center justify-between">
              <h3 className="text-[15px] font-bold">Penilaianmu</h3>
              <span role="img" aria-label={`${laporan.Rating} dari 5 bintang`} className="flex gap-0.5">
                {[1, 2, 3, 4, 5].map((nilai) => (
                  <Ikon3D
                    key={nilai}
                    nama="star"
                    ukuran={22}
                    className={cn(
                      'drop-shadow-none',
                      nilai > (laporan.Rating ?? 0) && 'opacity-30 grayscale',
                    )}
                  />
                ))}
              </span>
            </div>
            {laporan.Ulasan && <p className="mt-1.5 text-sm text-lapangan-teks-2">“{laporan.Ulasan}”</p>}
          </div>
        )}

        <Link
          href={ruteLapangan.pelapor.lapor}
          className="mt-5 inline-flex min-h-11 items-center justify-center text-sm font-bold text-lapangan-oranye-teks"
        >
          Ada kerusakan lain? Lapor sekarang
        </Link>
      </div>
    </KerangkaLapangan>
  );
}
