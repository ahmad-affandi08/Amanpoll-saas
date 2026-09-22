import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { Combobox } from '@/components/ui/combobox';

interface LokasiRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  gudang: Paginasi<Gudang>;
  lokasiGudangPerGudang: Record<string, LokasiGudang[]>;
  lokasi: LokasiRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormGudang({
  gudang,
  lokasi,
  wajib,
}: {
  gudang: Gudang | null;
  lokasi: LokasiRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    gudang
      ? {
          Kode: gudang.Kode,
          Nama: gudang.Nama,
          LokasiId: gudang.LokasiId ?? TANPA_PILIHAN,
          Status: gudang.Status,
        }
      : { Kode: '', Nama: '', LokasiId: TANPA_PILIHAN, Status: 'Aktif' as StatusGudang },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      LokasiId: form.data.LokasiId === TANPA_PILIHAN ? null : form.data.LokasiId,
    };
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-2">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="LokasiId">Lokasi</Label>
              <Combobox
                nilai={form.data.LokasiId}
                onPilih={(v) => form.setData('LokasiId', v)}
                opsi={[opsiKosong('Tidak diisi'), ...opsiDari(lokasi, (l) => l.Nama)]}
              />
            </div>
            <div className="space-y-2">
              <Label nama="Status">Status</Label>
              <Select
                value={form.data.Status}
                onValueChange={(v) => form.setData('Status', v as StatusGudang)}
              >
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogLokasiGudang({
  gudang,
  lokasiGudang,
  wajib,
}: {
  gudang: Gudang;
  lokasiGudang: LokasiGudang[];
  wajib: AturanWajib;
}) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', IndukId: TANPA_PILIHAN });

  const tambah = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteGudang.lokasi(gudang.Id),
      { ...form.data, IndukId: form.data.IndukId === TANPA_PILIHAN ? null : form.data.IndukId },
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
    router.delete(ruteGudang.lokasiDetail(item.Id), { preserveScroll: true });
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={tambah} className="flex items-end gap-2">
            <div className="flex-1 space-y-1.5">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            </div>
            <Button type="submit" size="sm" disabled={form.processing}>
              Tambah
            </Button>
          </form>
        </AturanWajibProvider>
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

export default function GudangIndex({ gudang, lokasiGudangPerGudang, lokasi, filter, wajib }: Props) {
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
          <Link href={ruteGudang.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </Link>
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
              wajib={wajib.lokasiGudang}
            />
            <DialogFormGudang gudang={row.original} lokasi={lokasi} wajib={wajib.gudang} />
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
    [lokasiGudangPerGudang, lokasi, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Gudang" />
      <KepalaHalaman
        judul="Gudang"
        deskripsi="Kelola gudang beserta lokasi penyimpanan di dalamnya."
        aksi={
          <>
            <DialogFormGudang gudang={null} lokasi={lokasi} wajib={wajib.gudang} />
          </>
        }
        className="mb-6"
      />

      {gudang.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/gudang.webp"
          judul="Belum ada gudang."
          deskripsi="Tambahkan gudang pertama untuk mulai mencatat stok."
        />
      ) : (
        <DataTable
          columns={columns}
          data={gudang.data}
          server={{ meta: gudang.meta, filter }}
          facetedFilters={[
            {
              columnId: 'Status',
              title: 'Status',
              options: [
                { label: 'Aktif', value: 'Aktif' },
                { label: 'Nonaktif', value: 'Nonaktif' },
              ],
            },
            {
              columnId: 'LokasiId',
              title: 'Lokasi',
              options: lokasi.map((satu) => ({ label: satu.Nama, value: satu.Id })),
            },
          ]}
          pencarianPlaceholder="Cari nama atau kode gudang..."
          pesanKosong="Tidak ada gudang yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
