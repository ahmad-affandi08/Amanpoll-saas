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
import { ClipboardCheck, Plus, Search, Layers, ArrowRight } from 'lucide-react';
import type { TemplatDaftarPeriksa } from '@/features/PreventifInspeksi/types';

interface Props {
  templat: TemplatDaftarPeriksa[];
  kategoriAset: { Id: string; Nama: string }[];
  modelAset: { Id: string; Nama: string; KategoriAsetId?: string }[];
}

export default function IndexTemplat({ templat, kategoriAset, modelAset }: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');

  const form = useForm({
    Kode: '',
    Nama: '',
    Jenis: 'Pemeliharaan',
    KategoriAsetId: '',
    ModelAsetId: '',
    Aktif: true,
  });

  const daftarTersaring = templat.filter((t) =>
    t.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
    t.Kode.toLowerCase().includes(pencarian.toLowerCase())
  );

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/preventif-inspeksi/templat-daftar-periksa', {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  return (
    <AppLayout>
      <Head title="Templat Daftar Periksa (Checklist)" />

      <div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-permukaan-900">Templat Daftar Periksa</h1>
            <p className="text-sm text-permukaan-500">
              Kelola lembar periksa terstandarisasi untuk inspeksi dan pemeliharaan preventif.
            </p>
          </div>

          <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
            <DialogTrigger asChild>
              <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                <Plus className="h-4 w-4" />
                Buat Templat Baru
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <form onSubmit={onSubmit}>
                <DialogHeader>
                  <DialogTitle>Buat Templat Daftar Periksa</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                  <div className="space-y-1.5">
                    <Label htmlFor="Kode">Kode Templat <span className="text-rose-500">*</span></Label>
                    <Input
                      id="Kode"
                      placeholder="Misal: CK-POMPA-01"
                      value={form.data.Kode}
                      onChange={(e) => form.setData('Kode', e.target.value)}
                      required
                    />
                    {form.errors.Kode && <p className="text-xs text-rose-500">{form.errors.Kode}</p>}
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="Nama">Nama Templat <span className="text-rose-500">*</span></Label>
                    <Input
                      id="Nama"
                      placeholder="Misal: Checklist Servis Rutin Pompa Sentrifugal"
                      value={form.data.Nama}
                      onChange={(e) => form.setData('Nama', e.target.value)}
                      required
                    />
                    {form.errors.Nama && <p className="text-xs text-rose-500">{form.errors.Nama}</p>}
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="Jenis">Jenis Operasi</Label>
                    <Select
                      value={form.data.Jenis}
                      onValueChange={(val) => form.setData('Jenis', val)}
                    >
                      <SelectTrigger id="Jenis" className="cursor-pointer">
                        <SelectValue placeholder="Pilih Jenis" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Pemeliharaan">Pemeliharaan</SelectItem>
                        <SelectItem value="Inspeksi">Inspeksi</SelectItem>
                        <SelectItem value="Kalibrasi">Kalibrasi</SelectItem>
                        <SelectItem value="Umum">Umum</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-1.5">
                    <Label htmlFor="KategoriAsetId">Kategori Aset Terkait (Opsional)</Label>
                    <Select
                      value={form.data.KategoriAsetId || '__none__'}
                      onValueChange={(val) => form.setData('KategoriAsetId', val === '__none__' ? '' : val)}
                    >
                      <SelectTrigger id="KategoriAsetId" className="cursor-pointer">
                        <SelectValue placeholder="Semua Kategori" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="__none__">-- Umum (Semua Kategori) --</SelectItem>
                        {kategoriAset.map((k) => (
                          <SelectItem key={k.Id} value={k.Id}>
                            {k.Nama}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
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
                    {form.processing ? 'Menyimpan...' : 'Simpan & Lanjutkan'}
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
              placeholder="Cari templat daftar periksa..."
              className="pl-9"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
            />
          </div>
        </div>

        {daftarTersaring.length === 0 ? (
          <EmptyState
            judul="Belum Ada Templat Daftar Periksa"
            deskripsi="Buat templat checklist pertama Anda untuk memudahkan proses inspeksi dan pekerjaan teknisi."
            aksi={
              <Button
                className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2"
                onClick={() => setBukaDialog(true)}
              >
                <Plus className="h-4 w-4" />
                Buat Templat Baru
              </Button>
            }
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
                    <Badge variant={t.Aktif ? 'default' : 'secondary'} className={t.Aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ''}>
                      {t.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </div>

                  <div>
                    <h3 className="font-semibold text-permukaan-900 text-base">{t.Nama}</h3>
                    <p className="text-xs text-permukaan-500 mt-0.5">
                      Jenis: {t.Jenis} {t.kategoriAset ? `• ${t.kategoriAset.Nama}` : ''}
                    </p>
                  </div>

                  <div className="flex items-center gap-3 pt-2 text-xs text-permukaan-600 border-t border-permukaan-100">
                    <span className="flex items-center gap-1 font-medium">
                      <Layers className="h-3.5 w-3.5 text-permukaan-400" />
                      {t.butir_count ?? t.butir?.length ?? 0} Pertanyaan
                    </span>
                    <span>•</span>
                    <span>Versi {t.VersiTemplat}</span>
                  </div>
                </div>

                <div className="pt-4 mt-4 border-t border-permukaan-100">
                  <Link
                    href={`/preventif-inspeksi/templat-daftar-periksa/${t.Id}`}
                    className="inline-flex items-center justify-between w-full text-xs font-medium text-teknisi-600 hover:text-teknisi-700 cursor-pointer"
                  >
                    <span>Buka Builder Pertanyaan</span>
                    <ArrowRight className="h-3.5 w-3.5" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
