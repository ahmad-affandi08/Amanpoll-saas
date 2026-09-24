import { Siren, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D, type NamaIkon3D } from '@/features/Lapangan/components/Ikon3D';
import { Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { KejadianRiwayatAset, PropsRiwayatAsetTeknisi, WarnaChip } from '@/features/Lapangan/types';
import { usePeringatanOffline } from '@/features/Lapangan/components/teknisi/umum';
import { durasiPendek, hariIni } from '@/features/Lapangan/components/teknisi/waktuTiket';

type SaringanRiwayat = 'Semua' | 'Perbaikan' | 'Preventif' | 'Inspeksi';

const NAMA_JENIS: Record<string, string> = {
  Korektif: 'Perbaikan',
  Preventif: 'Preventif',
  Inspeksi: 'Inspeksi',
  Kalibrasi: 'Kalibrasi',
  Umum: 'Pekerjaan',
  Vendor: 'Vendor',
};

const IKON_JENIS: Record<string, NamaIkon3D> = {
  Korektif: 'wrench',
  Preventif: 'spiral_calendar',
  Inspeksi: 'shield',
  Kalibrasi: 'stopwatch',
  Vendor: 'handshake',
};

const STATUS_SELESAI = ['MenungguVerifikasi', 'Selesai', 'Ditutup'];

const HASIL_INSPEKSI: Record<string, { teks: string; warna: WarnaChip }> = {
  Lolos: { teks: 'lulus', warna: 'hijau' },
  PerluPerhatian: { teks: 'perlu perhatian', warna: 'kuning' },
  Gagal: { teks: 'gagal', warna: 'merah' },
};

/** Teks dan warna chip kejadian: "Kritis · dikerjakan", "Preventif · selesai", "Inspeksi · lulus". */
function chipKejadian(satu: KejadianRiwayatAset): { teks: string; warna: WarnaChip; kritis: boolean } {
  if (satu.Jenis === 'Inspeksi') {
    const hasil = HASIL_INSPEKSI[satu.Status ?? ''] ?? { teks: 'selesai', warna: 'hijau' as WarnaChip };
    return { teks: `Inspeksi · ${hasil.teks}`, warna: hasil.warna, kritis: false };
  }
  const selesai = STATUS_SELESAI.includes(satu.Status ?? '');
  const kritis = satu.Prioritas === 'Kritis' && !selesai;
  const status = selesai ? 'selesai' : (satu.Status ?? '').replace(/([a-z])([A-Z])/g, '$1 $2').toLowerCase();
  return {
    teks: `${kritis ? 'Kritis' : (NAMA_JENIS[satu.Kategori ?? ''] ?? 'Pekerjaan')} · ${status}`,
    warna: kritis ? 'merah' : selesai ? 'hijau' : 'biru',
    kritis,
  };
}

function kelompok(satu: KejadianRiwayatAset): SaringanRiwayat {
  if (satu.Jenis === 'Inspeksi') return 'Inspeksi';
  return satu.Kategori === 'Preventif' ? 'Preventif' : 'Perbaikan';
}

/** Riwayat aset (DESIGN §36.6 layar 15): ringkasan angka dan garis waktu pekerjaan. */
export default function RiwayatAsetTeknisi(props: PropsRiwayatAsetTeknisi) {
  const [saringBuka, setSaringBuka] = useState(false);
  const [saringan, setSaringan] = useState<SaringanRiwayat>('Semua');

  return (
    <KerangkaLapangan
      varian="appbar"
      judulHalaman={`Riwayat ${props.aset.Nama}`}
      judul="Riwayat aset"
      subjudul={`${props.aset.KodeAset} · ${props.aset.Nama}`}
      panjang
      aksiKanan={
        <TombolAppbar label="Saring riwayat" ikon={SlidersHorizontal} onClick={() => setSaringBuka(true)} />
      }
    >
      <IsiRiwayat {...props} saringan={saringan} />
      <LembarBawah
        buka={saringBuka}
        onBukaBerubah={setSaringBuka}
        judul="Saring riwayat"
        kaki={
          <TombolLapangan penuh onClick={() => setSaringBuka(false)}>
            Terapkan
          </TombolLapangan>
        }
      >
        <div role="radiogroup" aria-label="Jenis pekerjaan" className="flex flex-wrap gap-2">
          {(['Semua', 'Perbaikan', 'Preventif', 'Inspeksi'] as const).map((satu) => (
            <button
              key={satu}
              type="button"
              role="radio"
              aria-checked={saringan === satu}
              onClick={() => setSaringan(satu)}
              className={cn(
                'h-11 rounded-xl px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                saringan === satu
                  ? 'bg-lapangan-navy-800 text-white'
                  : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
              )}
            >
              {satu}
            </button>
          ))}
        </div>
      </LembarBawah>
    </KerangkaLapangan>
  );
}

function IsiRiwayat({
  ringkasan,
  linimasa,
  saringan,
}: PropsRiwayatAsetTeknisi & { saringan: SaringanRiwayat }) {
  usePeringatanOffline();
  const tampil = linimasa.filter((satu) => saringan === 'Semua' || kelompok(satu) === saringan);

  return (
    <>
      <KartuApung className="grid grid-cols-3 px-1 py-4 text-center">
        {[
          { nilai: String(ringkasan.PekerjaanTahunIni), label: 'pekerjaan tahun ini' },
          {
            nilai: `${ringkasan.PersenBeroperasi.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`,
            label: 'beroperasi 30 hari',
          },
          {
            nilai: ringkasan.HariAntarKerusakan === null ? '—' : `${ringkasan.HariAntarKerusakan} hr`,
            label: 'rata-rata antar kerusakan',
          },
        ].map((satu, i) => (
          <div key={satu.label} className={cn('px-2', i > 0 && 'border-l-[1.5px] border-lapangan-garis-2')}>
            <strong className="block text-[22px] leading-tight font-extrabold tracking-[-0.02em] tabular-nums">
              {satu.nilai}
            </strong>
            <small className="mt-0.5 block text-xs leading-tight font-semibold text-lapangan-teks-3">
              {satu.label}
            </small>
          </div>
        ))}
      </KartuApung>

      {tampil.length === 0 ? (
        <Kartu>
          <IlustrasiMomen
            ringkas
            ikon="card_index_dividers"
            judul="Belum ada riwayat"
            teks={
              saringan === 'Semua'
                ? 'Pekerjaan pada aset ini akan tercatat di sini.'
                : `Belum ada ${saringan.toLowerCase()} pada aset ini.`
            }
          />
        </Kartu>
      ) : (
        <ol aria-label="Garis waktu pekerjaan" className="flex flex-col gap-3">
          {tampil.map((satu, i) => {
            const chip = chipKejadian(satu);
            const tanggal = satu.Pada ? new Date(satu.Pada) : null;
            const kini =
              i === 0 && !STATUS_SELESAI.includes(satu.Status ?? '') && satu.Jenis === 'PerintahKerja';
            const meta = [
              satu.Pada && hariIni(satu.Pada) ? 'Hari ini' : null,
              satu.DurasiMenit ? durasiPendek(satu.DurasiMenit) : null,
              satu.Keterangan && !satu.DurasiMenit ? satu.Keterangan : null,
              satu.Teknisi.join(', ') || null,
            ]
              .filter(Boolean)
              .join(' · ');

            return (
              <li
                key={`${satu.Jenis}-${satu.Id}`}
                className="grid grid-cols-[44px_20px_1fr] items-start gap-2"
              >
                <span className="pt-2.5 text-center">
                  <strong className="block text-xl leading-none font-bold tracking-[-0.02em] tabular-nums">
                    {tanggal ? String(tanggal.getDate()).padStart(2, '0') : '—'}
                  </strong>
                  <small className="mt-1 block text-xs font-bold text-lapangan-teks-3">
                    {tanggal ? tanggal.toLocaleDateString('id-ID', { month: 'short' }) : ''}
                  </small>
                </span>
                <span aria-hidden className="relative self-stretch">
                  <span
                    className={cn(
                      'absolute left-[9px] border-l-2 border-dashed border-lapangan-teks-3/35',
                      i === 0 ? 'top-[18px]' : 'top-0',
                      i === tampil.length - 1 ? 'bottom-0' : '-bottom-3',
                    )}
                  />
                  <i
                    className={cn(
                      'absolute top-3.5 left-[3px] size-3.5 rounded-full bg-white',
                      kini
                        ? 'shadow-[inset_0_0_0_4px_var(--color-lapangan-oranye-600),0_0_0_4px_var(--color-lapangan-oranye-100)]'
                        : 'shadow-[inset_0_0_0_3px_rgb(91_103_115_/_0.45)]',
                    )}
                  />
                </span>
                <Kartu className="flex items-start gap-3 px-3.5 py-3">
                  <div className="min-w-0 flex-1">
                    <ChipStatus warna={chip.warna} ukuran="kecil" ikon={chip.kritis ? Siren : undefined}>
                      {chip.teks}
                    </ChipStatus>
                    <b className="mt-1.5 block text-[14.5px] leading-snug font-bold">{satu.Judul}</b>
                    {meta && (
                      <span className="mt-0.5 block text-[12.5px] font-medium text-lapangan-teks-3">
                        {meta}
                      </span>
                    )}
                  </div>
                  <Ikon3D
                    nama={
                      satu.Jenis === 'Inspeksi' ? 'shield' : (IKON_JENIS[satu.Kategori ?? ''] ?? 'wrench')
                    }
                    ukuran={32}
                  />
                </Kartu>
              </li>
            );
          })}
        </ol>
      )}
    </>
  );
}
