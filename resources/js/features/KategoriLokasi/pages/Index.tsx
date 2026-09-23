import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { ruteKategoriLokasi } from '@/features/KategoriLokasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface KategoriLokasi {
  Id: string;
  Kode: string;
  Nama: string;
  Keterangan: string | null;
  DibuatPada: string;
}

interface Props {
  kategoriLokasi: Paginasi<KategoriLokasi>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormKategoriLokasi({
  kategori,
  wajib,
}: {
  kategori: KategoriLokasi | null;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: kategori?.Kode ?? '',
    Nama: kategori?.Nama ?? '',
    Keterangan: kategori?.Keterangan ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!kategori) form.reset();
      },
    };

    if (kategori) {
      router.put(ruteKategoriLokasi.detail(kategori.Id), form.data, opsi);
    } else {
      router.post(ruteKategoriLokasi.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kategori ? 'outline' : 'default'} size={kategori ? 'sm' : 'default'}>
          {kategori ? 'Ubah' : 'Tambah Kategori'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{kategori ? 'Ubah Kategori Lokasi' : 'Tambah Kategori Lokasi'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="grid gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
            />

            <div className="grid gap-2">
              <Label nama="Nama" htmlFor="Nama">
                Nama
              </Label>
              <Input
                id="Nama"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
            </div>

            <div className="grid gap-2">
              <Label nama="Keterangan" htmlFor="Keterangan">
                Keterangan
              </Label>
              <Textarea
                id="Keterangan"
                rows={3}
                value={form.data.Keterangan}
                onChange={(e) => form.setData('Keterangan', e.target.value)}
              />
              {form.errors.Keterangan ? (
                <p className="text-sm text-destructive">{form.errors.Keterangan}</p>
              ) : null}
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

export default function KategoriLokasiIndex({ kategoriLokasi, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapus = async (kategori: KategoriLokasi) => {
    const lanjut = await konfirmasi({
      judul: `Hapus kategori "${kategori.Nama}"?`,
      deskripsi: 'Kategori yang masih dipakai lokasi tidak dapat dihapus.',
      ragam: 'bahaya',
    });

    if (!lanjut) {
      return;
    }

    router.delete(ruteKategoriLokasi.detail(kategori.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KategoriLokasi>[]>(
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
        meta: { label: 'Nama', kartu: 'judul' },
      },
      {
        id: 'Keterangan',
        accessorFn: (row) => row.Keterangan ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Keterangan" />,
        cell: ({ row }) => row.original.Keterangan ?? '—',
        meta: { label: 'Keterangan' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormKategoriLokasi kategori={row.original} wajib={wajib.kategoriLokasi} />
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kategori Lokasi" />
      <KepalaHalaman
        judul="Kategori Lokasi"
        deskripsi="Klasifikasi lokasi, misalnya gedung, lantai, atau ruangan."
        aksi={<DialogFormKategoriLokasi kategori={null} wajib={wajib.kategoriLokasi} />}
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kategoriLokasi.data}
        server={{ meta: kategoriLokasi.meta, filter }}
        ekspor="/platform/kategori-lokasi/ekspor"
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong={
          adaPenyaringAktif(filter) ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori lokasi.'
        }
      />
    </KerangkaAplikasi>
  );
}
