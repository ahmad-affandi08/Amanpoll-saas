import { router, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { AvatarTeknisi } from '@/features/Lapangan/components/pelapor/KartuTeknisi';
import { PitaPelapor } from '@/features/Lapangan/components/pelapor/PitaPelapor';
import { namaDepan } from '@/features/Lapangan/components/pelapor/status';
import { waktuLengkap } from '@/features/Lapangan/components/pelapor/waktu';
import type { FotoSesudahLaporan, PropsKonfirmasiPelapor } from '@/features/Lapangan/types';

const LABEL_NILAI = ['', 'Buruk', 'Kurang', 'Cukup', 'Bagus', 'Sangat bagus'];

interface Jawaban {
  beres: boolean | null;
  nilai: number;
  ulasan: string;
}

/** Konfirmasi selesai (DESIGN.md 36.7 layar 12): hasil kerja, jempol/silang, bintang 1–5, komentar. */
export default function KonfirmasiPelapor() {
  const { props } = usePage<PropsKonfirmasiPelapor>();
  const [jawaban, setJawaban] = useState<Jawaban>({ beres: null, nilai: 0, ulasan: '' });
  const [galat, setGalat] = useState<Record<string, string>>({});

  return (
    <KerangkaLapangan
      judulHalaman={`Konfirmasi ${props.laporan.Nomor}`}
      varian="appbar"
      mode="Pelapor"
      judul="Konfirmasi perbaikan"
      subjudul={<span className="tabular-nums">{props.laporan.Nomor}</span>}
      kembali={ruteLapangan.pelapor.laporanDetail(props.laporan.Id)}
      bilahAksi={<TombolKonfirmasi {...props} jawaban={jawaban} onGalat={setGalat} />}
    >
      <IsiKonfirmasi {...props} jawaban={jawaban} onJawaban={setJawaban} galat={galat} />
    </KerangkaLapangan>
  );
}

function PilihanBesar({
  ikon,
  label,
  pilih,
  nada,
  onClick,
}: {
  ikon: NamaIkon3D;
  label: string;
  pilih: boolean;
  nada: 'hijau' | 'merah';
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      role="radio"
      aria-checked={pilih}
      onClick={onClick}
      className={cn(
        'relative flex flex-col items-center gap-2 rounded-[18px] px-2.5 pt-4 pb-3.5 text-center focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
        pilih
          ? nada === 'hijau'
            ? 'bg-lapangan-hijau-50 shadow-[inset_0_0_0_2px_var(--color-lapangan-hijau-700)]'
            : 'bg-lapangan-merah-50 shadow-[inset_0_0_0_2px_var(--color-lapangan-merah-700)]'
          : 'bg-white shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
      )}
    >
      {pilih && (
        <span
          className={cn(
            'absolute top-2.5 right-2.5 flex size-6 items-center justify-center rounded-full text-white',
            nada === 'hijau' ? 'bg-lapangan-hijau-700' : 'bg-lapangan-merah-700',
          )}
        >
          <Check aria-hidden className="size-3.5" strokeWidth={3} />
        </span>
      )}
      <Ikon3D nama={ikon} ukuran={52} segera />
      <b className="text-[15px] leading-tight font-bold">{label}</b>
    </button>
  );
}

function IsiKonfirmasi({
  laporan,
  foto,
  fotoSesudah,
  jawaban,
  onJawaban,
  galat,
}: PropsKonfirmasiPelapor & {
  jawaban: Jawaban;
  onJawaban: (jawaban: Jawaban) => void;
  galat: Record<string, string>;
}) {
  const teknisi = laporan.Teknisi;
  const pesanGalat = Object.values(galat);

  return (
    <>
      <PitaPelapor />
      {pesanGalat.length > 0 && (
        <PitaInfo nada="merah" ikon="warning" judul="Konfirmasi belum terkirim" teks={pesanGalat.join(' ')} />
      )}

      <Kartu pad>
        <div className="flex items-center gap-3">
          {teknisi ? <AvatarTeknisi nama={teknisi.Nama} /> : <Ikon3D nama="man_mechanic" ukuran={40} />}
          <div className="min-w-0 flex-1">
            <b className="block text-[15px] font-bold">
              {teknisi ? `${namaDepan(teknisi.Nama)} sudah selesai` : 'Tim teknik sudah selesai'}
            </b>
            <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
              {waktuLengkap(laporan.DiresolusikanPada)} · {laporan.Judul}
            </span>
          </div>
        </div>
        <p className="mt-2.5 text-sm leading-[1.45] text-lapangan-teks-2">
          {teknisi?.Ringkasan ??
            'Perbaikan sudah dilaporkan selesai. Cek kondisinya di lokasi, lalu beri tahu kami.'}
        </p>
        {foto.length > 0 && (
          <div className="mt-3 grid grid-cols-2 gap-2">
            {foto.slice(0, 2).map((satu) => (
              <div
                key={satu.BerkasId}
                className="relative h-[76px] overflow-hidden rounded-[14px] bg-lapangan-garis-2"
              >
                <img
                  src={ruteLapangan.pelapor.fotoBerkas(satu.BerkasId)}
                  alt={satu.Nama ?? 'Foto laporan'}
                  loading="lazy"
                  className="size-full object-cover"
                />
                <span className="absolute bottom-1.5 left-1.5 rounded-lg bg-lapangan-navy-900/70 px-2 py-0.5 text-xs font-bold text-white">
                  {satu.Kategori === 'Bukti' ? 'Fotomu' : (satu.Kategori ?? 'Foto')}
                </span>
              </div>
            ))}
          </div>
        )}
        <GaleriSesudah foto={fotoSesudah} />
      </Kartu>

      <h2 className="-mb-0.5 text-[17px] font-bold tracking-[-0.01em]">Apakah sudah beres?</h2>
      <div role="radiogroup" aria-label="Apakah sudah beres?" className="grid grid-cols-2 gap-3">
        <PilihanBesar
          ikon="thumbs_up"
          label="Ya, sudah beres"
          nada="hijau"
          pilih={jawaban.beres === true}
          onClick={() => onJawaban({ ...jawaban, beres: true })}
        />
        <PilihanBesar
          ikon="cross_mark"
          label="Belum, masih bermasalah"
          nada="merah"
          pilih={jawaban.beres === false}
          onClick={() => onJawaban({ ...jawaban, beres: false })}
        />
      </div>

      {jawaban.beres !== false && (
        <Kartu className="px-4 py-3.5">
          <div className="mb-2.5 flex items-center justify-between">
            <h3 className="text-[15px] font-bold">Nilai perbaikannya</h3>
            <span className="text-sm font-bold text-lapangan-oranye-teks">{LABEL_NILAI[jawaban.nilai]}</span>
          </div>
          <div role="radiogroup" aria-label="Nilai perbaikan" className="flex justify-center gap-1.5">
            {[1, 2, 3, 4, 5].map((nilai) => (
              <button
                key={nilai}
                type="button"
                role="radio"
                aria-checked={jawaban.nilai === nilai}
                aria-label={`${nilai} bintang, ${LABEL_NILAI[nilai]}`}
                onClick={() => onJawaban({ ...jawaban, beres: jawaban.beres ?? true, nilai })}
                className="rounded-xl p-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
              >
                <Ikon3D
                  nama="star"
                  ukuran={44}
                  segera
                  className={cn('transition', nilai > jawaban.nilai && 'opacity-30 grayscale')}
                />
              </button>
            ))}
          </div>
        </Kartu>
      )}

      <IsianTiket
        label={jawaban.beres === false ? 'Apa yang masih bermasalah?' : 'Komentar (boleh dilewati)'}
        galat={galat.Ulasan ?? null}
      >
        <AreaTiket
          value={jawaban.ulasan}
          onChange={(event) => onJawaban({ ...jawaban, ulasan: event.target.value })}
          rows={2}
          maxLength={2000}
          placeholder={
            jawaban.beres === false
              ? 'Contoh: lampu masih berkedip di ujung koridor.'
              : 'Contoh: cepat dan rapi, terima kasih!'
          }
        />
      </IsianTiket>
    </>
  );
}

/**
 * Foto Sesudah dari teknisi (PRD 8.20): hanya foto berkategori Sesudah pada perintah kerja
 * dari laporan ini. Ketuk untuk membuka ukuran penuh.
 */
function GaleriSesudah({ foto }: { foto: FotoSesudahLaporan[] }) {
  return (
    <section aria-label="Foto sesudah perbaikan" className="mt-3.5">
      <h3 className="mb-2 text-[13px] font-bold text-lapangan-teks-2">Foto sesudah perbaikan</h3>
      {foto.length > 0 ? (
        <ul className="grid grid-cols-3 gap-2">
          {foto.map((satu, i) => (
            <li key={satu.BerkasId}>
              <a
                href={ruteLapangan.pelapor.fotoBerkas(satu.BerkasId)}
                target="_blank"
                rel="noreferrer"
                className="block aspect-square overflow-hidden rounded-[14px] bg-lapangan-garis-2 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50"
              >
                <img
                  src={ruteLapangan.pelapor.fotoBerkas(satu.BerkasId)}
                  alt={satu.Nama ?? `Foto sesudah ${i + 1}`}
                  loading="lazy"
                  className="size-full object-cover"
                />
              </a>
            </li>
          ))}
        </ul>
      ) : (
        <div className="flex items-center gap-2.5 rounded-[14px] bg-lapangan-latar px-3 py-2.5">
          <Ikon3D nama="camera" ukuran={28} />
          <span className="text-[13px] leading-[1.4] font-medium text-lapangan-teks-3">
            Teknisi belum melampirkan foto sesudah perbaikan.
          </span>
        </div>
      )}
    </section>
  );
}

function TombolKonfirmasi({
  laporan,
  jawaban,
  onGalat,
}: PropsKonfirmasiPelapor & { jawaban: Jawaban; onGalat: (galat: Record<string, string>) => void }) {
  const { daring } = useSinkronisasiOffline();
  const [mengirim, setMengirim] = useState(false);
  const lengkap =
    jawaban.beres === true
      ? jawaban.nilai > 0
      : jawaban.beres === false
        ? jawaban.ulasan.trim() !== ''
        : false;

  const kirim = () => {
    if (!lengkap || !daring) return;
    router.post(
      ruteLapangan.pelapor.konfirmasi(laporan.Id),
      {
        Beres: jawaban.beres === true,
        Rating: jawaban.beres ? jawaban.nilai : null,
        Ulasan: jawaban.ulasan.trim() || null,
        Versi: laporan.Versi,
      },
      {
        onStart: () => setMengirim(true),
        onError: onGalat,
        onNetworkError: () => {
          onGalat({ Kirim: 'Sinyal terputus. Konfirmasi belum terkirim, coba lagi.' });
          return false;
        },
        onFinish: () => setMengirim(false),
      },
    );
  };

  return (
    <TombolLapangan penuh onClick={kirim} disabled={!lengkap || !daring || mengirim}>
      {!daring ? 'Butuh sinyal untuk mengirim' : mengirim ? 'Mengirim…' : 'Kirim Konfirmasi'}
    </TombolLapangan>
  );
}
