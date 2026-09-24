import { Link, usePage } from '@inertiajs/react';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Tiket } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { PropsKonfirmasiPenerimaHasil } from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';

/** Layar sesudah penerima menjawab lewat pindai QR (PRD 8.22, cara 2). */
export default function KonfirmasiPenerimaHasil() {
  const { props } = usePage<PropsKonfirmasiPenerimaHasil>();
  const diterima = props.hasil === 'Diterima';

  return (
    <KerangkaLapangan
      varian="polos"
      latar="putih"
      judulHalaman={diterima ? 'Pekerjaan diterima' : 'Pekerjaan dikembalikan'}
      bilahAksi={
        <TombolLapangan asChild penuh>
          <Link href="/">Selesai</Link>
        </TombolLapangan>
      }
    >
      <IlustrasiMomen
        jenis={diterima ? 'sukses' : 'kosong'}
        ikon={diterima ? 'handshake' : 'hammer_and_wrench'}
        className="pt-6"
        pendamping={diterima ? [{ nama: 'sparkles', letak: 'kiri-atas' }] : undefined}
        judul={diterima ? 'Terima kasih, pekerjaan diterima' : 'Pekerjaan dikembalikan ke teknisi'}
        teks={
          diterima
            ? 'Konfirmasi dan tanda tanganmu tercatat. Koordinator melanjutkan verifikasi.'
            : 'Teknisi sudah diberi tahu apa yang masih bermasalah dan akan kembali memeriksanya.'
        }
      />
      <Tiket
        bergaris
        latarLekuk="putih"
        atas={
          <>
            <strong className="text-lg font-bold tabular-nums">{props.pekerjaan.Nomor}</strong>
            <p className="mt-0.5 text-sm text-lapangan-teks-3">{props.pekerjaan.Judul}</p>
          </>
        }
        bawah={
          <p className="text-[13px] text-lapangan-teks-3">
            Dijawab pukul{' '}
            <b className="font-bold text-lapangan-teks tabular-nums">{jamPendek(props.dikonfirmasiPada)}</b>
            {props.pekerjaan.Lokasi ? ` · ${props.pekerjaan.Lokasi}` : ''}
          </p>
        }
      />
    </KerangkaLapangan>
  );
}
