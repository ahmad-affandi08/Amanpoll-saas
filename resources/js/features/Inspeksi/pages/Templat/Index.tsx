import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Plus, Search } from 'lucide-react';
import type { TemplatInspeksi } from '@/features/PreventifInspeksi/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

interface Props {
  templat: TemplatInspeksi[];
  kategoriAset: { Id: string; Nama: string }[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function InspeksiTemplatIndex({ templat, kategoriAset, templatDaftarPeriksa, wajib }: Props) {
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
    form.post(ruteInspeksi.templat, {
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
    <KerangkaAplikasi>
      <Head title="Templat Inspeksi Aset" />

      <div className="space-y-6">
        <KepalaHalaman
          judul="Templat Inspeksi Aset"
          deskripsi="Konfigurasi siklus inspeksi rutin dan lembar periksa per kategori aset."
          aksi={
            <>
              <TombolEkspor url="/preventif-inspeksi/templat-inspeksi/ekspor" />
              <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
                <DialogTrigger asChild>
                  <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                    <Plus className="h-4 w-4" />
                    Buat Templat Inspeksi
                  </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-md">
                  <AturanWajibProvider aturan={wajib.templat}>
                    <form onSubmit={onSubmit}>
                      <DialogHeader>
                        <DialogTitle>Buat Templat Inspeksi</DialogTitle>
                      </DialogHeader>

                      <div className="grid gap-4 py-4">
                        <BidangKode
                          nilai={form.data.Kode}
                          onUbah={(nilai) => form.setData('Kode', nilai)}
                          galat={form.errors.Kode}
                          label="Kode Templat"
                          contoh="Misal: INSP-HVAC-BULANAN"
                        />

                        <div className="space-y-1.5">
                          <Label nama="Nama" htmlFor="Nama">
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
                          <Label nama="KategoriAsetId" htmlFor="KategoriAsetId">
                            Kategori Aset Terkait
                          </Label>
                          <Combobox
                            nilai={form.data.KategoriAsetId || '__none__'}
                            onPilih={(val) => form.setData('KategoriAsetId', val === '__none__' ? '' : val)}
                            opsi={[
                              { nilai: '__none__', label: '-- Semua Kategori --' },
                              ...opsiDari(kategoriAset, (k) => k.Nama),
                            ]}
                            placeholder="Pilih Kategori..."
                            className="cursor-pointer"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <Label nama="TemplatDaftarPeriksaId" htmlFor="TemplatDaftarPeriksaId">
                            Hubungkan Checklist Lapangan
                          </Label>
                          <Combobox
                            nilai={form.data.TemplatDaftarPeriksaId || '__none__'}
                            onPilih={(val) =>
                              form.setData('TemplatDaftarPeriksaId', val === '__none__' ? '' : val)
                            }
                            opsi={[
                              { nilai: '__none__', label: '-- Tanpa Lembar Checklist --' },
                              ...opsiDari(templatDaftarPeriksa, (t) => `${t.Kode} - ${t.Nama}`),
                            ]}
                            placeholder="Pilih Checklist..."
                            className="cursor-pointer"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <Label nama="IntervalHari" htmlFor="IntervalHari">
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
                  </AturanWajibProvider>
                </DialogContent>
              </Dialog>
            </>
          }
        />

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
          <KeadaanKosong
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
    </KerangkaAplikasi>
  );
}
