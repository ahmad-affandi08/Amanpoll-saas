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
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import type { Lokasi, KategoriLokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteLokasi } from '@/features/Lokasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface Props {
  lokasi: Paginasi<Lokasi>;
  filter: FilterDaftar;
  unitOrganisasi: UnitOrganisasi[];
  kategoriLokasi: KategoriLokasi[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogKelolaKategori({
  kategoriLokasi,
  wajib,
}: {
  kategoriLokasi: KategoriLokasi[];
  wajib: AturanWajib;
}) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '', Keterangan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteLokasi.kategori, { onSuccess: () => form.reset(), preserveScroll: true });
  };

  const hapus = async (kategori: KategoriLokasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kategori "${kategori.Nama}"?`,
        deskripsi: 'Lokasi yang memakai kategori ini kehilangan penandaannya.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteLokasi.kategoriDetail(kategori.Id), { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Kelola Kategori</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kategori Lokasi</DialogTitle>
        </DialogHeader>
        <div className="space-y-2">
          {kategoriLokasi.map((k) => (
            <div
              key={k.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div>
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{k.Kode}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(k)}>
                Hapus
              </Button>
            </div>
          ))}
          {kategoriLokasi.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada kategori.</p>
          )}
        </div>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="flex gap-2 border-t border-border pt-4">
            <Input
              placeholder="Nama kategori"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              className="flex-1"
            />
            <Button type="submit" disabled={form.processing}>
              Tambah
            </Button>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogFormLokasi({
  lokasi,
  unitOrganisasi,
  kategoriLokasi,
  wajib,
}: {
  lokasi: Lokasi | null;
  unitOrganisasi: UnitOrganisasi[];
  kategoriLokasi: KategoriLokasi[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    lokasi
      ? {
          Kode: lokasi.Kode,
          KodeRuangAspak: lokasi.KodeRuangAspak ?? '',
          Nama: lokasi.Nama,
          Alamat: lokasi.Alamat ?? '',
          Lantai: lokasi.Lantai ?? '',
          Status: lokasi.Status,
          UnitOrganisasiId: lokasi.UnitOrganisasiId ?? TANPA_PILIHAN,
          KategoriLokasiId: lokasi.KategoriLokasiId ?? TANPA_PILIHAN,
        }
      : {
          Kode: '',
          KodeRuangAspak: '',
          Nama: '',
          Alamat: '',
          Lantai: '',
          Status: 'Aktif' as const,
          UnitOrganisasiId: TANPA_PILIHAN,
          KategoriLokasiId: TANPA_PILIHAN,
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      UnitOrganisasiId: form.data.UnitOrganisasiId === TANPA_PILIHAN ? null : form.data.UnitOrganisasiId,
      KategoriLokasiId: form.data.KategoriLokasiId === TANPA_PILIHAN ? null : form.data.KategoriLokasiId,
    };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (lokasi) {
      router.put(ruteLokasi.detail(lokasi.Id), payload, opsi);
    } else {
      router.post(ruteLokasi.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={lokasi ? 'outline' : 'default'} size={lokasi ? 'sm' : 'default'}>
          {lokasi ? 'Ubah' : 'Tambah Lokasi'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{lokasi ? 'Ubah Lokasi' : 'Tambah Lokasi'}</DialogTitle>
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
              <Label nama="KodeRuangAspak">Kode Ruang ASPAK</Label>
              <Input
                value={form.data.KodeRuangAspak}
                onChange={(e) => form.setData('KodeRuangAspak', e.target.value)}
                placeholder="Kosongkan bila ruang ini tidak dilaporkan ke ASPAK"
              />
              {form.errors.KodeRuangAspak && (
                <p className="text-sm text-destructive">{form.errors.KodeRuangAspak}</p>
              )}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="UnitOrganisasiId">Unit Organisasi</Label>
                <Combobox
                  nilai={form.data.UnitOrganisasiId}
                  onPilih={(v) => form.setData('UnitOrganisasiId', v)}
                  opsi={[opsiKosong('Tidak ditautkan'), ...opsiDari(unitOrganisasi, (u) => u.Nama)]}
                />
              </div>
              <div className="space-y-2">
                <Label nama="KategoriLokasiId">Kategori</Label>
                <Combobox
                  nilai={form.data.KategoriLokasiId}
                  onPilih={(v) => form.setData('KategoriLokasiId', v)}
                  opsi={[opsiKosong('Tanpa kategori'), ...opsiDari(kategoriLokasi, (k) => k.Nama)]}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="Lantai">Lantai</Label>
                <Input value={form.data.Lantai} onChange={(e) => form.setData('Lantai', e.target.value)} />
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
              <Label nama="Alamat">Alamat</Label>
              <Input value={form.data.Alamat} onChange={(e) => form.setData('Alamat', e.target.value)} />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
        {lokasi && <PanelKolaborasi jenisEntitas="Lokasi" entitasId={lokasi.Id} />}
      </DialogContent>
    </Dialog>
  );
}

export default function LokasiIndex({ lokasi, unitOrganisasi, kategoriLokasi, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: Lokasi) => {
    if (
      !(await konfirmasi({
        judul: `Hapus lokasi "${item.Nama}"?`,
        deskripsi: 'Lokasi yang masih memiliki sub-lokasi atau aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteLokasi.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Lokasi>[]>(
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
        id: 'NamaKategoriLokasi',
        accessorFn: (row) => row.NamaKategoriLokasi ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => row.original.NamaKategoriLokasi ?? '—',
        // Turunan relasi: disaring lewat faset, bukan diurutkan.
        enableSorting: false,
        meta: { label: 'Kategori' },
      },
      {
        id: 'NamaUnitOrganisasi',
        accessorFn: (row) => row.NamaUnitOrganisasi ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Unit" />,
        cell: ({ row }) => row.original.NamaUnitOrganisasi ?? '—',
        enableSorting: false,
        meta: { label: 'Unit' },
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
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormLokasi
              lokasi={row.original}
              unitOrganisasi={unitOrganisasi}
              kategoriLokasi={kategoriLokasi}
              wajib={wajib.lokasi}
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
    [unitOrganisasi, kategoriLokasi],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Lokasi" />
      <KepalaHalaman
        judul="Lokasi"
        deskripsi="Kelola lokasi fisik aset dan fasilitas."
        aksi={
          <>
            <div className="flex gap-2">
              <DialogKelolaKategori kategoriLokasi={kategoriLokasi} wajib={wajib.kategori} />
              <DialogFormLokasi
                lokasi={null}
                unitOrganisasi={unitOrganisasi}
                kategoriLokasi={kategoriLokasi}
                wajib={wajib.lokasi}
              />
            </div>
          </>
        }
        className="mb-5"
      />

      {lokasi.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/lokasi.webp"
          judul="Belum ada lokasi."
          deskripsi="Tambahkan lokasi pertama untuk mulai menempatkan aset."
        />
      ) : (
        <DataTable
          columns={columns}
          data={lokasi.data}
          server={{ meta: lokasi.meta, filter }}
          ekspor="/platform/lokasi/ekspor"
          pencarianPlaceholder="Cari nama atau kode lokasi..."
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
              columnId: 'KategoriLokasiId',
              title: 'Kategori',
              options: kategoriLokasi.map((k) => ({ label: k.Nama, value: k.Id })),
            },
            {
              columnId: 'UnitOrganisasiId',
              title: 'Unit',
              options: unitOrganisasi.map((u) => ({ label: u.Nama, value: u.Id })),
            },
          ]}
          pesanKosong="Tidak ada lokasi yang cocok."
          ilustrasiKosong="/assets/3d/lokasi.webp"
        />
      )}
    </KerangkaAplikasi>
  );
}
