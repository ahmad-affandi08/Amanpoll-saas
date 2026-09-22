import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { KategoriAset } from '@/features/Aset/types';
import { ruteKategoriAset } from '@/features/KategoriAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';

/** Hanya Id dan Nama: pemilih induk memuat seluruh kategori, bukan barisnya. */
interface IndukRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  kategoriAset: Paginasi<KategoriAset>;
  pilihanInduk: IndukRingkas[];
  filter: FilterDaftar;
}

function DialogFormKategoriAset({
  kategori,
  semuaKategori,
}: {
  kategori: KategoriAset | null;
  semuaKategori: IndukRingkas[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    kategori
      ? {
          Kode: kategori.Kode,
          Nama: kategori.Nama,
          IndukId: kategori.IndukId ?? TANPA_PILIHAN,
          UmurManfaatBulan: kategori.UmurManfaatBulan?.toString() ?? '',
          MetodePenyusutanBawaan: kategori.MetodePenyusutanBawaan ?? '',
          PersentaseNilaiResidu: kategori.PersentaseNilaiResidu ?? '',
          MemerlukanKalibrasi: kategori.MemerlukanKalibrasi,
          MemerlukanPemeliharaan: kategori.MemerlukanPemeliharaan,
        }
      : {
          Kode: '',
          Nama: '',
          IndukId: TANPA_PILIHAN,
          UmurManfaatBulan: '',
          MetodePenyusutanBawaan: '',
          PersentaseNilaiResidu: '',
          MemerlukanKalibrasi: false,
          MemerlukanPemeliharaan: true,
        },
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
      router.put(ruteKategoriAset.detail(kategori.Id), payload, opsi);
    } else {
      router.post(ruteKategoriAset.index, payload, opsi);
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
          <DialogTitle>{kategori ? 'Ubah Kategori Aset' : 'Tambah Kategori Aset'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
            />
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>Kategori Induk</Label>
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
            {form.errors.IndukId && <p className="text-sm text-destructive">{form.errors.IndukId}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Umur Manfaat (bulan)</Label>
              <Input
                type="number"
                min={1}
                value={form.data.UmurManfaatBulan}
                onChange={(e) => form.setData('UmurManfaatBulan', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Metode Penyusutan Bawaan</Label>
              <Input
                value={form.data.MetodePenyusutanBawaan}
                onChange={(e) => form.setData('MetodePenyusutanBawaan', e.target.value)}
                placeholder="mis. GarisLurus"
              />
            </div>
          </div>
          <div className="space-y-2">
            <Label>Persentase Nilai Residu (%)</Label>
            <Input
              type="number"
              min={0}
              max={100}
              value={form.data.PersentaseNilaiResidu}
              onChange={(e) => form.setData('PersentaseNilaiResidu', e.target.value)}
            />
          </div>
          <div className="flex gap-6">
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.MemerlukanKalibrasi}
                onCheckedChange={(v) => form.setData('MemerlukanKalibrasi', v === true)}
              />
              Memerlukan Kalibrasi
            </label>
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.MemerlukanPemeliharaan}
                onCheckedChange={(v) => form.setData('MemerlukanPemeliharaan', v === true)}
              />
              Memerlukan Pemeliharaan
            </label>
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

export default function KategoriAsetIndex({ kategoriAset, pilihanInduk, filter }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: KategoriAset) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kategori "${item.Nama}"?`,
        deskripsi: 'Kategori yang masih dipakai aset atau memiliki subkategori tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteKategoriAset.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KategoriAset>[]>(
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
        id: 'Kebutuhan',
        header: 'Kebutuhan',
        cell: ({ row }) => (
          <div className="flex gap-1">
            {row.original.MemerlukanKalibrasi && <Badge variant="secondary">Kalibrasi</Badge>}
            {row.original.MemerlukanPemeliharaan && <Badge variant="secondary">Pemeliharaan</Badge>}
          </div>
        ),
        meta: { label: 'Kebutuhan' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormKategoriAset kategori={row.original} semuaKategori={pilihanInduk} />
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
    [kategoriAset],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kategori Aset" />
      <KepalaHalaman
        judul="Kategori Aset"
        deskripsi="Klasifikasi aset beserta default penyusutan dan kebutuhan pemeliharaan/kalibrasi."
        aksi={
          <>
            <DialogFormKategoriAset kategori={null} semuaKategori={pilihanInduk} />
          </>
        }
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kategoriAset.data}
        server={{ meta: kategoriAset.meta, filter }}
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong={
          adaPenyaringAktif(filter) ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori aset.'
        }
      />
    </KerangkaAplikasi>
  );
}
