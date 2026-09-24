import { Link } from '@inertiajs/react';
import { ChevronRight, ClipboardList, Wrench } from 'lucide-react';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { kelasTint } from '@/components/shared/Ikon3D';
import { FotoAtauIkon3D } from '@/components/shared/FotoAtauIkon3D';
import { cn } from '@/lib/utils';
import { ikonKategori } from '@/components/shared/ikon-kategori';
import type { AsetPelapor, WarnaChip } from '@/features/Lapangan/types';

/** Ikon 3D aset: dari jenis asetnya, lalu dari namanya ("Printer Lt. 12"). */
export function ikonAset(aset: Pick<AsetPelapor, 'Kategori' | 'Nama'>) {
  const dariKategori = ikonKategori(aset.Kategori);
  return dariKategori.ikon !== 'toolbox' ? dariKategori : ikonKategori(aset.Nama);
}

/** Foto utama aset (PRD 8.4 "Foto Aset"), atau ikon 3D jenisnya bila belum ada foto. */
export function FotoAset({
  aset,
  className = 'size-11 rounded-[14px]',
  ukuranIkon = 30,
  segera = false,
}: {
  aset: Pick<AsetPelapor, 'Kategori' | 'Nama' | 'FotoUtamaThumbnailUrl'>;
  className?: string;
  ukuranIkon?: number;
  segera?: boolean;
}) {
  const ikon = ikonAset(aset);

  return (
    <FotoAtauIkon3D
      url={aset.FotoUtamaThumbnailUrl}
      ikon={ikon.ikon}
      ukuranIkon={ukuranIkon}
      alt=""
      segera={segera}
      className={cn(kelasTint(ikon.tint), className)}
    />
  );
}

/** Kondisi aset dalam bahasa sehari-hari (layar 14: "Baik", "Ada gangguan", "Rusak"). */
export function kondisiAset(aset: Pick<AsetPelapor, 'Kondisi' | 'LaporanTerbuka'>): {
  label: string;
  warna: WarnaChip;
} {
  const diperbaiki = aset.LaporanTerbuka.some((satu) => satu.Status === 'Diproses');
  if (aset.Kondisi === 'Rusak') return { label: 'Rusak', warna: 'merah' };
  if (diperbaiki) return { label: 'Sedang diperbaiki', warna: 'biru' };
  if (aset.Kondisi === 'PerluPerhatian') return { label: 'Ada gangguan', warna: 'kuning' };
  return { label: 'Baik', warna: 'hijau' };
}

interface PropsBarisPilihAset {
  aset: AsetPelapor;
  onPilih: (aset: AsetPelapor) => void;
}

/** Baris aset di langkah "Pilih alat" (layar 04): yang sudah punya laporan terbuka diberi chip. */
export function BarisPilihAset({ aset, onPilih }: PropsBarisPilihAset) {
  const dilaporkan = aset.LaporanTerbuka.length > 0;

  return (
    <button
      type="button"
      onClick={() => onPilih(aset)}
      className="flex min-h-[68px] w-full items-center gap-3 px-3.5 py-2.5 text-left transition-colors hover:bg-lapangan-latar focus-visible:bg-lapangan-latar focus-visible:outline-none [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2"
    >
      <FotoAset aset={aset} />
      <span className="min-w-0 flex-1">
        <b className="block truncate text-[15px] leading-snug font-bold">{aset.Nama}</b>
        <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
          {[aset.KodeAset, aset.LokasiNama].filter(Boolean).join(' · ')}
        </span>
      </span>
      {dilaporkan ? (
        <ChipStatus warna="biru">Dilaporkan</ChipStatus>
      ) : (
        <ChevronRight aria-hidden className="size-[18px] shrink-0 text-lapangan-teks-3" />
      )}
    </button>
  );
}

/** Baris aset di tab Aset (layar 14): kondisi, penanda laporan terbuka, tombol Lapor. */
export function BarisAsetLokasi({ aset }: { aset: AsetPelapor }) {
  const kondisi = kondisiAset(aset);
  const jumlah = aset.LaporanTerbuka.length;
  /** Penanda "laporan terbuka" membuka pantauan laporan terbaru (milik sendiri: Lacak). */
  const pertama = aset.LaporanTerbuka[0];
  const ditangani =
    aset.Kondisi === 'Rusak' && aset.LaporanTerbuka.some((satu) => satu.Status === 'Diproses');

  return (
    <li className="grid grid-cols-[44px_1fr_auto] items-center gap-x-3 gap-y-2 px-3.5 py-3 [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2">
      <FotoAset aset={aset} />
      <div className="min-w-0">
        <b className="block truncate text-[15px] leading-snug font-bold">{aset.Nama}</b>
        <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
          {[aset.KodeAset, aset.LokasiNama].filter(Boolean).join(' · ')}
        </span>
      </div>
      <Link
        href={ruteLapangan.pelapor.laporDengan({ aset: aset.Id })}
        aria-label={`Lapor kerusakan ${aset.Nama}`}
        className="inline-flex h-11 items-center rounded-xl bg-lapangan-biru-50 px-3.5 text-[13.5px] font-bold text-lapangan-biru-600 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50"
      >
        Lapor
      </Link>
      <div className="col-span-2 col-start-2 flex flex-wrap items-center gap-2.5">
        <ChipStatus warna={kondisi.warna}>{kondisi.label}</ChipStatus>
        {pertama && (
          <Link
            href={
              pertama.MilikSaya
                ? ruteLapangan.pelapor.laporanDetail(pertama.Id)
                : ruteLapangan.pelapor.pantau(pertama.Id)
            }
            aria-label={`Pantau laporan ${pertama.Nomor} untuk ${aset.Nama}`}
            className="-my-2 inline-flex min-h-11 items-center gap-1 rounded-lg text-[13px] font-bold text-lapangan-biru-600 underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50"
          >
            {ditangani ? (
              <>
                <Wrench aria-hidden className="size-3.5" />
                Sedang ditangani
              </>
            ) : (
              <>
                <ClipboardList aria-hidden className="size-3.5" />
                {jumlah} laporan terbuka
              </>
            )}
          </Link>
        )}
      </div>
    </li>
  );
}
