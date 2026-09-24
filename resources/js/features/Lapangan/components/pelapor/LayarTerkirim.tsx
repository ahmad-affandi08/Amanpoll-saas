import { CloudOff, Copy, CopyCheck } from 'lucide-react';
import { useState } from 'react';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { jamPendek } from '@/features/Lapangan/waktu';

interface PropsLayarTerkirim {
  /** Nama depan pelapor untuk ucapan terima kasih. */
  nama: string;
  /** Nomor keluhan; `null` bila laporan masih di antrean offline. */
  nomor: string | null;
  dikirimPada: string | null;
  targetDitinjau: string | null;
  subjek: string;
  ikonSubjek: NamaIkon3D;
}

/**
 * Isi layar "Laporan terkirim" (DESIGN.md 36.7 layar 08): nomor laporan besar seperti
 * kode booking, rute jam kirim → target ditinjau, dan apa selanjutnya. Dipakai
 * halaman Terkirim (online) dan langkah lapor yang tersimpan offline.
 */
export function LayarTerkirim({
  nama,
  nomor,
  dikirimPada,
  targetDitinjau,
  subjek,
  ikonSubjek,
}: PropsLayarTerkirim) {
  const [tersalin, setTersalin] = useState(false);
  const offline = nomor === null;

  const salin = () => {
    if (!nomor || !navigator.clipboard) return;
    void navigator.clipboard.writeText(nomor).then(() => setTersalin(true));
  };

  return (
    <div className="flex flex-col pt-6">
      <div className="relative flex justify-center">
        <div className="gradien-ilustrasi-lapangan flex size-28 items-center justify-center rounded-full shadow-lapangan-apung">
          <Ikon3D nama={offline ? 'mobile_phone' : 'check_mark_button'} ukuran={72} segera />
        </div>
        <span className="absolute top-[-8px] left-[calc(50%+30px)] rotate-[8deg]">
          <Ikon3D nama={offline ? 'satellite_antenna' : 'party_popper'} ukuran={48} segera />
        </span>
      </div>
      <h1 className="mt-3.5 text-center text-2xl font-bold tracking-[-0.01em]">
        {offline ? 'Laporan tersimpan!' : 'Laporan terkirim!'}
      </h1>
      <p className="mt-1 text-center text-[14.5px] text-lapangan-teks-3">
        {offline
          ? `Terima kasih, ${nama}. Laporan dikirim otomatis begitu sinyal kembali.`
          : `Terima kasih, ${nama}. Tim teknik segera meninjaunya.`}
      </p>

      <div className="mt-4 rounded-[20px] bg-white ring-[1.5px] ring-lapangan-garis ring-inset">
        <div className="flex items-center gap-3 px-4 pt-4 pb-2">
          <div className="min-w-0 flex-1">
            <span className="block text-[13px] font-medium text-lapangan-teks-3">Kode laporan</span>
            {offline ? (
              <span className="block text-lg leading-tight font-bold text-lapangan-kuning-700">
                Menunggu sinyal
              </span>
            ) : (
              <span className="block text-2xl leading-tight font-bold tracking-[0.02em] tabular-nums">
                {nomor}
              </span>
            )}
          </div>
          {!offline && (
            <button
              type="button"
              onClick={salin}
              aria-label={tersalin ? 'Kode tersalin' : 'Salin kode laporan'}
              className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lapangan-biru-50 text-lapangan-biru-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
            >
              {tersalin ? (
                <CopyCheck aria-hidden className="size-5" />
              ) : (
                <Copy aria-hidden className="size-5" />
              )}
            </button>
          )}
        </div>
        <Sobekan latarLekuk="putih" bergaris />
        <div className="px-4 pt-1.5 pb-4">
          {targetDitinjau ? (
            <RuteJam
              kiri={{ jam: jamPendek(dikirimPada), label: 'Dikirim' }}
              kanan={{ jam: jamPendek(targetDitinjau), label: 'Target ditinjau' }}
              ikon={ikonSubjek}
              label={subjek}
            />
          ) : (
            <div className="flex items-center gap-3">
              <Ikon3D nama={ikonSubjek} ukuran={32} />
              <div className="min-w-0 flex-1">
                <b className="block truncate text-sm font-bold">{subjek}</b>
                <span className="text-[13px] text-lapangan-teks-3">
                  {offline ? 'Disimpan' : 'Dikirim'} {jamPendek(dikirimPada)}
                </span>
              </div>
            </div>
          )}
        </div>
      </div>

      <h2 className="mt-4 mb-2.5 text-[15px] font-bold tracking-[-0.01em]">Apa selanjutnya?</h2>
      <ol className="flex flex-col">
        {[
          {
            judul: 'Tim teknik meninjau laporanmu',
            teks: offline ? 'Setelah laporan terkirim' : 'Kamu dikabari lewat notifikasi',
          },
          { judul: 'Teknisi ditugaskan dan datang', teks: 'Pantau perjalanannya di Laporan Saya' },
          { judul: 'Kamu konfirmasi saat sudah beres', teks: 'Cukup satu ketukan dari HP ini' },
        ].map((satu, i, semua) => (
          <li key={satu.judul} className="relative grid grid-cols-[28px_1fr] gap-3 pb-3.5 last:pb-0">
            {i < semua.length - 1 && (
              <span
                aria-hidden
                className="absolute top-7 bottom-0.5 left-[13px] border-l-2 border-dashed border-lapangan-garis"
              />
            )}
            <span className="flex size-7 items-center justify-center rounded-full bg-lapangan-biru-50 text-[13px] font-bold text-lapangan-biru-600">
              {i + 1}
            </span>
            <div>
              <b className="block pt-1 text-[14.5px] leading-[1.3] font-bold">{satu.judul}</b>
              <span className="block text-[13px] font-medium text-lapangan-teks-3">{satu.teks}</span>
            </div>
          </li>
        ))}
      </ol>

      <div className="mt-3.5 flex items-center gap-2.5 rounded-[14px] bg-lapangan-latar px-3 py-2.5">
        <CloudOff aria-hidden className="size-[18px] shrink-0 text-lapangan-teks-2" />
        <span className="text-[13px] leading-[1.35] text-lapangan-teks-2">
          {offline
            ? 'Laporan tersimpan di HP ini. Foto bisa ditambahkan dari Lacak laporan setelah online.'
            : 'Sinyal hilang? Laporan tersimpan dan akan dikirim otomatis saat online.'}
        </span>
      </div>
    </div>
  );
}
