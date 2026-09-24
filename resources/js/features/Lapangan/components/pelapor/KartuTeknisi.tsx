import { Phone, Wrench } from 'lucide-react';
import { inisialNama } from '@/features/Lapangan/waktu';
import type { TeknisiLaporan } from '@/features/Lapangan/types';

const STATUS_PEKERJAAN: Record<string, string> = {
  Ditugaskan: 'Baru ditugaskan',
  Diterima: 'Menuju lokasi',
  Dikerjakan: 'Sedang mengerjakan',
  MenungguSukuCadang: 'Menunggu suku cadang',
  MenungguPenyedia: 'Menunggu penyedia',
  Dijeda: 'Pekerjaan dijeda',
  MenungguVerifikasi: 'Selesai, dicek koordinator',
  Selesai: 'Pekerjaan selesai',
  Ditutup: 'Pekerjaan selesai',
};

/** Avatar inisial bergradien navy (papan acuan `.avatar.navy`). */
export function AvatarTeknisi({ nama, besar = false }: { nama: string; besar?: boolean }) {
  return (
    <span
      aria-hidden
      className={
        'flex shrink-0 items-center justify-center rounded-full border-2 border-white bg-linear-to-br from-lapangan-biru-500 to-lapangan-navy-800 font-bold text-white ' +
        (besar ? 'size-11 text-[15px]' : 'size-10 text-sm')
      }
    >
      {inisialNama(nama)}
    </span>
  );
}

/**
 * Kartu teknisi di perhentian saat ini (papan pelapor layar 10): nama, keadaan
 * pekerjaannya, dan tombol telepon bila nomornya tercatat.
 */
export function KartuTeknisi({ teknisi }: { teknisi: TeknisiLaporan }) {
  return (
    <div className="flex items-center gap-2.5 rounded-2xl bg-lapangan-latar p-2.5">
      <AvatarTeknisi nama={teknisi.Nama} />
      <div className="min-w-0 flex-1">
        <b className="block truncate text-sm font-bold">{teknisi.Nama}</b>
        <span className="flex items-start gap-1 text-[13px] leading-tight font-bold text-lapangan-hijau-700">
          <Wrench aria-hidden className="mt-px size-3.5 shrink-0" />
          <span>{STATUS_PEKERJAAN[teknisi.StatusPekerjaan] ?? teknisi.Jabatan ?? 'Teknisi'}</span>
        </span>
      </div>
      {teknisi.Telepon && (
        <a
          href={`tel:${teknisi.Telepon.replace(/[^\d+]/g, '')}`}
          aria-label={`Telepon ${teknisi.Nama}`}
          className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lapangan-hijau-700 text-white shadow-[0_6px_14px_rgb(14_122_79_/_0.28)] focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50"
        >
          <Phone aria-hidden className="size-5" />
        </a>
      )}
    </div>
  );
}
