import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { AlurPersetujuan, JenisPenyetuju, TahapPersetujuan } from '@/features/Persetujuan/types';
import type { Peran } from '@/features/PeranIzin/types';
import type { Pengguna } from '@/features/Pengguna/types';
import { ruteAlurPersetujuan } from '@/features/AlurPersetujuan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  alurPersetujuan: AlurPersetujuan[];
  jenisEntitasTersedia: string[];
  peran: Peran[];
  pengguna: Pengguna[];
}

const JENIS_PENYETUJU: JenisPenyetuju[] = ['Pengguna', 'Peran', 'Unit'];
const TANPA = '__tanpa__';

function DialogFormAlur({
  alur,
  jenisEntitasTersedia,
}: {
  alur: AlurPersetujuan | null;
  jenisEntitasTersedia: string[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: alur?.Kode ?? '',
    Nama: alur?.Nama ?? '',
    JenisEntitas: alur?.JenisEntitas ?? jenisEntitasTersedia[0] ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!alur) form.reset();
      },
    };
    if (alur) {
      router.put(ruteAlurPersetujuan.detail(alur.Id), form.data, opsi);
    } else {
      router.post(ruteAlurPersetujuan.index, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={alur ? 'outline' : 'default'} size={alur ? 'sm' : 'default'}>
          {alur ? 'Ubah' : 'Tambah Alur'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{alur ? 'Ubah Alur Persetujuan' : 'Tambah Alur Persetujuan'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
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
          <div className="space-y-2">
            <Label>Jenis Entitas</Label>
            <Select value={form.data.JenisEntitas} onValueChange={(v) => form.setData('JenisEntitas', v)}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {jenisEntitasTersedia.map((j) => (
                  <SelectItem key={j} value={j}>
                    {j}
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
      </DialogContent>
    </Dialog>
  );
}

function FormTahap({
  alurPersetujuanId,
  tahap,
  peran,
  pengguna,
  onSelesai,
}: {
  alurPersetujuanId: string;
  tahap: TahapPersetujuan | null;
  peran: Peran[];
  pengguna: Pengguna[];
  onSelesai: () => void;
}) {
  const form = useForm({
    Urutan: tahap?.Urutan ?? 1,
    Nama: tahap?.Nama ?? '',
    JenisPenyetuju: tahap?.JenisPenyetuju ?? ('Pengguna' as JenisPenyetuju),
    PeranId: tahap?.PeranId ?? '',
    PenggunaId: tahap?.PenggunaId ?? '',
    JumlahMinimumPenyetuju: tahap?.JumlahMinimumPenyetuju ?? 1,
    BolehMenyetujuiSendiri: tahap?.BolehMenyetujuiSendiri ?? false,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      PeranId: form.data.JenisPenyetuju === 'Peran' ? form.data.PeranId : null,
      PenggunaId: form.data.JenisPenyetuju === 'Pengguna' ? form.data.PenggunaId : null,
    };
    const opsi = {
      onSuccess: () => {
        onSelesai();
        if (!tahap) form.reset();
      },
    };
    if (tahap) {
      router.put(`/persetujuan/tahap/${tahap.Id}`, payload, opsi);
    } else {
      router.post(ruteAlurPersetujuan.tahap(alurPersetujuanId), payload, opsi);
    }
  };

  return (
    <form onSubmit={submit} className="grid grid-cols-2 gap-3 rounded-md border border-border p-3">
      <div className="space-y-1.5">
        <Label>Urutan</Label>
        <Input
          type="number"
          min={1}
          value={form.data.Urutan}
          onChange={(e) => form.setData('Urutan', Number(e.target.value))}
        />
      </div>
      <div className="space-y-1.5">
        <Label>Nama Tahap</Label>
        <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
      </div>
      <div className="space-y-1.5">
        <Label>Jenis Penyetuju</Label>
        <Select
          value={form.data.JenisPenyetuju}
          onValueChange={(v) => form.setData('JenisPenyetuju', v as JenisPenyetuju)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {JENIS_PENYETUJU.map((j) => (
              <SelectItem key={j} value={j}>
                {j}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      {form.data.JenisPenyetuju === 'Pengguna' && (
        <div className="space-y-1.5">
          <Label>Pengguna</Label>
          <Select
            value={form.data.PenggunaId || TANPA}
            onValueChange={(v) => form.setData('PenggunaId', v === TANPA ? '' : v)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Pilih pengguna" />
            </SelectTrigger>
            <SelectContent>
              {pengguna.map((p) => (
                <SelectItem key={p.Id} value={p.Id}>
                  {p.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}
      {(form.data.JenisPenyetuju === 'Peran' || form.data.JenisPenyetuju === 'Unit') && (
        <div className="space-y-1.5">
          <Label>Peran {form.data.JenisPenyetuju === 'Unit' && '(opsional)'}</Label>
          <Select
            value={form.data.PeranId || TANPA}
            onValueChange={(v) => form.setData('PeranId', v === TANPA ? '' : v)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Pilih peran" />
            </SelectTrigger>
            <SelectContent>
              {form.data.JenisPenyetuju === 'Unit' && <SelectItem value={TANPA}>Semua peran</SelectItem>}
              {peran.map((p) => (
                <SelectItem key={p.Id} value={p.Id}>
                  {p.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}
      <div className="space-y-1.5">
        <Label>Jumlah Minimum Penyetuju</Label>
        <Input
          type="number"
          min={1}
          value={form.data.JumlahMinimumPenyetuju}
          onChange={(e) => form.setData('JumlahMinimumPenyetuju', Number(e.target.value))}
        />
      </div>
      <label className="flex items-center gap-2 self-end pb-2 text-sm">
        <Checkbox
          checked={form.data.BolehMenyetujuiSendiri}
          onCheckedChange={(v) => form.setData('BolehMenyetujuiSendiri', Boolean(v))}
        />
        Boleh menyetujui permintaan sendiri
      </label>
      <div className="col-span-2 flex justify-end">
        <Button type="submit" size="sm" disabled={form.processing}>
          {tahap ? 'Simpan Perubahan' : 'Tambah Tahap'}
        </Button>
      </div>
    </form>
  );
}

function DialogKelolaTahap({
  alur,
  peran,
  pengguna,
}: {
  alur: AlurPersetujuan;
  peran: Peran[];
  pengguna: Pengguna[];
}) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const [mengedit, setMengedit] = useState<TahapPersetujuan | null>(null);

  const hapus = async (tahap: TahapPersetujuan) => {
    if (
      !(await konfirmasi({
        judul: `Hapus tahap "${tahap.Nama}"?`,
        deskripsi: 'Urutan tahap sesudahnya akan bergeser naik.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(`/persetujuan/tahap/${tahap.Id}`, { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Kelola Tahap
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Tahap Persetujuan -- {alur.Nama}</DialogTitle>
        </DialogHeader>
        {alur.Aktif && (
          <p className="rounded-md bg-amber-50 p-3 text-sm text-amber-800">
            Nonaktifkan alur ini terlebih dahulu untuk mengubah tahapnya.
          </p>
        )}
        <div className="space-y-2">
          {alur.TahapPersetujuan.map((tahap) => (
            <div
              key={tahap.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div className="text-sm">
                <span className="font-medium text-foreground">
                  {tahap.Urutan}. {tahap.Nama}
                </span>
                <span className="ml-2 text-muted-foreground">
                  ({tahap.JenisPenyetuju}
                  {tahap.NamaPengguna ? `: ${tahap.NamaPengguna}` : ''}
                  {tahap.NamaPeran ? `: ${tahap.NamaPeran}` : ''}, min. {tahap.JumlahMinimumPenyetuju})
                </span>
              </div>
              {!alur.Aktif && (
                <div className="flex gap-2">
                  <Button variant="ghost" size="sm" onClick={() => setMengedit(tahap)}>
                    Ubah
                  </Button>
                  <Button variant="ghost" size="sm" onClick={() => hapus(tahap)}>
                    Hapus
                  </Button>
                </div>
              )}
            </div>
          ))}
          {alur.TahapPersetujuan.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada tahap.</p>
          )}
        </div>
        {!alur.Aktif && (
          <div className="border-t border-border pt-4">
            <p className="mb-2 text-sm font-medium text-foreground">
              {mengedit ? `Ubah Tahap ${mengedit.Urutan}` : 'Tambah Tahap Baru'}
            </p>
            <FormTahap
              key={mengedit?.Id ?? 'baru'}
              alurPersetujuanId={alur.Id}
              tahap={mengedit}
              peran={peran}
              pengguna={pengguna}
              onSelesai={() => setMengedit(null)}
            />
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

export default function AlurPersetujuanIndex({
  alurPersetujuan,
  jenisEntitasTersedia,
  peran,
  pengguna,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (alur: AlurPersetujuan) => {
    if (
      !(await konfirmasi({
        judul: `Hapus alur "${alur.Nama}"?`,
        deskripsi: 'Entitas yang memakai alur ini tidak dapat diajukan sampai ada alur aktif pengganti.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAlurPersetujuan.detail(alur.Id), { preserveScroll: true });
  };

  const toggleAktif = (alur: AlurPersetujuan) => {
    const url = alur.Aktif ? ruteAlurPersetujuan.nonaktifkan(alur.Id) : ruteAlurPersetujuan.aktifkan(alur.Id);
    router.post(url, {}, { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<AlurPersetujuan>[]>(
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
        accessorKey: 'JenisEntitas',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis Entitas" />,
        meta: { label: 'Jenis Entitas' },
      },
      {
        id: 'JumlahTahap',
        accessorFn: (row) => row.TahapPersetujuan.length,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap" />,
        cell: ({ row }) => row.original.TahapPersetujuan.length,
        meta: { label: 'Tahap' },
      },
      {
        id: 'Aktif',
        accessorFn: (row) => (row.Aktif ? 'Aktif' : 'Nonaktif'),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'default' : 'outline'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogKelolaTahap alur={row.original} peran={peran} pengguna={pengguna} />
            <DialogFormAlur alur={row.original} jenisEntitasTersedia={jenisEntitasTersedia} />
            <Button variant="outline" size="sm" onClick={() => toggleAktif(row.original)}>
              {row.original.Aktif ? 'Nonaktifkan' : 'Aktifkan'}
            </Button>
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
    [jenisEntitasTersedia, peran, pengguna],
  );

  return (
    <AppLayout>
      <Head title="Alur Persetujuan" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Alur Persetujuan</h1>
          <p className="text-sm text-muted-foreground">
            Definisikan tahapan persetujuan untuk berbagai jenis entitas.
          </p>
        </div>
        <DialogFormAlur alur={null} jenisEntitasTersedia={jenisEntitasTersedia} />
      </div>

      <DataTable
        columns={columns}
        data={alurPersetujuan}
        pencarianPlaceholder="Cari nama atau kode alur..."
        facetedFilters={[
          {
            columnId: 'Aktif',
            title: 'Status',
            options: [
              { label: 'Aktif', value: 'Aktif' },
              { label: 'Nonaktif', value: 'Nonaktif' },
            ],
          },
        ]}
        pesanKosong="Belum ada alur persetujuan."
        ilustrasiKosong="/assets/3d/persetujuan-kepatuhan.webp"
      />
    </AppLayout>
  );
}
