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
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteUnitOrganisasi } from '@/features/UnitOrganisasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { Switch } from '@/components/ui/switch';
import { opsiDari } from '@/lib/pilihan';

/** Hanya Id dan Nama: pemilih induk memuat seluruh unit, bukan barisnya. */
interface IndukRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  unitOrganisasi: Paginasi<UnitOrganisasi>;
  pilihanInduk: IndukRingkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const TANPA_INDUK = '__tanpa_induk__';

function DialogFormUnit({
  unit,
  semuaUnit,
  wajib,
}: {
  unit: UnitOrganisasi | null;
  semuaUnit: IndukRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    unit
      ? {
          Kode: unit.Kode,
          Nama: unit.Nama,
          Jenis: unit.Jenis,
          Status: unit.Status,
          IndukId: unit.IndukId ?? TANPA_INDUK,
          MengelolaAset: unit.MengelolaAset,
        }
      : {
          Kode: '',
          Nama: '',
          Jenis: 'Unit',
          Status: 'Aktif' as const,
          IndukId: TANPA_INDUK,
          MengelolaAset: false,
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, IndukId: form.data.IndukId === TANPA_INDUK ? null : form.data.IndukId };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (unit) {
      router.put(ruteUnitOrganisasi.detail(unit.Id), payload, opsi);
    } else {
      router.post(ruteUnitOrganisasi.index, payload, opsi);
    }
  };

  const pilihanInduk = semuaUnit.filter((u) => u.Id !== unit?.Id);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={unit ? 'outline' : 'default'} size={unit ? 'sm' : 'default'}>
          {unit ? 'Ubah' : 'Tambah Unit'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{unit ? 'Ubah Unit Organisasi' : 'Tambah Unit Organisasi'}</DialogTitle>
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
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="Jenis">Jenis</Label>
                <Input
                  value={form.data.Jenis}
                  onChange={(e) => form.setData('Jenis', e.target.value)}
                  placeholder="Divisi, Departemen, dst."
                />
              </div>
              <div className="space-y-2">
                <Label nama="Status">Status</Label>
                <Select
                  value={form.data.Status}
                  onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Aktif">Aktif</SelectItem>
                    <SelectItem value="Nonaktif">Nonaktif</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="IndukId">Induk</Label>
              <Combobox
                nilai={form.data.IndukId}
                onPilih={(v) => form.setData('IndukId', v)}
                opsi={[
                  { nilai: TANPA_INDUK, label: 'Tanpa induk' },
                  ...opsiDari(pilihanInduk, (u) => u.Nama),
                ]}
              />
              {form.errors.IndukId && <p className="text-sm text-destructive">{form.errors.IndukId}</p>}
            </div>
            <div className="space-y-1">
              <label className="flex items-center gap-2 text-sm font-medium">
                <Switch
                  checked={form.data.MengelolaAset}
                  onCheckedChange={(v) => form.setData('MengelolaAset', v)}
                />
                Mengelola aset (unit pengelola pemeliharaan)
              </label>
              <p className="text-xs text-muted-foreground">
                Aktifkan untuk bagian yang memelihara aset, mis. IPSRS atau IT. Unit bertanda ini muncul
                sebagai pilihan unit pengelola di aset, gudang, kategori keluhan, dan tiket.
              </p>
              {form.errors.MengelolaAset && (
                <p className="text-sm text-destructive">{form.errors.MengelolaAset}</p>
              )}
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

export default function UnitOrganisasiIndex({ unitOrganisasi, pilihanInduk, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  // Dipetakan dari daftar penuh: induk sebuah unit bisa saja ada di halaman lain.
  const namaIndukDari = useMemo(() => {
    const peta = new Map(pilihanInduk.map((u) => [u.Id, u.Nama]));
    return (indukId: string | null) => (indukId ? (peta.get(indukId) ?? '—') : '—');
  }, [pilihanInduk]);

  const hapus = async (unit: UnitOrganisasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus unit "${unit.Nama}"?`,
        deskripsi: 'Unit yang masih memiliki sub-unit, pengguna, atau aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteUnitOrganisasi.detail(unit.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<UnitOrganisasi>[]>(
    () => [
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        meta: { label: 'Nama' },
      },
      {
        accessorKey: 'Kode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.Kode}</span>,
        meta: { label: 'Kode' },
      },
      {
        accessorKey: 'Jenis',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        meta: { label: 'Jenis' },
      },
      {
        id: 'Induk',
        accessorFn: (row) => namaIndukDari(row.IndukId),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Induk" />,
        meta: { label: 'Induk' },
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
      {
        accessorKey: 'MengelolaAset',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Unit Pengelola" />,
        cell: ({ row }) =>
          row.original.MengelolaAset ? (
            <Badge variant="info">Mengelola aset</Badge>
          ) : (
            <span className="text-muted-foreground">—</span>
          ),
        enableSorting: false,
        meta: { label: 'Unit Pengelola' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormUnit unit={row.original} semuaUnit={pilihanInduk} wajib={wajib.unit} />
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
    [unitOrganisasi, namaIndukDari, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Unit Organisasi" />
      <KepalaHalaman
        judul="Unit Organisasi"
        deskripsi="Kelola struktur divisi dan hierarki organisasi."
        aksi={
          <>
            <DialogFormUnit unit={null} semuaUnit={pilihanInduk} wajib={wajib.unit} />
          </>
        }
        className="mb-5"
      />

      <DataTable
        columns={columns}
        data={unitOrganisasi.data}
        server={{ meta: unitOrganisasi.meta, filter }}
        ekspor="/platform/unit-organisasi/ekspor"
        pencarianPlaceholder="Cari nama, kode, atau email unit..."
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
            columnId: 'MengelolaAset',
            title: 'Unit Pengelola',
            options: [
              { label: 'Mengelola aset', value: '1' },
              { label: 'Bukan unit pengelola', value: '0' },
            ],
          },
        ]}
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada unit yang cocok.' : 'Belum ada unit organisasi.'}
      />
    </KerangkaAplikasi>
  );
}
