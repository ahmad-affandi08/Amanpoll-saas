import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Redirect } from '@/features/Pemasaran/types';

interface Props {
  redirect: Redirect[];
  pilihan: { Kode: string[] };
}

const AKAR = '/admin-platform/pemasaran/redirect';

export default function Index({ redirect, pilihan }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapus = async (satu: Redirect) => {
    const setuju = await konfirmasi({
      judul: 'Hapus redirect?',
      deskripsi: `Alamat ${satu.Dari} akan kembali menjawab 404 bagi siapa pun yang masih menautnya.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.delete(`${AKAR}/${satu.Id}`, { preserveScroll: true });
    }
  };

  const columns = useMemo<ColumnDef<Redirect>[]>(
    () => [
      {
        id: 'Dari',
        accessorFn: (row) => `${row.Dari} ${row.Ke ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Alamat" />,
        cell: ({ row }) => (
          <div className="font-mono text-xs">
            <div className="text-foreground">{row.original.Dari}</div>
            <div className="text-muted-foreground">→ {row.original.Ke ?? '410 Gone'}</div>
          </div>
        ),
        meta: { label: 'Alamat', kartu: 'judul' },
      },
      {
        id: 'Kode',
        accessorFn: (row) => row.Kode,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => (
          <div className="flex gap-1">
            <Badge variant="secondary">{row.original.Kode}</Badge>
            {row.original.Aktif ? null : <Badge variant="outline">Nonaktif</Badge>}
          </div>
        ),
        meta: { label: 'Kode' },
      },
      {
        id: 'JumlahDipakai',
        accessorFn: (row) => row.JumlahDipakai,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Dipakai" />,
        cell: ({ row }) => <span className="font-mono text-xs">{row.original.JumlahDipakai}</span>,
        meta: { label: 'Dipakai' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogRedirect redirect={row.original} pilihan={pilihan} />
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [pilihan],
  );

  return (
    <KerangkaPlatform>
      <Head title="Redirect" />

      <PageHeader
        judul="Redirect"
        deskripsi="Berlaku hanya di host publik. Slug yang berubah tanpa redirect kehilangan peringkatnya."
        tanpaBreadcrumb
        aksi={<DialogRedirect redirect={null} pilihan={pilihan} />}
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={redirect}
        kartuDiPonsel
        pencarianPlaceholder="Cari alamat..."
        pesanKosong="Belum ada redirect."
      />
    </KerangkaPlatform>
  );
}

function DialogRedirect({ redirect, pilihan }: { redirect: Redirect | null; pilihan: { Kode: string[] } }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Dari: redirect?.Dari ?? '',
    Ke: redirect?.Ke ?? '',
    Kode: redirect?.Kode ?? pilihan.Kode[0],
    Aktif: redirect?.Aktif ?? true,
    Catatan: redirect?.Catatan ?? '',
  });

  const butuhTujuan = form.data.Kode !== '410';

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!redirect) form.reset();
      },
    };

    if (redirect) {
      router.put(`${AKAR}/${redirect.Id}`, form.data, opsi);
    } else {
      router.post(AKAR, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={redirect ? 'outline' : 'default'} size={redirect ? 'sm' : 'default'}>
          {redirect ? 'Ubah' : 'Tambah Redirect'}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{redirect ? 'Ubah Redirect' : 'Tambah Redirect'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Dari">Dari</Label>
            <Input
              id="Dari"
              value={form.data.Dari}
              onChange={(e) => form.setData('Dari', e.target.value)}
              placeholder="/halaman-lama"
              required
            />
            {form.errors.Dari ? <p className="text-sm text-destructive">{form.errors.Dari}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Kode">Kode</Label>
            <Select value={form.data.Kode} onValueChange={(v) => form.setData('Kode', v)}>
              <SelectTrigger id="Kode">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Kode.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {butuhTujuan ? (
            <div className="grid gap-2">
              <Label htmlFor="Ke">Ke</Label>
              <Input
                id="Ke"
                value={form.data.Ke}
                onChange={(e) => form.setData('Ke', e.target.value)}
                placeholder="/halaman-baru"
              />
              {form.errors.Ke ? <p className="text-sm text-destructive">{form.errors.Ke}</p> : null}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">
              Kode 410 menyatakan halaman hilang permanen, jadi tidak punya tujuan.
            </p>
          )}

          <div className="grid gap-2">
            <Label htmlFor="Catatan">Catatan</Label>
            <Input
              id="Catatan"
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
            />
          </div>

          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Aktif
          </label>

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
