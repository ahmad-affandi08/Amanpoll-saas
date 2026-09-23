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
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { KategoriAset, Merek, ModelAset } from '@/features/Aset/types';
import { ruteModelAset } from '@/features/ModelAset/api';
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
  modelAset: Paginasi<ModelAset>;
  filter: FilterDaftar;
  kategoriAset: KategoriAset[];
  merek: Merek[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormModelAset({
  model,
  kategoriAset,
  merek,
  wajib,
}: {
  model: ModelAset | null;
  kategoriAset: KategoriAset[];
  merek: Merek[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const [errorSpesifikasi, setErrorSpesifikasi] = useState<string | null>(null);
  const form = useForm(
    model
      ? {
          KategoriAsetId: model.KategoriAsetId,
          MerekId: model.MerekId ?? TANPA_PILIHAN,
          KodeModel: model.KodeModel ?? '',
          Nama: model.Nama,
          Produsen: model.Produsen ?? '',
          Spesifikasi: model.Spesifikasi ? JSON.stringify(model.Spesifikasi, null, 2) : '',
          IntervalPemeliharaanHari: model.IntervalPemeliharaanHari?.toString() ?? '',
          IntervalKalibrasiHari: model.IntervalKalibrasiHari?.toString() ?? '',
          UmurManfaatBulan: model.UmurManfaatBulan?.toString() ?? '',
        }
      : {
          KategoriAsetId: kategoriAset[0]?.Id ?? '',
          MerekId: TANPA_PILIHAN,
          KodeModel: '',
          Nama: '',
          Produsen: '',
          Spesifikasi: '',
          IntervalPemeliharaanHari: '',
          IntervalKalibrasiHari: '',
          UmurManfaatBulan: '',
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    let spesifikasi: Record<string, string | number | boolean | null> | null = null;
    if (form.data.Spesifikasi.trim() !== '') {
      try {
        spesifikasi = JSON.parse(form.data.Spesifikasi);
      } catch {
        setErrorSpesifikasi('Spesifikasi harus berupa JSON valid, mis. {"Daya": "5 kW"}');
        return;
      }
    }
    setErrorSpesifikasi(null);
    const payload = {
      ...form.data,
      MerekId: form.data.MerekId === TANPA_PILIHAN ? null : form.data.MerekId,
      Spesifikasi: spesifikasi,
    };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        if (!model) form.reset();
      },
    };
    if (model) {
      router.put(ruteModelAset.detail(model.Id), payload, opsi);
    } else {
      router.post(ruteModelAset.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={model ? 'outline' : 'default'} size={model ? 'sm' : 'default'}>
          {model ? 'Ubah' : 'Tambah Model'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{model ? 'Ubah Model Aset' : 'Tambah Model Aset'}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="KategoriAsetId">Kategori</Label>
                <Combobox
                  nilai={form.data.KategoriAsetId}
                  onPilih={(v) => form.setData('KategoriAsetId', v)}
                  opsi={opsiDari(kategoriAset, (k) => k.Nama)}
                />
                {form.errors.KategoriAsetId && (
                  <p className="text-sm text-destructive">{form.errors.KategoriAsetId}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label nama="MerekId">Merek</Label>
                <Combobox
                  nilai={form.data.MerekId}
                  onPilih={(v) => form.setData('MerekId', v)}
                  opsi={[opsiKosong('Tanpa merek'), ...opsiDari(merek, (m) => m.Nama)]}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <BidangKode
                nilai={form.data.KodeModel}
                onUbah={(nilai) => form.setData('KodeModel', nilai)}
                galat={form.errors.KodeModel}
                label="Kode Model"
                id="KodeModel"
              />
              <div className="space-y-2">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="Produsen">Produsen</Label>
              <Input value={form.data.Produsen} onChange={(e) => form.setData('Produsen', e.target.value)} />
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-2">
                <Label nama="IntervalPemeliharaanHari">Interval Pemeliharaan (hari)</Label>
                <Input
                  type="number"
                  min={1}
                  value={form.data.IntervalPemeliharaanHari}
                  onChange={(e) => form.setData('IntervalPemeliharaanHari', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="IntervalKalibrasiHari">Interval Kalibrasi (hari)</Label>
                <Input
                  type="number"
                  min={1}
                  value={form.data.IntervalKalibrasiHari}
                  onChange={(e) => form.setData('IntervalKalibrasiHari', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="UmurManfaatBulan">Umur Manfaat (bulan)</Label>
                <Input
                  type="number"
                  min={1}
                  value={form.data.UmurManfaatBulan}
                  onChange={(e) => form.setData('UmurManfaatBulan', e.target.value)}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="Spesifikasi">Spesifikasi Teknis (JSON, opsional)</Label>
              <Textarea
                value={form.data.Spesifikasi}
                onChange={(e) => form.setData('Spesifikasi', e.target.value)}
                rows={4}
                placeholder='{"Daya": "5 kW", "Tegangan": "220V"}'
                className="font-mono text-xs"
              />
              {errorSpesifikasi && <p className="text-sm text-destructive">{errorSpesifikasi}</p>}
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

export default function ModelAsetIndex({ modelAset, kategoriAset, merek, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapus = async (item: ModelAset) => {
    if (
      !(await konfirmasi({
        judul: `Hapus model "${item.Nama}"?`,
        deskripsi: 'Model yang masih dipakai aset tidak dapat dihapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteModelAset.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<ModelAset>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.KodeModel ?? ''}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            {row.original.KodeModel && (
              <div className="font-mono text-xs text-muted-foreground">{row.original.KodeModel}</div>
            )}
          </div>
        ),
        meta: { label: 'Nama' },
      },
      {
        id: 'NamaKategoriAset',
        accessorFn: (row) => row.NamaKategoriAset ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
        // Turunan relasi: pengurutannya ada di kolom tabel lain, jadi server tidak menawarkannya.
        enableSorting: false,
        meta: { label: 'Kategori' },
      },
      {
        id: 'NamaMerek',
        accessorFn: (row) => row.NamaMerek ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Merek" />,
        cell: ({ row }) => row.original.NamaMerek ?? '—',
        enableSorting: false,
        meta: { label: 'Merek' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogFormModelAset
              model={row.original}
              kategoriAset={kategoriAset}
              merek={merek}
              wajib={wajib.modelAset}
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
    [kategoriAset, merek, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Model Aset" />
      <KepalaHalaman
        judul="Model Aset"
        deskripsi="Master model/tipe aset lengkap dengan metadata teknis dan interval pemeliharaan/kalibrasi."
        aksi={
          <>
            <DialogFormModelAset
              model={null}
              kategoriAset={kategoriAset}
              merek={merek}
              wajib={wajib.modelAset}
            />
          </>
        }
        className="mb-6"
      />

      {modelAset.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          judul="Belum ada model aset."
          deskripsi="Tambahkan model pertama untuk dipakai pendataan aset."
        />
      ) : (
        <DataTable
          columns={columns}
          data={modelAset.data}
          server={{ meta: modelAset.meta, filter }}
          ekspor="/aset-master/model/ekspor"
          facetedFilters={[
            {
              columnId: 'KategoriAsetId',
              title: 'Kategori',
              options: kategoriAset.map((k) => ({ label: k.Nama, value: k.Id })),
            },
            {
              columnId: 'MerekId',
              title: 'Merek',
              options: merek.map((m) => ({ label: m.Nama, value: m.Id })),
            },
          ]}
          pencarianPlaceholder="Cari nama atau kode model..."
          pesanKosong="Tidak ada model aset yang cocok."
        />
      )}
    </KerangkaAplikasi>
  );
}
