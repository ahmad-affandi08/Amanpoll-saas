import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/shared/EmptyState';
import { ruteKodeKegagalan } from '@/features/KodeKegagalan/api';

interface KategoriAsetRingkas {
  Id: string;
  Nama: string;
}

interface ItemKodeKegagalan {
  Id: string;
  Kode: string;
  Nama: string;
  Jenis: 'Masalah' | 'Penyebab' | 'Tindakan';
  KategoriAsetId: string | null;
  kategori_aset?: KategoriAsetRingkas | null;
  Keterangan: string | null;
  Aktif: boolean;
}

interface Props {
  kodeKegagalan: ItemKodeKegagalan[];
  kategoriAset: KategoriAsetRingkas[];
}

const TANPA = '__tanpa__';

function DialogFormKodeKegagalan({
  kategoriAset,
  itemEdit,
  pemicu,
}: {
  kategoriAset: KategoriAsetRingkas[];
  itemEdit?: ItemKodeKegagalan;
  pemicu?: React.ReactNode;
}) {
  const [buka, setBuka] = useState(false);
  const sedangEdit = Boolean(itemEdit);

  const form = useForm({
    Jenis: itemEdit?.Jenis ?? ('Masalah' as 'Masalah' | 'Penyebab' | 'Tindakan'),
    Kode: itemEdit?.Kode ?? '',
    Nama: itemEdit?.Nama ?? '',
    KategoriAsetId: itemEdit?.KategoriAsetId ?? TANPA,
    Keterangan: itemEdit?.Keterangan ?? '',
    Aktif: itemEdit?.Aktif ?? true,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KategoriAsetId: data.KategoriAsetId === TANPA ? null : data.KategoriAsetId,
    }));

    if (sedangEdit && itemEdit) {
      form.put(ruteKodeKegagalan.detail(itemEdit.Id), {
        preserveScroll: true,
        onSuccess: () => setBuka(false),
      });
    } else {
      form.post(ruteKodeKegagalan.index, {
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
      });
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        {pemicu ? pemicu : <Button className="cursor-pointer">Tambah Kode Kegagalan</Button>}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{sedangEdit ? 'Edit Kode Kegagalan' : 'Tambah Kode Kegagalan'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Jenis Taksonomi</Label>
            <Select
              value={form.data.Jenis}
              onValueChange={(val) => form.setData('Jenis', val as 'Masalah' | 'Penyebab' | 'Tindakan')}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Masalah" className="cursor-pointer">
                  Masalah (Problem / Symptom)
                </SelectItem>
                <SelectItem value="Penyebab" className="cursor-pointer">
                  Penyebab (Cause / Mechanism)
                </SelectItem>
                <SelectItem value="Tindakan" className="cursor-pointer">
                  Tindakan (Remedy / Action)
                </SelectItem>
              </SelectContent>
            </Select>
            {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Kode</Label>
              <Input
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value.toUpperCase())}
                placeholder="Misal: MSL-001"
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>

            <div className="space-y-1.5">
              <Label>Kategori Aset (Opsional)</Label>
              <Select
                value={form.data.KategoriAsetId}
                onValueChange={(val) => form.setData('KategoriAsetId', val)}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue placeholder="Semua kategori" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA} className="cursor-pointer">
                    Semua Kategori Aset
                  </SelectItem>
                  {kategoriAset.map((k) => (
                    <SelectItem key={k.Id} value={k.Id} className="cursor-pointer">
                      {k.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Nama / Deskripsi Ringkas</Label>
            <Input
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              placeholder="Contoh: Kebocoran Oli Seal, Overheat, Kalibrasi Sensor..."
            />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Keterangan Tambahan</Label>
            <Textarea
              rows={3}
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
              placeholder="Penjelasan konteks atau panduan diagnosa..."
            />
            {form.errors.Keterangan && <p className="text-sm text-destructive">{form.errors.Keterangan}</p>}
          </div>

          <div className="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="kode-aktif"
              checked={form.data.Aktif}
              onChange={(e) => form.setData('Aktif', e.target.checked)}
              className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
            />
            <label htmlFor="kode-aktif" className="text-sm font-medium cursor-pointer">
              Aktif dan dapat dipilih pada perintah kerja
            </label>
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing} className="cursor-pointer">
              {sedangEdit ? 'Perbarui Kode' : 'Simpan Kode'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KodeKegagalanIndex({ kodeKegagalan, kategoriAset }: Props) {
  const [tabJenis, setTabJenis] = useState<'Semua' | 'Masalah' | 'Penyebab' | 'Tindakan'>('Semua');

  const daftarTersaring =
    tabJenis === 'Semua' ? kodeKegagalan : kodeKegagalan.filter((k) => k.Jenis === tabJenis);

  const varianJenis: Record<'Masalah' | 'Penyebab' | 'Tindakan', 'perhatian' | 'destructive' | 'sukses'> = {
    Masalah: 'perhatian',
    Penyebab: 'destructive',
    Tindakan: 'sukses',
  };

  return (
    <AppLayout>
      <Head title="Kode Kegagalan" />

      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Kode Kegagalan</h1>
          <p className="text-sm text-muted-foreground">
            Katalog taksonomi Problem-Cause-Remedy untuk standarisasi analisis kegagalan aset.
          </p>
        </div>
        <DialogFormKodeKegagalan kategoriAset={kategoriAset} />
      </div>

      {/* FILTER TAB JENIS */}
      <div className="mb-4 flex flex-wrap gap-2">
        {(['Semua', 'Masalah', 'Penyebab', 'Tindakan'] as const).map((jenis) => {
          const aktif = tabJenis === jenis;
          const hitung =
            jenis === 'Semua' ? kodeKegagalan.length : kodeKegagalan.filter((k) => k.Jenis === jenis).length;
          return (
            <button
              key={jenis}
              onClick={() => setTabJenis(jenis)}
              className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors ${
                aktif ? 'bg-teknisi-700 text-white' : 'bg-muted text-muted-foreground hover:bg-muted/80'
              }`}
            >
              <span>{jenis}</span>
              <span
                className={`rounded-full px-1.5 py-0.2 text-[10px] ${
                  aktif ? 'bg-white/20 text-white' : 'bg-background text-foreground'
                }`}
              >
                {hitung}
              </span>
            </button>
          );
        })}
      </div>

      {daftarTersaring.length === 0 ? (
        <EmptyState
          judul="Belum ada kode kegagalan"
          deskripsi="Tambahkan master data kode masalah, penyebab, atau tindakan untuk memudahkan teknisi."
        />
      ) : (
        <div className="overflow-x-auto rounded-lg border border-border bg-card">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-border bg-muted/40 text-xs font-medium text-muted-foreground">
              <tr>
                <th className="px-4 py-3">Kode</th>
                <th className="px-4 py-3">Nama</th>
                <th className="px-4 py-3">Jenis</th>
                <th className="px-4 py-3">Kategori Aset</th>
                <th className="px-4 py-3">Keterangan</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {daftarTersaring.map((item) => (
                <tr key={item.Id} className="hover:bg-muted/30 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs font-semibold text-foreground">{item.Kode}</td>
                  <td className="px-4 py-3 font-medium text-foreground">{item.Nama}</td>
                  <td className="px-4 py-3">
                    <Badge variant={varianJenis[item.Jenis]}>{item.Jenis}</Badge>
                  </td>
                  <td className="px-4 py-3 text-xs text-muted-foreground">
                    {item.kategori_aset?.Nama ?? 'Semua Kategori'}
                  </td>
                  <td className="px-4 py-3 text-xs text-muted-foreground max-w-xs truncate">
                    {item.Keterangan ?? '—'}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={item.Aktif ? 'sukses' : 'netral'}>
                      {item.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <DialogFormKodeKegagalan
                      kategoriAset={kategoriAset}
                      itemEdit={item}
                      pemicu={
                        <Button size="sm" variant="outline" className="cursor-pointer h-7 text-xs">
                          Edit
                        </Button>
                      }
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </AppLayout>
  );
}
