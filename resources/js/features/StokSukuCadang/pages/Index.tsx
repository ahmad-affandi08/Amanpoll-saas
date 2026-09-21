import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { EmptyState } from '@/components/shared/EmptyState';
import type { StokSukuCadang } from '@/features/Persediaan/types';
import { ruteStokSukuCadang } from '@/features/StokSukuCadang/api';

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
  stok: StokSukuCadang[];
  gudang: Ringkas[];
  sukuCadang: SukuCadangRingkas[];
  filter: { gudangId?: string; sukuCadangId?: string };
}

const SEMUA = '__semua__';

export default function StokSukuCadangIndex({ stok, gudang, sukuCadang, filter }: Props) {
  const [gudangId, setGudangId] = useState(filter.gudangId ?? SEMUA);
  const [sukuCadangId, setSukuCadangId] = useState(filter.sukuCadangId ?? SEMUA);

  const terapkanFilter = (gudangIdBaru: string, sukuCadangIdBaru: string) => {
    router.get(
      ruteStokSukuCadang.index,
      {
        gudangId: gudangIdBaru === SEMUA ? undefined : gudangIdBaru,
        sukuCadangId: sukuCadangIdBaru === SEMUA ? undefined : sukuCadangIdBaru,
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
        meta: { label: 'Tersedia Bersih' },
      },
    ],
    [],
  );

  return (
    <AppLayout>
      <Head title="Stok Suku Cadang" />
      <div className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight text-foreground">Stok Suku Cadang</h1>
        <p className="text-sm text-muted-foreground">
          Saldo stok per gudang -- hanya baca. Perubahan hanya lewat Mutasi Stok atau Reservasi.
        </p>
      </div>

      <div className="mb-4 grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label>Gudang</Label>
          <Select
            value={gudangId}
            onValueChange={(v) => {
              setGudangId(v);
              terapkanFilter(v, sukuCadangId);
            }}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua Gudang</SelectItem>
              {gudang.map((g) => (
                <SelectItem key={g.Id} value={g.Id}>
                  {g.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-1.5">
          <Label>Suku Cadang</Label>
          <Select
            value={sukuCadangId}
            onValueChange={(v) => {
              setSukuCadangId(v);
              terapkanFilter(gudangId, v);
            }}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua Suku Cadang</SelectItem>
              {sukuCadang.map((s) => (
                <SelectItem key={s.Id} value={s.Id}>
                  {s.Nama} ({s.Kode})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {stok.length === 0 ? (
        <EmptyState
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada saldo stok."
          deskripsi="Saldo stok akan muncul setelah mutasi stok pertama diposting."
        />
      ) : (
        <DataTable
          columns={columns}
          data={stok}
          pencarianPlaceholder="Cari suku cadang atau gudang..."
          pesanKosong="Tidak ada saldo stok yang cocok."
        />
      )}
    </AppLayout>
  );
}
