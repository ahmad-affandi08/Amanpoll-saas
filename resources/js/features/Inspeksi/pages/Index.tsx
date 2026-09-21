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
import { EmptyState } from '@/components/shared/EmptyState';
import {
  ClipboardCheck,
  Plus,
  Search,
  Filter,
  ArrowRight,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Calendar,
} from 'lucide-react';
import type { Inspeksi } from '@/features/PreventifInspeksi/types';
import { statusInspeksiBadge, hasilInspeksiBadge } from '@/features/PreventifInspeksi/status';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  inspeksi: Inspeksi[];
  templatInspeksi: { Id: string; Nama: string; Kode: string }[];
  aset: { Id: string; KodeAset: string; Nama: string; LokasiId?: string | null }[];
  inspektor: { Id: string; Nama: string }[];
  filter: { status?: string; hasil?: string };
}

export default function IndexInspeksi({ inspeksi, templatInspeksi, aset, inspektor, filter }: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');

  const form = useForm({
    TemplatInspeksiId: '',
    AsetId: '',
    DijadwalkanPada: new Date().toISOString().split('T')[0],
    DilaksanakanOleh: '',
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteInspeksi.index, {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  const terapkanFilter = (field: string, value: string) => {
    router.get(
      ruteInspeksi.index,
      {
        ...filter,
        [field]: value === '__all__' ? undefined : value,
      },
      { preserveState: true },
    );
  };

  const daftarTersaring = inspeksi.filter(
    (i) =>
      i.Nomor.toLowerCase().includes(pencarian.toLowerCase()) ||
      (i.aset?.Nama || '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (i.templatInspeksi?.Nama || '').toLowerCase().includes(pencarian.toLowerCase()),
  );

  const lolosCount = inspeksi.filter((i) => i.Hasil === 'Lolos').length;
  const perhatianCount = inspeksi.filter((i) => i.Hasil === 'PerluPerhatian').length;
  const gagalCount = inspeksi.filter((i) => i.Hasil === 'Gagal').length;

  return (
    <AppLayout>
      <Head title="Inspeksi Aset Berkala" />

      <div className="space-y-6">
        {/* Header */}
        <PageHeader
          judul="Inspeksi Aset Berkala"
          deskripsi="Pemeriksaan fisik, pemantauan kondisi aset, dan pencatatan temuan operasional."
          aksi={
            <>
              <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
                <DialogTrigger asChild>
                  <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                    <Plus className="h-4 w-4" />
                    Jadwalkan Inspeksi
                  </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-md">
                  <form onSubmit={onSubmit}>
                    <DialogHeader>
                      <DialogTitle>Jadwalkan Inspeksi Aset</DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                      <div className="space-y-1.5">
                        <Label htmlFor="TemplatInspeksiId">
                          Templat Inspeksi <span className="text-rose-500">*</span>
                        </Label>
                        <Select
                          value={form.data.TemplatInspeksiId}
                          onValueChange={(val) => form.setData('TemplatInspeksiId', val)}
                          required
                        >
                          <SelectTrigger id="TemplatInspeksiId" className="cursor-pointer">
                            <SelectValue placeholder="Pilih Templat Inspeksi..." />
                          </SelectTrigger>
                          <SelectContent>
                            {templatInspeksi.map((t) => (
                              <SelectItem key={t.Id} value={t.Id}>
                                {t.Kode} - {t.Nama}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>

                      <div className="space-y-1.5">
                        <Label htmlFor="AsetId">
                          Unit Aset yang Diinspeksi <span className="text-rose-500">*</span>
                        </Label>
                        <Select
                          value={form.data.AsetId}
                          onValueChange={(val) => form.setData('AsetId', val)}
                          required
                        >
                          <SelectTrigger id="AsetId" className="cursor-pointer">
                            <SelectValue placeholder="Pilih Unit Aset..." />
                          </SelectTrigger>
                          <SelectContent className="max-h-60">
                            {aset.map((a) => (
                              <SelectItem key={a.Id} value={a.Id}>
                                {a.KodeAset} - {a.Nama}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>

                      <div className="space-y-1.5">
                        <Label htmlFor="DijadwalkanPada">
                          Tanggal Jadwal Inspeksi <span className="text-rose-500">*</span>
                        </Label>
                        <DatePicker
                          value={form.data.DijadwalkanPada}
                          onChange={(val) => form.setData('DijadwalkanPada', val)}
                          placeholder="Pilih tanggal jadwal..."
                        />
                        {form.errors.DijadwalkanPada && (
                          <p className="text-xs text-rose-500">{form.errors.DijadwalkanPada}</p>
                        )}
                      </div>

                      <div className="space-y-1.5">
                        <Label htmlFor="DilaksanakanOleh">Inspektor / Petugas (Opsional)</Label>
                        <Select
                          value={form.data.DilaksanakanOleh || '__none__'}
                          onValueChange={(val) =>
                            form.setData('DilaksanakanOleh', val === '__none__' ? '' : val)
                          }
                        >
                          <SelectTrigger id="DilaksanakanOleh" className="cursor-pointer">
                            <SelectValue placeholder="Pilih Petugas..." />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="__none__">-- Ditentukan Nanti --</SelectItem>
                            {inspektor.map((p) => (
                              <SelectItem key={p.Id} value={p.Id}>
                                {p.Nama}
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
                        disabled={form.processing || !form.data.TemplatInspeksiId || !form.data.AsetId}
                      >
                        {form.processing ? 'Menjadwalkan...' : 'Jadwalkan'}
                      </Button>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            </>
          }
        />

        {/* Ringkasan Hasil Inspeksi */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-permukaan-500 font-medium">Total Jadwal</span>
            <div className="text-2xl font-bold text-permukaan-900 mt-1">{inspeksi.length}</div>
          </div>
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-emerald-600 font-medium flex items-center gap-1">
              <CheckCircle2 className="h-3.5 w-3.5" /> Lolos Normal
            </span>
            <div className="text-2xl font-bold text-emerald-700 mt-1">{lolosCount}</div>
          </div>
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-amber-600 font-medium flex items-center gap-1">
              <AlertTriangle className="h-3.5 w-3.5" /> Perlu Perhatian
            </span>
            <div className="text-2xl font-bold text-amber-700 mt-1">{perhatianCount}</div>
          </div>
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-rose-600 font-medium flex items-center gap-1">
              <XCircle className="h-3.5 w-3.5" /> Gagal / Temuan
            </span>
            <div className="text-2xl font-bold text-rose-700 mt-1">{gagalCount}</div>
          </div>
        </div>

        {/* Filter & Search Bar */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="relative w-full sm:w-80">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-permukaan-400" />
            <Input
              type="text"
              placeholder="Cari nomor, aset, atau templat..."
              className="pl-9"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
            />
          </div>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Select value={filter.status || '__all__'} onValueChange={(val) => terapkanFilter('status', val)}>
              <SelectTrigger className="cursor-pointer w-36 text-xs h-9">
                <SelectValue placeholder="Semua Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">Semua Status</SelectItem>
                <SelectItem value="Terjadwal">Terjadwal</SelectItem>
                <SelectItem value="SedangDikerjakan">Sedang Berjalan</SelectItem>
                <SelectItem value="Selesai">Selesai</SelectItem>
              </SelectContent>
            </Select>

            <Select value={filter.hasil || '__all__'} onValueChange={(val) => terapkanFilter('hasil', val)}>
              <SelectTrigger className="cursor-pointer w-40 text-xs h-9">
                <SelectValue placeholder="Semua Hasil" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">Semua Hasil</SelectItem>
                <SelectItem value="Lolos">Lolos</SelectItem>
                <SelectItem value="PerluPerhatian">Perlu Perhatian</SelectItem>
                <SelectItem value="Gagal">Gagal / Rusak</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Tabel Inspeksi */}
        {daftarTersaring.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/persetujuan-kepatuhan.webp"
            judul="Belum Ada Catatan Inspeksi"
            deskripsi="Riwayat dan jadwal inspeksi kondisi aset operasional akan dicatat di sini."
          />
        ) : (
          <div className="bg-card border border-permukaan-200 rounded-xl overflow-hidden shadow-sm">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-permukaan-50 border-b border-permukaan-200 text-xs font-semibold text-permukaan-600 uppercase">
                  <tr>
                    <th className="px-5 py-3">Nomor</th>
                    <th className="px-5 py-3">Aset</th>
                    <th className="px-5 py-3">Templat Inspeksi</th>
                    <th className="px-5 py-3">Jadwal / Pelaksanaan</th>
                    <th className="px-5 py-3">Status</th>
                    <th className="px-5 py-3">Hasil Evaluasi</th>
                    <th className="px-5 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-permukaan-100">
                  {daftarTersaring.map((item) => {
                    const statusBadge = statusInspeksiBadge[item.Status] ?? { label: item.Status, kelas: '' };
                    const hasilBadge = item.Hasil ? hasilInspeksiBadge[item.Hasil] : null;

                    return (
                      <tr key={item.Id} className="hover:bg-permukaan-50/50 transition-colors">
                        <td className="px-5 py-4 font-mono font-bold text-xs text-permukaan-900">
                          {item.Nomor}
                        </td>
                        <td className="px-5 py-4 text-xs font-medium text-permukaan-900">
                          <div className="font-semibold text-permukaan-800">{item.aset?.Nama}</div>
                          <span className="text-permukaan-500 font-mono">
                            {item.aset?.KodeAset} • {item.aset?.lokasi?.Nama ?? '-'}
                          </span>
                        </td>
                        <td className="px-5 py-4 text-xs text-permukaan-700">
                          {item.templatInspeksi?.Nama ?? '-'}
                        </td>
                        <td className="px-5 py-4 text-xs text-permukaan-600">
                          <div className="flex items-center gap-1.5">
                            <Calendar className="h-3.5 w-3.5 text-permukaan-400" />
                            <span>{item.DijadwalkanPada ? item.DijadwalkanPada.substring(0, 10) : '-'}</span>
                          </div>
                        </td>
                        <td className="px-5 py-4">
                          <Badge variant="outline" className={`text-xs ${statusBadge.kelas}`}>
                            {statusBadge.label}
                          </Badge>
                        </td>
                        <td className="px-5 py-4">
                          {hasilBadge ? (
                            <Badge variant="outline" className={`text-xs ${hasilBadge.kelas}`}>
                              {hasilBadge.label}
                            </Badge>
                          ) : (
                            <span className="text-xs text-permukaan-400">Belum Dievaluasi</span>
                          )}
                        </td>
                        <td className="px-5 py-4 text-right">
                          <Link
                            href={ruteInspeksi.detail(item.Id)}
                            className="inline-flex items-center gap-1 text-xs font-medium text-teknisi-600 hover:text-teknisi-700 cursor-pointer"
                          >
                            <span>Detail</span>
                            <ArrowRight className="h-3.5 w-3.5" />
                          </Link>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
