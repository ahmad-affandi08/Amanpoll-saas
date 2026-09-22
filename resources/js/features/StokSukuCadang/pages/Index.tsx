import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Label } from '@/components/ui/label';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { StokSukuCadang } from '@/features/Persediaan/types';
import { ruteStokSukuCadang } from '@/features/StokSukuCadang/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface SukuCadangRingkas {
  Id: string;
  Nama: string;
  Kode: string;
}

interface Props {
  stok: Paginasi<StokSukuCadang>;
  gudang: Ringkas[];
  sukuCadang: SukuCadangRingkas[];
  filter: FilterDaftar;
}

const SEMUA = '__semua__';

export default function StokSukuCadangIndex({ stok, gudang, sukuCadang, filter }: Props) {
  const [gudangId, setGudangId] = useState(filter.gudangId ?? SEMUA);
  const [sukuCadangId, setSukuCadangId] = useState(filter.sukuCadangId ?? SEMUA);

  // Filter lain ikut dibawa, kalau tidak memilih gudang akan membuang pencarian dan urutan yang sedang berlaku.
  const terapkanFilter = (gudangIdBaru: string, sukuCadangIdBaru: string) => {
    router.get(
      ruteStokSukuCadang.index,
      {
        ...filter,
        gudangId: gudangIdBaru === SEMUA ? undefined : gudangIdBaru,
        sukuCadangId: sukuCadangIdBaru === SEMUA ? undefined : sukuCadangIdBaru,
        page: undefined,
      },
      { preserveState: true },
    );
  };

  const columns = useMemo<ColumnDef<StokSukuCadang>[]>(
    () => [
      {
        id: 'NamaSukuCadang',
        accessorFn: (row) => `${row.NamaSukuCadang} ${row.KodeSukuCadang}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Suku Cadang" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.NamaSukuCadang}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.KodeSukuCadang}</div>
          </div>
        ),
        meta: { label: 'Suku Cadang' },
      },
      {
        id: 'NamaGudang',
        accessorFn: (row) => `${row.NamaGudang ?? ''} ${row.NamaLokasiGudang ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Gudang" />,
        cell: ({ row }) => (
          <div>
            <div className="text-foreground">{row.original.NamaGudang}</div>
            {row.original.NamaLokasiGudang && (
              <div className="text-xs text-muted-foreground">{row.original.NamaLokasiGudang}</div>
            )}
          </div>
        ),
        meta: { label: 'Gudang' },
      },
      {
        id: 'NomorBatch',
        accessorFn: (row) => row.NomorBatch ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Batch" />,
        cell: ({ row }) => row.original.NomorBatch ?? '—',
        // Turunan relasi kelompok, bukan kolom StokSukuCadang.
        enableSorting: false,
        meta: { label: 'Batch' },
      },
      {
        id: 'JumlahTersedia',
        accessorFn: (row) => Number(row.JumlahTersedia),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Fisik" />,
        cell: ({ row }) => `${row.original.JumlahTersedia} ${row.original.SatuanDasar ?? ''}`,
        meta: { label: 'Fisik' },
      },
      {
        id: 'JumlahDitahan',
        accessorFn: (row) => Number(row.JumlahDitahan),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Ditahan" />,
        cell: ({ row }) => row.original.JumlahDitahan,
        meta: { label: 'Ditahan' },
      },
      {
        id: 'JumlahTersediaBersih',
        accessorFn: (row) => row.JumlahTersediaBersih,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tersedia Bersih" />,
        cell: ({ row }) => (
          <span className="font-semibold text-foreground">{row.original.JumlahTersediaBersih}</span>
        ),
        // Selisih yang dihitung di resource; urutkan lewat Fisik atau Ditahan.
        enableSorting: false,
        meta: { label: 'Tersedia Bersih' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Stok Suku Cadang" />
      <KepalaHalaman
        judul="Stok Suku Cadang"
        deskripsi="Saldo stok per gudang -- hanya baca. Perubahan hanya lewat Mutasi Stok atau Reservasi."
      />

      <div className="mb-4 grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label>Gudang</Label>
          <Combobox
            nilai={gudangId}
            onPilih={(v) => {
              setGudangId(v);
              terapkanFilter(v, sukuCadangId);
            }}
            opsi={[{ nilai: SEMUA, label: 'Semua Gudang' }, ...opsiDari(gudang, (g) => g.Nama)]}
          />
        </div>
        <div className="space-y-1.5">
          <Label>Suku Cadang</Label>
          <Combobox
            nilai={sukuCadangId}
            onPilih={(v) => {
              setSukuCadangId(v);
              terapkanFilter(gudangId, v);
            }}
            opsi={[
              { nilai: SEMUA, label: 'Semua Suku Cadang' },
              ...opsiDari(sukuCadang, (s) => `${s.Nama} (${s.Kode})`),
            ]}
          />
        </div>
      </div>

      {stok.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada saldo stok."
          deskripsi="Saldo stok akan muncul setelah mutasi stok pertama diposting."
        />
      ) : (
        <DataTable
          columns={columns}
          data={stok.data}
          server={{ meta: stok.meta, filter }}
          pencarianPlaceholder="Cari suku cadang atau gudang..."
          pesanKosong="Tidak ada saldo stok yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
