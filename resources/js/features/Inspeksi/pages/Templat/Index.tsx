import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
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
import { EmptyState } from '@/components/shared/EmptyState';
import { ClipboardCheck, Plus, Search, Calendar, FolderTree } from 'lucide-react';
import type { TemplatInspeksi } from '@/features/PreventifInspeksi/types';

interface Props {
  templat: TemplatInspeksi[];
  kategoriAset: { Id: string; Nama: string }[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
}

export default function IndexTemplatInspeksi({ templat, kategoriAset, templatDaftarPeriksa }: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');

  const form = useForm({
    Kode: '',
    Nama: '',
    KategoriAsetId: '',
    TemplatDaftarPeriksaId: '',
    IntervalHari: 30,
    Aktif: true,
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/preventif-inspeksi/templat-inspeksi', {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  const daftarTersaring = templat.filter(
    (t) =>
      t.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
      t.Kode.toLowerCase().includes(pencarian.toLowerCase()),
  );

  return (
    <AppLayout>
      <Head title="Templat Inspeksi Aset" />

      <div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-permukaan-900">Templat Inspeksi Aset</h1>
            <p className="text-sm text-permukaan-500">
              Konfigurasi siklus inspeksi rutin dan lembar periksa per kategori aset.
            </p>
          </div>

          <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
            <DialogTrigger asChild>
              <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                <Plus className="h-4 w-4" />
                Buat Templat Inspeksi
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <form onSubmit={onSubmit}>
                <DialogHeader>
                  <DialogTitle>Buat Templat Inspeksi</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="Kode">
                      Kode Templat <span className="text-rose-500">*</span>
                    </Label>
                    <Input
                      id="Kode"
                      placeholder="Misal: INSP-HVAC-BULANAN"
                      value={form.data.Kode}
                      onChange={(e) => form.setData('Kode', e.target.value)}
                      required
                    />
                    {form.errors.Kode && <p className="text-xs text-rose-500">{form.errors.Kode}</p>}
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="Nama">
                      Nama Templat <span className="text-rose-500">*</span>
                    </Label>
                    <Input
                      id="Nama"
                      placeholder="Misal: Inspeksi Visual & Kelistrikan HVAC"
                      value={form.data.Nama}
                      onChange={(e) => form.setData('Nama', e.target.value)}
                      required
                    />
                    {form.errors.Nama && <p className="text-xs text-rose-500">{form.errors.Nama}</p>}
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="KategoriAsetId">Kategori Aset Terkait</Label>
                    <Select
                      value={form.data.KategoriAsetId || '__none__'}
                      onValueChange={(val) => form.setData('KategoriAsetId', val === '__none__' ? '' : val)}
                    >
                      <SelectTrigger id="KategoriAsetId" className="cursor-pointer">
                        <SelectValue placeholder="Pilih Kategori..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="__none__">-- Semua Kategori --</SelectItem>
                        {kategoriAset.map((k) => (
                          <SelectItem key={k.Id} value={k.Id}>
                            {k.Nama}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="TemplatDaftarPeriksaId">Hubungkan Checklist Lapangan</Label>
                    <Select
                      value={form.data.TemplatDaftarPeriksaId || '__none__'}
                      onValueChange={(val) =>
                        form.setData('TemplatDaftarPeriksaId', val === '__none__' ? '' : val)
                      }
                    >
                      <SelectTrigger id="TemplatDaftarPeriksaId" className="cursor-pointer">
                        <SelectValue placeholder="Pilih Checklist..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="__none__">-- Tanpa Lembar Checklist --</SelectItem>
                        {templatDaftarPeriksa.map((t) => (
                          <SelectItem key={t.Id} value={t.Id}>
                            {t.Kode} - {t.Nama}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="IntervalHari">
                      Interval Siklus (Hari) <span className="text-rose-500">*</span>
                    </Label>
                    <Input
                      id="IntervalHari"
                      type="number"
                      min={1}
                      value={form.data.IntervalHari}
                      onChange={(e) => form.setData('IntervalHari', Number(e.target.value))}
                      required
                    />
                  </div>
                </div>

                <DialogFooter>
                  <Button
                    type="button"
                    variant="outline"
                    className="cursor-pointer"
                    onClick={() => setBukaDialog(false)}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                    disabled={form.processing}
                  >
                    {form.processing ? 'Menyimpan...' : 'Simpan Templat'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>

        <div className="flex items-center gap-2 max-w-sm">
          <div className="relative w-full">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-permukaan-400" />
            <Input
              type="text"
              placeholder="Cari templat inspeksi..."
              className="pl-9"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
            />
          </div>
        </div>

        {daftarTersaring.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/berkas-dokumen.webp"
            judul="Belum Ada Templat Inspeksi"
            deskripsi="Templat inspeksi berkala yang dibuat akan muncul di sini untuk menentukan standar pemeriksaan aset."
          />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {daftarTersaring.map((t) => (
              <div
                key={t.Id}
                className="bg-card border border-permukaan-200 rounded-xl p-5 hover:border-teknisi-300 hover:shadow-sm transition-all flex flex-col justify-between"
              >
                <div className="space-y-3">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-permukaan-700">
                      {t.Kode}
                    </span>
                    <Badge
                      variant={t.Aktif ? 'default' : 'secondary'}
                      className={t.Aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ''}
                    >
                      {t.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </div>

                  <div>
                    <h3 className="font-semibold text-permukaan-900 text-base">{t.Nama}</h3>
                    <p className="text-xs text-permukaan-500 mt-0.5">
                      Kategori: {t.kategoriAset?.Nama ?? 'Semua Kategori'}
                    </p>
                  </div>

                  <div className="space-y-1 pt-2 border-t border-permukaan-100 text-xs text-permukaan-600">
                    <div className="flex items-center justify-between">
                      <span className="text-permukaan-500">Interval Rutin:</span>
                      <span className="font-semibold text-permukaan-800">Setiap {t.IntervalHari} Hari</span>
                    </div>
                    {t.templatDaftarPeriksa && (
                      <div className="flex items-center justify-between">
                        <span className="text-permukaan-500">Checklist:</span>
                        <span className="text-permukaan-700 truncate max-w-[160px] font-medium">
                          {t.templatDaftarPeriksa.Nama}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
