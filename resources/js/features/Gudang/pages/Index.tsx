import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { Gudang, LokasiGudang, StatusGudang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_GUDANG } from '@/features/Persediaan/status';
import { ruteGudang } from '@/features/Gudang/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface LokasiRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  gudang: Gudang[];
  lokasiGudangPerGudang: Record<string, LokasiGudang[]>;
  lokasi: LokasiRingkas[];
}

const TANPA = '__tanpa__';

function DialogFormGudang({ gudang, lokasi }: { gudang: Gudang | null; lokasi: LokasiRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    gudang
      ? { Kode: gudang.Kode, Nama: gudang.Nama, LokasiId: gudang.LokasiId ?? TANPA, Status: gudang.Status }
      : { Kode: '', Nama: '', LokasiId: TANPA, Status: 'Aktif' as StatusGudang },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, LokasiId: form.data.LokasiId === TANPA ? null : form.data.LokasiId };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!gudang) form.reset();
      },
    };
    if (gudang) {
      router.put(ruteGudang.detail(gudang.Id), payload, opsi);
    } else {
      router.post(ruteGudang.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={gudang ? 'outline' : 'default'} size={gudang ? 'sm' : 'default'}>
          {gudang ? 'Ubah' : 'Tambah Gudang'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{gudang ? 'Ubah Gudang' : 'Tambah Gudang'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode</Label>
              <Input
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                className="font-mono"
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>Lokasi</Label>
            <Select value={form.data.LokasiId} onValueChange={(v) => form.setData('LokasiId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tidak diisi</SelectItem>
                {lokasi.map((l) => (
                  <SelectItem key={l.Id} value={l.Id}>
                    {l.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Status</Label>
            <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v as StatusGudang)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Aktif">Aktif</SelectItem>
                <SelectItem value="Nonaktif">Nonaktif</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogLokasiGudang({ gudang, lokasiGudang }: { gudang: Gudang; lokasiGudang: LokasiGudang[] }) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', IndukId: TANPA });

  const tambah = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteGudang.lokasi(gudang.Id),
      { ...form.data, IndukId: form.data.IndukId === TANPA ? null : form.data.IndukId },
      {
        preserveScroll: true,
        onSuccess: () => form.reset(),
      },
    );
  };

  const hapus = async (item: LokasiGudang) => {
    if (
      !(await konfirmasi({
        judul: `Hapus lokasi "${item.Nama}"?`,
        deskripsi: 'Lokasi yang masih menyimpan stok tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(`/lokasi-gudang/${item.Id}`, { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Lokasi ({lokasiGudang.length})
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Lokasi dalam {gudang.Nama}</DialogTitle>
        </DialogHeader>
        <form onSubmit={tambah} className="flex items-end gap-2">
          <div className="flex-1 space-y-1.5">
            <Label>Kode</Label>
            <Input
              value={form.data.Kode}
              onChange={(e) => form.setData('Kode', e.target.value)}
              className="font-mono"
            />
          </div>
          <div className="flex-1 space-y-1.5">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          </div>
          <Button type="submit" size="sm" disabled={form.processing}>
            Tambah
          </Button>
        </form>
        <div className="space-y-2">
          {lokasiGudang.length === 0 && <p className="text-sm text-muted-foreground">Belum ada lokasi.</p>}
          {lokasiGudang.map((l) => (
            <div
              key={l.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
            >
              <div>
                <span className="font-medium text-foreground">{l.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{l.Kode}</span>
                {l.NamaInduk && (
                  <span className="ml-2 text-xs text-muted-foreground">di dalam {l.NamaInduk}</span>
                )}
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(l)}>
                Hapus
              </Button>
            </div>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default function GudangIndex({ gudang, lokasiGudangPerGudang, lokasi }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Gudang) => {
    if (
      !(await konfirmasi({
        judul: `Hapus gudang "${item.Nama}"?`,
        deskripsi: 'Gudang yang masih memiliki stok atau mutasi tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteGudang.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Gudang>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaLokasi',
        accessorFn: (row) => row.NamaLokasi ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Lokasi" />,
        cell: ({ row }) => row.original.NamaLokasi ?? '—',
        meta: { label: 'Lokasi' },
      },
      {
        id: 'PenanggungJawab',
        accessorFn: (row) => row.NamaPenanggungJawab ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Penanggung Jawab" />,
        cell: ({ row }) => row.original.NamaPenanggungJawab ?? '—',
        meta: { label: 'Penanggung Jawab' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={VARIAN_BADGE_STATUS_GUDANG[row.original.Status]}>{row.original.Status}</Badge>
        ),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogLokasiGudang
              gudang={row.original}
              lokasiGudang={lokasiGudangPerGudang[row.original.Id] ?? []}
            />
            <DialogFormGudang gudang={row.original} lokasi={lokasi} />
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [lokasiGudangPerGudang, lokasi],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Gudang" />
      <KepalaHalaman
        judul="Gudang"
        deskripsi="Kelola gudang beserta lokasi penyimpanan di dalamnya."
        aksi={
          <>
            <DialogFormGudang gudang={null} lokasi={lokasi} />
          </>
        }
        className="mb-6"
      />

      {gudang.length === 0 ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/gudang.webp"
          judul="Belum ada gudang."
          deskripsi="Tambahkan gudang pertama untuk mulai mencatat stok."
        />
      ) : (
        <DataTable
          columns={columns}
          data={gudang}
          pencarianPlaceholder="Cari nama atau kode gudang..."
          pesanKosong="Belum ada gudang."
        />
      )}
    </KerangkaAplikasi>
  );
}
