import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
import { DatePicker } from '@/components/ui/date-picker';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Calendar,
  Building,
  Wrench,
  Clock,
  CheckCircle2,
} from 'lucide-react';
import type { RencanaPemeliharaan } from '@/features/PreventifInspeksi/types';

interface Props {
  rencana: RencanaPemeliharaan;
  asetTersedia: { Id: string; KodeAset: string; Nama: string; LokasiId?: string | null }[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
}

export default function ShowRencana({ rencana, asetTersedia }: Props) {
  const [bukaDialogAset, setBukaDialogAset] = useState(false);

  const formAset = useForm({
    AsetId: '',
    TanggalMulai: new Date().toISOString().split('T')[0],
    TanggalBerikutnya: '',
  });

  const daftarkanAset = (e: FormEvent) => {
    e.preventDefault();
    formAset.post(`/preventif-inspeksi/rencana-pemeliharaan/${rencana.Id}/aset`, {
      onSuccess: () => {
        setBukaDialogAset(false);
        formAset.reset();
      },
    });
  };

  const lepasAset = (asetId: string, namaAset: string) => {
    if (confirm(`Apakah Anda yakin ingin melepas aset "${namaAset}" dari rencana pemeliharaan ini?`)) {
      router.delete(`/preventif-inspeksi/rencana-pemeliharaan/${rencana.Id}/aset/${asetId}`);
    }
  };

  // Filter aset yang belum didaftarkan di rencana ini
  const asetTerdaftarIds = new Set(rencana.aset?.map((a) => a.AsetId) ?? []);
  const asetBelumTerdaftar = asetTersedia.filter((a) => !asetTerdaftarIds.has(a.Id));

  return (
    <AppLayout>
      <Head title={`Rencana: ${rencana.Nama}`} />

      <div className="space-y-6">
        {/* Breadcrumb */}
        <div className="flex items-center gap-2 text-sm text-permukaan-500">
          <Link
            href="/preventif-inspeksi/rencana-pemeliharaan"
            className="hover:text-permukaan-700 flex items-center gap-1 cursor-pointer"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Rencana</span>
          </Link>
        </div>

        {/* Info Rencana */}
        <div className="bg-card border border-permukaan-200 rounded-xl p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2.5">
                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-permukaan-700 border border-permukaan-300">
                  {rencana.Kode}
                </span>
                <Badge variant={rencana.Aktif ? 'default' : 'secondary'} className={rencana.Aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ''}>
                  {rencana.Aktif ? 'Aktif' : 'Nonaktif'}
                </Badge>
                <Badge variant="outline">
                  Prioritas: {rencana.Prioritas}
                </Badge>
              </div>

              <h1 className="text-xl font-bold text-permukaan-900">{rencana.Nama}</h1>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-xs text-permukaan-600">
                <div className="flex items-center gap-1.5">
                  <Clock className="h-4 w-4 text-permukaan-400" />
                  <span>Interval: <strong>Setiap {rencana.IntervalNilai} {rencana.IntervalSatuan}</strong></span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Calendar className="h-4 w-4 text-permukaan-400" />
                  <span>Horizon WO: <strong>{rencana.BuatPerintahKerjaHariSebelum} Hari Sebelum</strong></span>
                </div>
                {rencana.templatDaftarPeriksa && (
                  <div className="flex items-center gap-1.5">
                    <Wrench className="h-4 w-4 text-permukaan-400" />
                    <span>Checklist: <strong>{rencana.templatDaftarPeriksa.Kode}</strong></span>
                  </div>
                )}
              </div>
            </div>

            <Dialog open={bukaDialogAset} onOpenChange={setBukaDialogAset}>
              <DialogTrigger asChild>
                <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                  <Plus className="h-4 w-4" />
                  Daftarkan Aset ke Rencana
                </Button>
              </DialogTrigger>
              <DialogContent className="sm:max-w-md">
                <form onSubmit={daftarkanAset}>
                  <DialogHeader>
                    <DialogTitle>Daftarkan Aset</DialogTitle>
                  </DialogHeader>

                  <div className="grid gap-4 py-4">
                    <div className="space-y-1.5">
                      <Label htmlFor="AsetId">Pilih Aset <span className="text-rose-500">*</span></Label>
                      <Select
                        value={formAset.data.AsetId}
                        onValueChange={(val) => formAset.setData('AsetId', val)}
                        required
                      >
                        <SelectTrigger id="AsetId" className="cursor-pointer">
                          <SelectValue placeholder="Pilih unit aset..." />
                        </SelectTrigger>
                        <SelectContent className="max-h-60">
                          {asetBelumTerdaftar.map((a) => (
                            <SelectItem key={a.Id} value={a.Id}>
                              {a.KodeAset} - {a.Nama}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {formAset.errors.AsetId && <p className="text-xs text-rose-500">{formAset.errors.AsetId}</p>}
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="TanggalMulai">Tanggal Mulai Berlaku <span className="text-rose-500">*</span></Label>
                      <DatePicker
                        value={formAset.data.TanggalMulai}
                        onChange={(val) => formAset.setData('TanggalMulai', val)}
                        placeholder="Pilih tanggal mulai..."
                      />
                    </div>

                    <div className="space-y-1.5">
                      <Label htmlFor="TanggalBerikutnya">Tanggal Jatuh Tempo Pertama (Opsional)</Label>
                      <DatePicker
                        value={formAset.data.TanggalBerikutnya}
                        onChange={(val) => formAset.setData('TanggalBerikutnya', val)}
                        placeholder="Otomatis dihitung jika kosong"
                      />
                      <p className="text-[11px] text-permukaan-500">
                        Kosongkan agar otomatis dihitung: Tanggal Mulai + {rencana.IntervalNilai} {rencana.IntervalSatuan}.
                      </p>
                    </div>
                  </div>

                  <DialogFooter>
                    <Button
                      type="button"
                      variant="outline"
                      className="cursor-pointer"
                      onClick={() => setBukaDialogAset(false)}
                    >
                      Batal
                    </Button>
                    <Button
                      type="submit"
                      className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                      disabled={formAset.processing || !formAset.data.AsetId}
                    >
                      {formAset.processing ? 'Mendaftarkan...' : 'Daftarkan Aset'}
                    </Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          </div>
        </div>

        {/* Tabel Aset Terdaftar */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-permukaan-900">
              Aset Terdaftar ({rencana.aset?.length ?? 0})
            </h2>
            <span className="text-xs text-permukaan-500">
              Setiap aset memiliki tanggal jatuh tempo pemeliharaan preventif tersendiri.
            </span>
          </div>

          {(!rencana.aset || rencana.aset.length === 0) ? (
            <div className="bg-card border border-dashed border-permukaan-300 rounded-xl p-8 text-center">
              <p className="text-permukaan-500 text-sm">Belum ada aset yang didaftarkan pada rencana ini.</p>
            </div>
          ) : (
            <div className="bg-card border border-permukaan-200 rounded-xl overflow-hidden shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="bg-permukaan-50 border-b border-permukaan-200 text-xs font-semibold text-permukaan-600 uppercase">
                    <tr>
                      <th className="px-5 py-3">Aset</th>
                      <th className="px-5 py-3">Lokasi</th>
                      <th className="px-5 py-3">Tanggal Mulai</th>
                      <th className="px-5 py-3">Jatuh Tempo Berikutnya</th>
                      <th className="px-5 py-3">Riwayat Jadwal</th>
                      <th className="px-5 py-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-permukaan-100">
                    {rencana.aset.map((item) => (
                      <tr key={item.Id} className="hover:bg-permukaan-50/50 transition-colors">
                        <td className="px-5 py-4 font-medium text-permukaan-900">
                          <div>
                            <span className="font-mono text-xs font-semibold text-permukaan-500">
                              {item.aset?.KodeAset}
                            </span>
                            <div className="font-semibold text-permukaan-800">
                              {item.aset?.Nama}
                            </div>
                          </div>
                        </td>
                        <td className="px-5 py-4 text-permukaan-600 text-xs">
                          {item.aset?.lokasi?.Nama ?? '-'}
                        </td>
                        <td className="px-5 py-4 text-permukaan-600 text-xs">
                          {item.TanggalMulai}
                        </td>
                        <td className="px-5 py-4">
                          <span className="inline-flex items-center gap-1.5 font-semibold text-xs px-2.5 py-1 rounded-full bg-teknisi-50 text-teknisi-700 border border-teknisi-200">
                            <Calendar className="h-3.5 w-3.5" />
                            {item.TanggalBerikutnya ?? 'Belum dijadwalkan'}
                          </span>
                        </td>
                        <td className="px-5 py-4 text-xs text-permukaan-500">
                          {item.jadwal && item.jadwal.length > 0 ? (
                            <span>{item.jadwal.length} Jadwal tercatat</span>
                          ) : (
                            <span className="text-permukaan-400">Menunggu siklus</span>
                          )}
                        </td>
                        <td className="px-5 py-4 text-right">
                          <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer h-8 px-2.5 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200"
                            onClick={() => lepasAset(item.AsetId, item.aset?.Nama ?? 'Aset')}
                          >
                            <Trash2 className="h-3.5 w-3.5 mr-1" />
                            Lepas
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
