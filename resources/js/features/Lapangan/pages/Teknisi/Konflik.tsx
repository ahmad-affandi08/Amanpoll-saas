import { Link, router, usePage } from '@inertiajs/react';
import { ArchiveRestore } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';
import { Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { PropsKonflikTeknisi } from '@/features/Lapangan/types';
import { jamPendek, kelompokHari } from '@/features/Lapangan/waktu';
import { labelStatusTiket } from '@/features/Lapangan/components/teknisi/KartuTiketTeknisi';
import {
  BilahTetap,
  KELAS_ISI_BERBILAH,
  usePeringatanOffline,
} from '@/features/Lapangan/components/teknisi/umum';

type Versi = 'saya' | 'server';

const LABEL_OPERASI: Record<string, string> = {
  'PerintahKerja.ResponsPenugasan': 'Respons penugasan',
  'PerintahKerja.UbahStatus': 'Status',
  'DaftarPeriksa.SimpanJawaban': 'Jawaban checklist',
  'DaftarPeriksa.Finalisasi': 'Checklist',
};

/** "hari ini 11.05", "kemarin 16.40", atau "22 Sep 11.05". */
function kapan(nilai: string | null): string {
  if (!nilai) return '';
  const hari = kelompokHari(nilai);
  return `${hari === 'Hari ini' || hari === 'Kemarin' ? hari.toLowerCase() : hari} ${jamPendek(nilai)}`;
}

function teks(nilai: unknown): string | null {
  return typeof nilai === 'string' && nilai.trim() !== '' ? nilai : null;
}

/** Pilih versi (DESIGN §36.6 layar 18): perubahan di HP vs perubahan di server, lengkap dengan siapa dan kapan. */
export default function KonflikTeknisi(props: PropsKonflikTeknisi) {
  return (
    <KerangkaLapangan
      varian="appbar"
      judulHalaman="Pilih versi"
      judul="Pilih versi"
      subjudul={props.tiket ? `${props.tiket.Nomor} · ${props.tiket.Judul}` : 'Perubahan offline'}
      kembali={ruteLapangan.akun}
      panjang
      classNameIsi={KELAS_ISI_BERBILAH}
    >
      <IsiKonflik {...props} />
    </KerangkaLapangan>
  );
}

function BarisBeda({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="grid grid-cols-[70px_1fr] items-start gap-2.5 text-sm">
      <dt className="pt-[3px] text-[12.5px] font-semibold text-lapangan-teks-3">{label}</dt>
      <dd className="leading-snug font-semibold">{children}</dd>
    </div>
  );
}

function KartuVersi({
  terpilih,
  onPilih,
  judul,
  oleh,
  ikon,
  status,
  catatan,
}: {
  terpilih: boolean;
  onPilih: () => void;
  judul: string;
  oleh: string;
  ikon: NamaIkon3D;
  status: string | null;
  catatan: string | null;
}) {
  return (
    <button
      type="button"
      role="radio"
      aria-checked={terpilih}
      onClick={onPilih}
      className={cn(
        'w-full rounded-[20px] bg-white px-4 py-3.5 text-left transition-shadow focus-visible:outline-none',
        terpilih
          ? 'shadow-[0_6px_20px_rgb(42_123_176_/_0.18),inset_0_0_0_2.5px_var(--color-lapangan-biru-500)]'
          : 'shadow-[0_1px_2px_rgb(15_42_68_/_0.04),inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
      )}
    >
      <span className="flex items-center gap-2.5">
        <span
          aria-hidden
          className={cn(
            'size-6 shrink-0 rounded-full',
            terpilih
              ? 'shadow-[inset_0_0_0_7px_var(--color-lapangan-biru-500)]'
              : 'shadow-[inset_0_0_0_2px_rgb(91_103_115_/_0.45)]',
          )}
        />
        <span className="min-w-0 flex-1">
          <b className="block text-lg leading-tight font-bold">{judul}</b>
          <span className="block truncate text-[13px] text-lapangan-teks-3">{oleh}</span>
        </span>
        <Ikon3D nama={ikon} ukuran={36} />
      </span>
      <dl className="mt-3 flex flex-col gap-2">
        {status && (
          <BarisBeda label="Status">
            <ChipStatus status={status}>{labelStatusTiket(status)}</ChipStatus>
          </BarisBeda>
        )}
        <BarisBeda label="Catatan">
          {catatan ?? <span className="text-lapangan-teks-3">Tanpa catatan</span>}
        </BarisBeda>
      </dl>
    </button>
  );
}

function IsiKonflik({ antrian: baris, tiket, perubahanServer }: PropsKonflikTeknisi) {
  usePeringatanOffline();
  const { auth } = usePage<PropsKonflikTeknisi>().props;
  const { antrian, daring, selesaikanKonflik } = useSinkronisasiOffline();
  const [pilihan, setPilihan] = useState<Versi>('saya');
  const [memproses, setMemproses] = useState(false);
  const lokal = antrian.find((satu) => satu.KunciOperasi === baris.KunciOperasi);
  const masihKonflik = (lokal?.Status ?? baris.Status) === 'Konflik';
  const konflik = lokal?.Konflik ?? baris.Konflik;
  const muatan = lokal?.MuatanData ?? {};

  if (!masihKonflik) {
    return (
      <KartuApung>
        <IlustrasiMomen
          jenis="sukses"
          ikon="check_mark_button"
          judul="Sudah beres"
          teks="Versi untuk perubahan ini sudah dipilih. Tidak ada yang perlu dilakukan lagi."
          aksi={
            <TombolLapangan asChild penuh>
              <Link href={tiket ? ruteLapangan.teknisi.tugasDetail(tiket.Id) : ruteLapangan.akun}>
                Kembali
              </Link>
            </TombolLapangan>
          }
        />
      </KartuApung>
    );
  }

  const namaSaya = auth.pengguna?.Nama?.split(' ')[0] ?? 'Kamu';
  const waktuSaya = lokal?.DibuatPada ?? baris.DiterimaPada;
  const statusSaya =
    teks(konflik?.NilaiKlien?.Status) ??
    teks(muatan.Status) ??
    (teks(muatan.Respons) === 'Terima' ? 'Diterima' : null);
  const statusServer = perubahanServer?.Status ?? teks(konflik?.NilaiServer?.Status);
  const oleh = [perubahanServer?.Oleh, perubahanServer?.Jabatan].filter(Boolean).join(', ') || 'Server';

  const putuskan = async () => {
    setMemproses(true);
    try {
      await selesaikanKonflik(baris.KunciOperasi, pilihan === 'saya' ? 'TerapkanUlang' : 'PakaiServer');
      toast.success(
        pilihan === 'saya'
          ? 'Versimu dipakai dan diterapkan ulang.'
          : 'Versi server dipakai. Perubahanmu tetap tercatat di riwayat.',
      );
      router.visit(tiket ? ruteLapangan.teknisi.tugasDetail(tiket.Id) : ruteLapangan.akun);
    } catch {
      toast.error('Belum bisa disimpan. Coba lagi saat sinyal stabil.');
    } finally {
      setMemproses(false);
    }
  };

  return (
    <>
      <KartuApung pad className="flex items-start gap-3.5">
        <Ikon3D nama="handshake" ukuran={52} />
        <div className="min-w-0 flex-1">
          <h2 className="text-[17px] leading-snug font-bold">Tiket ini diubah di dua tempat</h2>
          <p className="mt-1 text-sm text-lapangan-teks-2">
            {perubahanServer?.Oleh
              ? `Kamu mengubahnya saat offline, ${perubahanServer.Oleh} juga mengubahnya dari kantor.`
              : (konflik?.Pesan ??
                'Kamu mengubahnya saat offline, dan tiket yang sama juga diubah dari kantor.')}{' '}
            Pilih versi yang dipakai.
          </p>
        </div>
      </KartuApung>

      <div role="radiogroup" aria-label="Versi yang dipakai" className="flex flex-col gap-3">
        <KartuVersi
          terpilih={pilihan === 'saya'}
          onPilih={() => setPilihan('saya')}
          judul="Versi saya"
          oleh={`${namaSaya} · ${kapan(waktuSaya)} (offline)`}
          ikon="mobile_phone"
          status={statusSaya}
          catatan={
            teks(muatan.Catatan) ??
            teks(muatan.Isi) ??
            (lokal ? lokal.Label : (LABEL_OPERASI[baris.Operasi] ?? baris.Operasi))
          }
        />
        <KartuVersi
          terpilih={pilihan === 'server'}
          onPilih={() => setPilihan('server')}
          judul="Versi server"
          oleh={perubahanServer ? `${oleh} · ${kapan(perubahanServer.Pada)}` : oleh}
          ikon="desktop_computer"
          status={statusServer}
          catatan={perubahanServer?.Catatan ?? null}
        />
      </div>

      {tiket && (
        <Kartu pad>
          <h3 className="text-sm font-semibold text-lapangan-teks-3">Sama di kedua versi</h3>
          <dl className="mt-3 grid grid-cols-2 gap-2.5">
            <div className="rounded-[14px] bg-lapangan-latar px-3 py-2.5">
              <dt className="text-xs font-semibold text-lapangan-teks-3">Prioritas</dt>
              <dd className="text-sm font-bold">{tiket.Prioritas}</dd>
            </div>
            <div className="rounded-[14px] bg-lapangan-latar px-3 py-2.5">
              <dt className="text-xs font-semibold text-lapangan-teks-3">Aset</dt>
              <dd className="truncate text-sm font-bold">{tiket.Aset?.Nama ?? '—'}</dd>
            </div>
          </dl>
        </Kartu>
      )}

      <p className="flex items-start gap-2.5 px-1 text-[13px] leading-normal text-lapangan-teks-2">
        <ArchiveRestore aria-hidden className="mt-px size-[18px] shrink-0 text-lapangan-biru-600" />
        Versi yang tidak dipilih tetap tersimpan di riwayat tiket.
      </p>

      {!daring && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul="Butuh sinyal untuk memilih"
          teks="Keputusanmu dikirim langsung ke server."
        />
      )}

      <BilahTetap>
        <TombolLapangan penuh disabled={!daring || memproses} onClick={() => void putuskan()}>
          {memproses ? 'Menyimpan…' : pilihan === 'saya' ? 'Pakai versi saya' : 'Pakai versi server'}
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}
