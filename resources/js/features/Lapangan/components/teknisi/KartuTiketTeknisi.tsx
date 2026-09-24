import { Link } from '@inertiajs/react';
import { ChevronRight, ClockAlert, Hourglass, Siren } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { ChipStatus, labelStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D } from '@/components/shared/Ikon3D';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { JudulTiket, Tiket } from '@/features/Lapangan/components/Tiket';
import { ikonKategori } from '@/components/shared/ikon-kategori';
import type { TiketTeknisi } from '@/features/Lapangan/types';
import { lamaTerlambat, ruteTiket, teksLokasi } from '@/features/Lapangan/components/teknisi/waktuTiket';

/** Label status tiket untuk teknisi ("Menunggu Verifikasi", "Ditugaskan"). */
export function labelStatusTiket(status: string): string {
  return labelStatus(status).replace(/^\w/, (huruf) => huruf.toUpperCase());
}

/** Chip prioritas: Kritis bertanda sirene; Normal/Rendah abu. */
export function ChipPrioritas({ prioritas, ukuran }: { prioritas: string; ukuran?: 'normal' | 'kecil' }) {
  return (
    <ChipStatus status={prioritas} ikon={prioritas === 'Kritis' ? Siren : undefined} ukuran={ukuran}>
      {prioritas}
    </ChipStatus>
  );
}

/** Deretan chip kepala tiket: terlambat atau prioritas, lalu status bila diminta. */
export function ChipKepalaTiket({
  tiket,
  tampilStatus = true,
}: {
  tiket: TiketTeknisi;
  tampilStatus?: boolean;
}) {
  const lama = lamaTerlambat(tiket);

  return (
    <div className="flex min-w-0 flex-wrap items-center gap-1.5">
      {lama ? (
        <ChipStatus warna="merah" ikon={ClockAlert}>
          Terlambat {lama}
        </ChipStatus>
      ) : null}
      <ChipPrioritas prioritas={tiket.Prioritas} />
      {tampilStatus && !lama ? (
        <ChipStatus status={tiket.Status}>{labelStatusTiket(tiket.Status)}</ChipStatus>
      ) : null}
    </div>
  );
}

/** Baris aset di bagian bawah tiket: ikon 3D jenis aset, nama, lokasi. */
export function BarisAsetTiket({ tiket, kanan }: { tiket: TiketTeknisi; kanan?: ReactNode }) {
  const ikon = ikonKategori(tiket.Aset?.Kategori ?? tiket.Aset?.Nama ?? tiket.Judul);
  const lokasi = teksLokasi(tiket.Aset?.Lokasi ?? tiket.Lokasi);

  return (
    <div className="flex items-center gap-3">
      <Ikon3D nama={ikon.ikon} ukuran={36} />
      <div className="min-w-0 flex-1">
        <b className="block truncate text-sm leading-snug font-bold text-lapangan-teks">
          {tiket.Aset?.Nama ?? 'Tanpa aset'}
        </b>
        {lokasi && <span className="block truncate text-[13px] text-lapangan-teks-3">{lokasi}</span>}
      </div>
      {kanan}
    </div>
  );
}

interface PropsKartuTiketTeknisi {
  tiket: TiketTeknisi;
  /** Tujuan saat kartu diketuk. Tanpa `aksi`, chevron kanan ditampilkan. */
  href?: string;
  /** Tombol di kanan baris aset (mis. "Mulai"); menggantikan chevron. */
  aksi?: ReactNode;
  tampilStatus?: boolean;
  /** Isi tambahan di bawah baris aset. */
  catatan?: ReactNode;
  className?: string;
}

/**
 * Tiket kerja teknisi (DESIGN §36.3): chip prioritas/status, nomor, judul, rute jam,
 * sobekan, lalu aset. Seluruh kartu menjadi tautan, kecuali tombol aksinya sendiri.
 */
export function KartuTiketTeknisi({
  tiket,
  href,
  aksi,
  tampilStatus = true,
  catatan,
  className,
}: PropsKartuTiketTeknisi) {
  const rute = ruteTiket(tiket);
  const dapatDiketuk = Boolean(href) && tiket.DapatDibuka;

  return (
    <Tiket
      sebagai="article"
      kerapatan="ringkas"
      className={cn('relative', className)}
      atas={
        <>
          <div className="flex items-start justify-between gap-2">
            <ChipKepalaTiket tiket={tiket} tampilStatus={tampilStatus} />
            <span className="shrink-0 pt-1 text-[13px] font-semibold text-lapangan-teks-3 tabular-nums">
              {tiket.Nomor}
            </span>
          </div>
          <JudulTiket className="mt-2 mb-2.5">
            {dapatDiketuk && href ? (
              <Link
                href={href}
                className="after:absolute after:inset-0 after:rounded-[20px] focus-visible:outline-none focus-visible:after:ring-[3px] focus-visible:after:ring-lapangan-biru-500/60"
              >
                {tiket.Judul}
              </Link>
            ) : (
              tiket.Judul
            )}
          </JudulTiket>
          {rute && <RuteJam {...rute} />}
        </>
      }
      bawah={
        <>
          <BarisAsetTiket
            tiket={tiket}
            kanan={
              aksi ? (
                <div className="relative z-10">{aksi}</div>
              ) : dapatDiketuk ? (
                <ChevronRight aria-hidden className="size-5 shrink-0 text-lapangan-teks-3" />
              ) : null
            }
          />
          {tiket.MenungguKonfirmasiPenerima && (
            <p className="mt-2 flex items-center gap-1.5 text-[13px] font-semibold text-lapangan-kuning-700">
              <Hourglass aria-hidden className="size-4" />
              Menunggu konfirmasi penerima
            </p>
          )}
          {catatan}
        </>
      }
    />
  );
}
