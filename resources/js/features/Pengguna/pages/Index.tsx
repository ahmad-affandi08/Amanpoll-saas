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
import { useIzin } from '@/hooks/use-izin';
import type { Pengguna, PeranRingkas } from '@/features/Pengguna/types';
import { rutePengguna } from '@/features/Pengguna/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';

interface Props {
  pengguna: Paginasi<Pengguna>;
  filter: FilterDaftar;
  peranTersedia: PeranRingkas[];
}

const kosong = {
  Nama: '',
  Email: '',
  KataSandi: '',
  Telepon: '',
  NomorPegawai: '',
  Jabatan: '',
  JenisPengguna: 'Internal' as const,
};

function DialogFormPengguna({ pengguna }: { pengguna: Pengguna | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    pengguna
      ? {
          Nama: pengguna.Nama,
          Email: pengguna.Email,
          KataSandi: '',
          Telepon: pengguna.Telepon ?? '',
          NomorPegawai: pengguna.NomorPegawai ?? '',
          Jabatan: pengguna.Jabatan ?? '',
          JenisPengguna: pengguna.JenisPengguna,
        }
      : kosong,
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (pengguna) {
      form.put(rutePengguna.detail(pengguna.Id), opsi);
    } else {
      form.post(rutePengguna.index, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={pengguna ? 'outline' : 'default'} size={pengguna ? 'sm' : 'default'}>
          {pengguna ? 'Ubah' : 'Tambah Pengguna'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{pengguna ? 'Ubah Pengguna' : 'Tambah Pengguna'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label>Email</Label>
              <Input
                type="email"
                value={form.data.Email}
                onChange={(e) => form.setData('Email', e.target.value)}
              />
              {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>{pengguna ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi'}</Label>
            <Input
              type="password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
            />
            {form.errors.KataSandi && <p className="text-sm text-destructive">{form.errors.KataSandi}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Telepon</Label>
              <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Nomor Pegawai</Label>
              <Input
                value={form.data.NomorPegawai}
                onChange={(e) => form.setData('NomorPegawai', e.target.value)}
              />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Jabatan</Label>
              <Input value={form.data.Jabatan} onChange={(e) => form.setData('Jabatan', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Jenis Pengguna</Label>
              <Select
                value={form.data.JenisPengguna}
                onValueChange={(v) => form.setData('JenisPengguna', v as 'Internal' | 'Eksternal')}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Internal">Internal</SelectItem>
                  <SelectItem value="Eksternal">Eksternal</SelectItem>
                </SelectContent>
              </Select>
            </div>
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

function DialogKelolaPeran({
  pengguna,
  peranTersedia,
}: {
  pengguna: Pengguna;
  peranTersedia: PeranRingkas[];
}) {
  const [buka, setBuka] = useState(false);
  const [peranTerpilih, setPeranTerpilih] = useState('');

  const tambahkan = () => {
    if (!peranTerpilih) return;
    router.post(
      rutePengguna.peran(pengguna.Id),
      { PeranId: peranTerpilih },
      {
        preserveScroll: true,
        onSuccess: () => setPeranTerpilih(''),
      },
    );
  };

  const cabut = (penggunaPeranId: string) => {
    router.delete(rutePengguna.peranDetail(penggunaPeranId), { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Kelola Peran
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Peran untuk {pengguna.Nama}</DialogTitle>
        </DialogHeader>
        <div className="space-y-3">
          {pengguna.Peran.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada peran ditetapkan.</p>
          )}
          {pengguna.Peran.map((p) => (
            <div
              key={p.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <span className="text-sm">{p.NamaPeran}</span>
              <Button variant="ghost" size="sm" onClick={() => cabut(p.Id)}>
                Cabut
              </Button>
            </div>
          ))}
          <div className="flex gap-2 pt-2">
            <Select value={peranTerpilih} onValueChange={setPeranTerpilih}>
              <SelectTrigger className="flex-1">
                <SelectValue placeholder="Pilih peran" />
              </SelectTrigger>
              <SelectContent>
                {peranTersedia.map((p) => (
                  <SelectItem key={p.Id} value={p.Id}>
                    {p.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button onClick={tambahkan}>Tetapkan</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default function PenggunaIndex({ pengguna, peranTersedia, filter }: Props) {
  const { boleh } = useIzin();
  const bolehKelola = boleh('Pengguna.Kelola');

  const ubahStatus = (item: Pengguna) => {
    const statusBaru = item.Status === 'Aktif' ? 'Nonaktif' : 'Aktif';
    router.put(rutePengguna.status(item.Id), { Status: statusBaru }, { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Pengguna>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Email}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Link href={rutePengguna.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="text-sm text-muted-foreground">{row.original.Email}</div>
          </Link>
        ),
        meta: { label: 'Nama' },
      },
      {
        accessorKey: 'Jabatan',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jabatan" />,
        cell: ({ row }) => row.original.Jabatan ?? '—',
        meta: { label: 'Jabatan' },
      },
      {
        id: 'JenisPengguna',
        accessorKey: 'JenisPengguna',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        cell: ({ row }) => row.original.JenisPengguna,
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Jenis' },
      },
      {
        id: 'Peran',
        header: 'Peran',
        accessorFn: (row) => row.Peran.map((p) => p.NamaPeran).join(', '),
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            {row.original.Peran.map((p) => (
              <Badge key={p.Id} variant="secondary">
                {p.NamaPeran}
              </Badge>
            ))}
          </div>
        ),
        enableSorting: false,
        meta: { label: 'Peran' },
      },
      {
        accessorKey: 'Status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>
            {row.original.Status}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      ...(bolehKelola
        ? [
            {
              id: 'aksi',
              header: 'Aksi',
              cell: ({ row }: { row: { original: Pengguna } }) => (
                <div className="flex justify-end gap-2">
                  <DialogFormPengguna pengguna={row.original} />
                  <DialogKelolaPeran pengguna={row.original} peranTersedia={peranTersedia} />
                  <Button variant="ghost" size="sm" onClick={() => ubahStatus(row.original)}>
                    {row.original.Status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                  </Button>
                </div>
              ),
              enableSorting: false,
              enableHiding: false,
              meta: { label: 'Aksi' },
            } satisfies ColumnDef<Pengguna>,
          ]
        : []),
    ],
    [bolehKelola, peranTersedia],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Pengguna" />
      <KepalaHalaman
        judul="Pengguna"
        deskripsi="Kelola akun pengguna dan penetapan peran."
        aksi={<>{bolehKelola && <DialogFormPengguna pengguna={null} />}</>}
        className="mb-6"
      />

      {pengguna.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/pengguna.webp"
          judul="Belum ada pengguna."
          deskripsi="Tambahkan pengguna pertama untuk memberi akses ke sistem."
        />
      ) : (
        <DataTable
          columns={columns}
          data={pengguna.data}
          server={{ meta: pengguna.meta, filter }}
          pencarianPlaceholder="Cari nama, email, jabatan..."
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
              columnId: 'JenisPengguna',
              title: 'Jenis',
              options: [
                { label: 'Internal', value: 'Internal' },
                { label: 'Eksternal', value: 'Eksternal' },
              ],
            },
          ]}
          pesanKosong="Tidak ada pengguna yang cocok."
          ilustrasiKosong="/assets/3d/pengguna.webp"
        />
      )}
    </KerangkaAplikasi>
  );
}
