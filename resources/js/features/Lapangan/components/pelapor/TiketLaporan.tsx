import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { WadahIkon3D } from '@/components/shared/Ikon3D';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { ikonKategori } from '@/components/shared/ikon-kategori';
import type { LaporanPelapor } from '@/features/Lapangan/types';
import { JejakLaporan } from '@/features/Lapangan/components/pelapor/JejakLaporan';
import { namaDepan, tampilanStatus, teksLangkah } from '@/features/Lapangan/components/pelapor/status';
import { waktuSingkat } from '@/features/Lapangan/components/pelapor/waktu';

/** Ikon 3D laporan: dari jenis aset, lalu nama aset, lalu kategori keluhan. */
export function ikonLaporan(laporan: Pick<LaporanPelapor, 'Aset' | 'KategoriNama'>) {
  const petunjuk = [laporan.Aset?.Kategori, laporan.Aset?.Nama, laporan.KategoriNama].find(
    (teks) => teks && ikonKategori(teks).ikon !== 'toolbox',
  );
  return ikonKategori(petunjuk ?? laporan.KategoriNama);
}

/** Tujuan saat tiket diketuk: laporan yang menunggu konfirmasi langsung ke layar konfirmasi. */
export function tujuanLaporan(laporan: Pick<LaporanPelapor, 'Id' | 'Status'>): string {
  return laporan.Status === 'Selesai'
    ? ruteLapangan.pelapor.konfirmasi(laporan.Id)
    : ruteLapangan.pelapor.laporanDetail(laporan.Id);
}

function keteranganSingkat(laporan: LaporanPelapor, ragam: 'beranda' | 'daftar'): string {
  const teknisi = laporan.Teknisi?.Nama;
  switch (laporan.Status) {
    case 'Selesai':
      return ragam === 'beranda'
        ? `Sudah diperbaiki${teknisi ? ` ${namaDepan(teknisi)}` : ''}`
        : [`Selesai ${waktuSingkat(laporan.DiresolusikanPada)}`, teknisi].filter(Boolean).join(' · ');
    case 'Ditutup':
      return `Ditutup ${waktuSingkat(laporan.DitutupPada)}`;
    case 'Baru':
      return `Dilaporkan ${waktuSingkat(laporan.DilaporkanPada)}`;
    default:
      return [`Dilaporkan ${waktuSingkat(laporan.DilaporkanPada)}`, teknisi].filter(Boolean).join(' · ');
  }
}

interface PropsTiketLaporan {
  laporan: LaporanPelapor;
  /** `beranda`: ringkas dengan tombol Konfirmasi; `daftar`: dengan jejak langkah di bawah sobekan. */
  ragam?: 'beranda' | 'daftar';
}

/** Tiket laporan keluhan milik pelapor (papan pelapor layar 02 dan 09). Seluruh tiket dapat diketuk. */
export function TiketLaporan({ laporan, ragam = 'daftar' }: PropsTiketLaporan) {
  const status = tampilanStatus(laporan.Status);
  const ikon = ikonLaporan(laporan);
  const perluKonfirmasi = laporan.Status === 'Selesai';
  const berjalan = !perluKonfirmasi && !['Ditutup', 'Ditolak', 'Dibatalkan'].includes(laporan.Status);

  const kepala = (
    <div className="flex items-center justify-between gap-3">
      <ChipStatus warna={status.warna} ikon={status.ikon}>
        {status.label}
      </ChipStatus>
      <span className="truncate text-[13px] font-semibold text-lapangan-teks-3">{laporan.Nomor}</span>
    </div>
  );

  return (
    <Link
      href={tujuanLaporan(laporan)}
      className={cn(
        'block rounded-[20px] bg-white shadow-lapangan-kartu transition-shadow focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
        perluKonfirmasi && 'ring-2 ring-lapangan-oranye-100',
      )}
    >
      <div className={cn('px-4 pt-4', ragam === 'daftar' && berjalan ? 'pb-3' : 'pb-4')}>
        {kepala}
        {ragam === 'beranda' && berjalan ? (
          <>
            <h3 className="mt-2 mb-3 text-base leading-[1.3] font-bold tracking-[-0.01em]">
              {laporan.Judul}
            </h3>
            <JejakLaporan status={laporan.Status} />
          </>
        ) : (
          <div className="mt-2.5 flex items-center gap-3">
            <WadahIkon3D
              nama={ikon.ikon}
              tint={ikon.tint}
              ukuran="sedang"
              className="size-11 rounded-[14px]"
            />
            <div className="min-w-0 flex-1">
              <h3 className="text-[15px] leading-[1.3] font-bold tracking-[-0.01em] text-lapangan-teks">
                {laporan.Judul}
              </h3>
              <p className="mt-px text-[13px] font-medium text-lapangan-teks-3">
                {keteranganSingkat(laporan, ragam)}
              </p>
            </div>
            {ragam === 'beranda' && perluKonfirmasi && (
              <span className="inline-flex h-11 shrink-0 items-center rounded-xl bg-lapangan-oranye-700 px-4 text-sm font-bold text-white shadow-lapangan-oranye">
                Konfirmasi
              </span>
            )}
          </div>
        )}
      </div>
      {ragam === 'daftar' && berjalan && (
        <>
          <Sobekan />
          <div className="px-4 pt-2.5 pb-4">
            <JejakLaporan status={laporan.Status} />
            <p className="mt-2 text-[13px] font-medium text-lapangan-teks-3">
              {teksLangkah(laporan.Status)}
              {laporan.StatusSejak && laporan.Status !== 'Baru' && (
                <span className="font-bold text-lapangan-teks">
                  {' '}
                  sejak {waktuSingkat(laporan.StatusSejak)}
                </span>
              )}
            </p>
          </div>
        </>
      )}
    </Link>
  );
}
