import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
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
import { Switch } from '@/components/ui/switch';
import type { KategoriKeluhan, PrioritasKeluhan } from '@/features/Keluhan/types';
import { ruteKategoriKeluhan } from '@/features/KategoriKeluhan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface Props {
  kategori: Paginasi<KategoriKeluhan>;
  // Pemilih induk memuat seluruh kategori, bukan hanya baris di halaman ini.
  pilihanInduk: Ringkas[];
  tingkatLayanan: Ringkas[];
  peran: Ringkas[];
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}
const PRIORITAS: PrioritasKeluhan[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];

function DialogKategori({
  item,
  kategori,
  tingkatLayanan,
  peran,
  wajib,
}: {
  item: KategoriKeluhan | null;
  kategori: Ringkas[];
  tingkatLayanan: Ringkas[];
  peran: Ringkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: item?.Kode ?? '',
    Nama: item?.Nama ?? '',
    IndukId: item?.IndukId ?? TANPA_PILIHAN,
    TingkatLayananId: item?.TingkatLayananId ?? TANPA_PILIHAN,
    PrioritasBawaan: item?.PrioritasBawaan ?? ('Normal' as PrioritasKeluhan),
    AsetWajib: item?.AsetWajib ?? false,
    PeranPenanggungJawabId: item?.PeranPenanggungJawabId ?? TANPA_PILIHAN,
    Aktif: item?.Aktif ?? true,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    form.transform((data) => ({
      ...data,
      IndukId: data.IndukId === TANPA_PILIHAN ? null : data.IndukId,
      TingkatLayananId: data.TingkatLayananId === TANPA_PILIHAN ? null : data.TingkatLayananId,
      PeranPenanggungJawabId:
        data.PeranPenanggungJawabId === TANPA_PILIHAN ? null : data.PeranPenanggungJawabId,
    }));
    item ? form.put(ruteKategoriKeluhan.detail(item.Id), opsi) : form.post(ruteKategoriKeluhan.index, opsi);
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={item ? 'outline' : 'default'} size={item ? 'sm' : 'default'}>
          {item ? 'Ubah' : 'Tambah Kategori'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{item ? 'Ubah' : 'Tambah'} Kategori Keluhan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-1.5">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="IndukId">Kategori Induk</Label>
              <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Tanpa induk</SelectItem>
                  {kategori
                    .filter((k) => k.Id !== item?.Id)
                    .map((k) => (
                      <SelectItem key={k.Id} value={k.Id}>
                        {k.Nama}
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TingkatLayananId">Tingkat Layanan</Label>
                <Select
                  value={form.data.TingkatLayananId}
                  onValueChange={(v) => form.setData('TingkatLayananId', v)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={TANPA_PILIHAN}>Tanpa SLA</SelectItem>
                    {tingkatLayanan.map((sla) => (
                      <SelectItem key={sla.Id} value={sla.Id}>
                        {sla.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label nama="PrioritasBawaan">Prioritas Bawaan</Label>
                <Select
                  value={form.data.PrioritasBawaan}
                  onValueChange={(v) => form.setData('PrioritasBawaan', v as PrioritasKeluhan)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {PRIORITAS.map((p) => (
                      <SelectItem key={p} value={p}>
                        {p}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="PeranPenanggungJawabId">Routing ke Peran</Label>
              <Select
                value={form.data.PeranPenanggungJawabId}
                onValueChange={(v) => form.setData('PeranPenanggungJawabId', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Tanpa routing</SelectItem>
                  {peran.map((p) => (
                    <SelectItem key={p.Id} value={p.Id}>
                      {p.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex flex-wrap gap-6">
              <label className="flex items-center gap-2 text-sm">
                <Switch checked={form.data.AsetWajib} onCheckedChange={(v) => form.setData('AsetWajib', v)} />{' '}
                Aset wajib
              </label>
              <label className="flex items-center gap-2 text-sm">
                <Switch checked={form.data.Aktif} onCheckedChange={(v) => form.setData('Aktif', v)} /> Aktif
              </label>
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

export default function KategoriKeluhanIndex({
  kategori,
  pilihanInduk,
  tingkatLayanan,
  peran,
  filter,
  wajib,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const columns = useMemo<ColumnDef<KategoriKeluhan>[]>(
    () => [
      {
        id: 'nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Kategori' },
      },
      {
        accessorKey: 'PrioritasBawaan',
        header: 'Prioritas',
        cell: ({ row }) => <Badge>{row.original.PrioritasBawaan}</Badge>,
      },
      {
        id: 'sla',
        accessorFn: (row) => row.NamaTingkatLayanan ?? '',
        header: 'SLA',
        cell: ({ row }) => row.original.NamaTingkatLayanan ?? '—',
      },
      {
        id: 'aturan',
        header: 'Aturan',
        cell: ({ row }) => (
          <div className="text-sm">
            <div>{row.original.AsetWajib ? 'Aset wajib' : 'Aset opsional'}</div>
            <div className="text-muted-foreground">
              {row.original.NamaPeranPenanggungJawab ?? 'Tanpa routing'}
            </div>
          </div>
        ),
      },
      {
        id: 'status',
        accessorFn: (row) => (row.Aktif ? 'Aktif' : 'Nonaktif'),
        header: 'Status',
        cell: ({ row }) => (
          <Badge variant={row.original.Aktif ? 'sukses' : 'netral'}>
            {row.original.Aktif ? 'Aktif' : 'Nonaktif'}
          </Badge>
        ),
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogKategori
              item={row.original}
              kategori={pilihanInduk}
              tingkatLayanan={tingkatLayanan}
              peran={peran}
              wajib={wajib.kategoriKeluhan}
            />
            <Button
              variant="ghost"
              size="sm"
              onClick={async () => {
                const lanjut = await konfirmasi({
                  judul: `Hapus kategori "${row.original.Nama}"?`,
                  deskripsi:
                    'Keluhan yang sudah memakai kategori ini tetap tersimpan dengan kategori kosong.',
                  ragam: 'bahaya',
                });
                if (lanjut) router.delete(ruteKategoriKeluhan.detail(row.original.Id));
              }}
            >
              Hapus
            </Button>
          </div>
        ),
      },
    ],
    [pilihanInduk, tingkatLayanan, peran, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kategori Keluhan" />
      <KepalaHalaman
        judul="Kategori Keluhan"
        deskripsi="Atur prioritas bawaan, kebutuhan aset, SLA, dan routing triage."
        aksi={
          <>
            <DialogKategori
              item={null}
              kategori={pilihanInduk}
              tingkatLayanan={tingkatLayanan}
              peran={peran}
              wajib={wajib.kategoriKeluhan}
            />
          </>
        }
        className="mb-6"
      />
      <DataTable
        columns={columns}
        data={kategori.data}
        server={{ meta: kategori.meta, filter }}
        facetedFilters={[
          {
            columnId: 'PrioritasBawaan',
            title: 'Prioritas',
            options: PRIORITAS.map((satu) => ({ label: satu, value: satu })),
          },
        ]}
        pencarianPlaceholder="Cari nama atau kode kategori..."
        pesanKosong={
          adaPenyaringAktif(filter) ? 'Tidak ada kategori yang cocok.' : 'Belum ada kategori keluhan.'
        }
      />
    </KerangkaAplikasi>
  );
}
