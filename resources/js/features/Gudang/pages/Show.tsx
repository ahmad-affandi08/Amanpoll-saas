import { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import type { Gudang, LokasiGudang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_GUDANG } from '@/features/Persediaan/status';
import { ruteSukuCadang } from '@/features/SukuCadang/api';

interface IsiGudang {
  Id: string;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  SatuanDasar: string | null;
  LokasiGudang: string | null;
  NomorBatch: string | null;
  JumlahTersedia: number;
  JumlahDitahan: number;
  JumlahBersih: number;
}

interface Props {
  gudang: Gudang;
  lokasiGudang: LokasiGudang[];
  stok: Paginasi<IsiGudang>;
  filter: FilterDaftar;
  ringkasan: {
    JenisSukuCadang: number;
    TotalUnit: number;
    JumlahLokasi: number;
    ReservasiAktif: number;
  };
}

export default function GudangShow({ gudang, lokasiGudang, stok, filter, ringkasan }: Props) {
  const columns = useMemo<ColumnDef<IsiGudang>[]>(
    () => [
      {
        id: 'NamaSukuCadang',
        accessorFn: (row) => row.NamaSukuCadang ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Suku Cadang" />,
        cell: ({ row }) => (
          <Link href={ruteSukuCadang.detail(row.original.SukuCadangId)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.NamaSukuCadang ?? '—'}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.KodeSukuCadang}</div>
          </Link>
        ),
        meta: { label: 'Suku Cadang' },
      },
      {
        id: 'LokasiGudangId',
        accessorFn: (row) => row.LokasiGudang ?? '',
        header: 'Lokasi Rak',
        cell: ({ row }) => (
          <div>
            <div className="text-foreground">{row.original.LokasiGudang ?? 'Tanpa rak'}</div>
            {row.original.NomorBatch && (
              <div className="text-xs text-muted-foreground">batch {row.original.NomorBatch}</div>
            )}
          </div>
        ),
        enableSorting: false,
        meta: { label: 'Lokasi Rak' },
      },
      {
        id: 'JumlahTersedia',
        accessorFn: (row) => row.JumlahTersedia,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Fisik" />,
        cell: ({ row }) => `${row.original.JumlahTersedia} ${row.original.SatuanDasar ?? ''}`,
        meta: { label: 'Fisik' },
      },
      {
        id: 'JumlahDitahan',
        accessorFn: (row) => row.JumlahDitahan,
        header: 'Ditahan',
        cell: ({ row }) => row.original.JumlahDitahan,
        // Kolom tabel lain; server hanya mengurutkan Nama dan Fisik.
        enableSorting: false,
        meta: { label: 'Ditahan' },
      },
      {
        id: 'JumlahBersih',
        accessorFn: (row) => row.JumlahBersih,
        header: 'Tersedia Bersih',
        cell: ({ row }) => <span className="font-semibold text-foreground">{row.original.JumlahBersih}</span>,
        enableSorting: false,
        meta: { label: 'Tersedia Bersih' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title={gudang.Nama} />
      <div className="space-y-5">
        <KepalaHalaman
          judul={gudang.Nama}
          labelBreadcrumb={gudang.Kode}
          lencana={<Badge variant={VARIAN_BADGE_STATUS_GUDANG[gudang.Status]}>{gudang.Status}</Badge>}
          deskripsi={
            <>
              <span className="font-mono">{gudang.Kode}</span>
              {gudang.NamaLokasi && ` · ${gudang.NamaLokasi}`}
              {gudang.NamaUnitPengelola && ` · Dikelola ${gudang.NamaUnitPengelola}`}
              {gudang.NamaPenanggungJawab && ` · PJ ${gudang.NamaPenanggungJawab}`}
            </>
          }
        />

        <DeretStatistik kolom={4}>
          <KartuStatistik menyatu label="Jenis Suku Cadang" nilai={ringkasan.JenisSukuCadang} />
          <KartuStatistik menyatu label="Total Unit Fisik" nilai={ringkasan.TotalUnit} />
          <KartuStatistik menyatu label="Lokasi Rak" nilai={ringkasan.JumlahLokasi} />
          <KartuStatistik menyatu label="Reservasi Aktif" nilai={ringkasan.ReservasiAktif} />
        </DeretStatistik>

        <div className="rounded-md border border-border bg-card px-5 py-4">
          <h2 className="mb-3 text-sm font-semibold text-foreground">Lokasi Rak</h2>
          {lokasiGudang.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              Belum ada lokasi rak. Stok tetap dapat disimpan, tetapi sistem hanya dapat menjawab &ldquo;ada
              di gudang mana&rdquo;, bukan &ldquo;ada di rak mana&rdquo;.
            </p>
          ) : (
            <div className="flex flex-wrap gap-2">
              {lokasiGudang.map((satu) => (
                <span
                  key={satu.Id}
                  className="rounded-sm border border-border px-2.5 py-1 text-sm text-foreground"
                >
                  {satu.Nama}
                  {satu.NamaInduk && (
                    <span className="text-xs text-muted-foreground"> di dalam {satu.NamaInduk}</span>
                  )}
                </span>
              ))}
            </div>
          )}
        </div>

        <div>
          <h2 className="mb-3 text-sm font-semibold text-foreground">Isi Gudang</h2>
          <DataTable
            columns={columns}
            data={stok.data}
            server={{ meta: stok.meta, filter }}
            pencarianPlaceholder="Cari nama atau kode suku cadang..."
            pesanKosong={
              adaPenyaringAktif(filter) ? 'Tidak ada suku cadang yang cocok.' : 'Gudang ini masih kosong.'
            }
          />
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
