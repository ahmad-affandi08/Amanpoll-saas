import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import type { KategoriSukuCadang } from '@/features/Persediaan/types';
import { ruteKategoriSukuCadang } from '@/features/KategoriSukuCadang/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

/** Hanya Id dan Nama: pemilih induk memuat seluruh kategori, bukan barisnya. */
interface IndukRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  kategoriSukuCadang: Paginasi<KategoriSukuCadang>;
  pilihanInduk: IndukRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormKategori({
  kategori,
  semuaKategori,
  wajib,
}: {
  kategori: KategoriSukuCadang | null;
  semuaKategori: IndukRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    kategori
      ? { Kode: kategori.Kode, Nama: kategori.Nama, IndukId: kategori.IndukId ?? TANPA_PILIHAN }
      : { Kode: '', Nama: '', IndukId: TANPA_PILIHAN },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, IndukId: form.data.IndukId === TANPA_PILIHAN ? null : form.data.IndukId };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!kategori) form.reset();
      },
    };
    if (kategori) {
      router.put(ruteKategoriSukuCadang.detail(kategori.Id), payload, opsi);
    } else {
      router.post(ruteKategoriSukuCadang.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kategori ? 'outline' : 'default'} size={kategori ? 'sm' : 'default'}>
          {kategori ? 'Ubah' : 'Tambah Kategori'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{kategori ? 'Ubah Kategori Suku Cadang' : 'Tambah Kategori Suku Cadang'}</DialogTitle>
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
              <Label nama="IndukId">Kategori Induk</Label>
              <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Tidak ada (kategori utama)</SelectItem>
                  {semuaKategori
                    .filter((k) => k.Id !== kategori?.Id)
                    .map((k) => (
                      <SelectItem key={k.Id} value={k.Id}>
                        {k.Nama}
                      </SelectItem>
                    ))}
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

export default function KategoriSukuCadangIndex({ kategoriSukuCadang, pilihanInduk, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: KategoriSukuCadang) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kategori "${item.Nama}"?`,
        deskripsi: 'Kategori yang masih dipakai suku cadang tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteKategoriSukuCadang.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KategoriSukuCadang>[]>(
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
        id: 'NamaInduk',
        accessorFn: (row) => row.NamaInduk ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Induk" />,
        cell: ({ row }) => row.original.NamaInduk ?? '—',
        meta: { label: 'Induk' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormKategori
              kategori={row.original}
              semuaKategori={pilihanInduk}
              wajib={wajib.kategoriSukuCadang}
            />
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
    [kategoriSukuCadang, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kategori Suku Cadang" />
      <KepalaHalaman
        judul="Kategori Suku Cadang"
        deskripsi="Klasifikasi suku cadang, mendukung hierarki sub-kategori."
        aksi={
          <>
            <DialogFormKategori
              kategori={null}
              semuaKategori={pilihanInduk}
              wajib={wajib.kategoriSukuCadang}
            />
          </>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kategoriSukuCadang.data}
        server={{ meta: kategoriSukuCadang.meta, filter }}
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong={
          adaPenyaringAktif(filter) ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori suku cadang.'
        }
      />
    </KerangkaAplikasi>
  );
}
