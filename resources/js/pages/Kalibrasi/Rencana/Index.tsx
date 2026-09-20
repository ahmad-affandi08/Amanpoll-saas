import { FormEvent, useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { EmptyState } from '@/components/shared/EmptyState';
import {
  Calendar,
  Plus,
  Pencil,
  Trash2,
  ArrowRight,
  Filter,
  Search,
  CheckCircle2,
  AlertTriangle,
  Clock,
  FileCheck2,
} from 'lucide-react';
import type { RencanaKalibrasi } from '@/features/Kalibrasi/types';
import { statusKalibrasiBadge } from '@/features/Kalibrasi/status';

interface Props {
  rencanaKalibrasi: RencanaKalibrasi[];
  aset: { Id: string; KodeAset: string; Nama: string }[];
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
  filter: {
    asetId?: string;
    jenisKalibrasiId?: string;
    status?: string;
  };
}

export default function RencanaKalibrasiIndex({
  rencanaKalibrasi,
  aset,
  jenisKalibrasi,
  penyedia,
  filter,
}: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [rencanaDiedit, setRencanaDiedit] = useState<RencanaKalibrasi | null>(null);
  const [pencarian, setPencarian] = useState('');

  const form = useForm({
    AsetId: '',
    JenisKalibrasiId: '',
    PenyediaId: '',
    IntervalHari: 365,
    TanggalMulai: new Date().toISOString().split('T')[0],
    TanggalBerikutnya: '',
    PeringatanHariSebelum: 30,
    Aktif: true,
  });

  // Otomatis hitung TanggalBerikutnya saat TanggalMulai atau IntervalHari berubah
  const hitungTanggalBerikutnya = (tglMulai: string, interval: number) => {
    if (!tglMulai || isNaN(interval)) return '';
    const d = new Date(tglMulai);
    d.setDate(d.getDate() + Number(interval));
    return d.toISOString().split('T')[0];
  };

  const bukaModalTambah = () => {
    setRencanaDiedit(null);
    const today = new Date().toISOString().split('T')[0];
    const defaultNext = hitungTanggalBerikutnya(today, 365);

    form.reset();
    form.setData({
      AsetId: '',
      JenisKalibrasiId: '',
      PenyediaId: '',
      IntervalHari: 365,
      TanggalMulai: today,
      TanggalBerikutnya: defaultNext,
      PeringatanHariSebelum: 30,
      Aktif: true,
    });
    setBukaDialog(true);
  };

  const bukaModalEdit = (rk: RencanaKalibrasi) => {
    setRencanaDiedit(rk);
    form.setData({
      AsetId: rk.AsetId,
      JenisKalibrasiId: rk.JenisKalibrasiId ?? '',
      PenyediaId: rk.PenyediaId ?? '',
      IntervalHari: rk.IntervalHari,
      TanggalMulai: rk.TanggalMulai,
      TanggalBerikutnya: rk.TanggalBerikutnya,
      PeringatanHariSebelum: rk.PeringatanHariSebelum,
      Aktif: rk.Aktif,
    });
    setBukaDialog(true);
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (rencanaDiedit) {
      form.put(`/kalibrasi/rencana/${rencanaDiedit.Id}`, {
        onSuccess: () => {
          setBukaDialog(false);
          form.reset();
        },
      });
    } else {
      form.post('/kalibrasi/rencana', {
        onSuccess: () => {
          setBukaDialog(false);
          form.reset();
        },
      });
    }
  };

  const hapusRencana = (rk: RencanaKalibrasi) => {
    if (confirm(`Apakah Anda yakin ingin menghapus rencana kalibrasi untuk aset "${rk.aset?.Nama}"?`)) {
      form.delete(`/kalibrasi/rencana/${rk.Id}`);
    }
  };

  const terapkanFilter = (field: string, value: string) => {
    router.get(
      '/kalibrasi/rencana',
      {
        ...filter,
        [field]: value === '__all__' ? undefined : value,
      },
      { preserveState: true }
    );
  };

  const filteredList = rencanaKalibrasi.filter((rk) => {
    return (
      (rk.aset?.Nama ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (rk.aset?.KodeAset ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (rk.jenisKalibrasi?.Nama ?? '').toLowerCase().includes(pencarian.toLowerCase())
    );
  });

  return (
    <AppLayout>
      <Head title="Rencana Kalibrasi Berkala" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">
              Rencana Kalibrasi
            </h1>
            <p className="text-sm text-muted-foreground">
              Atur siklus interval, tanggal jatuh tempo, dan mitra kalibrasi untuk setiap instrumen operasional.
            </p>
          </div>
          <Button onClick={bukaModalTambah} size="sm">
            <Plus className="mr-1.5 size-4" />
            Buat Rencana Kalibrasi
          </Button>
        </div>

        {/* Filter Card */}
        <Card className="border-border">
          <CardHeader className="p-4 sm:p-5 border-b border-border">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div className="relative">
                <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                <Input
                  placeholder="Cari aset atau instrumen..."
                  value={pencarian}
                  onChange={(e) => setPencarian(e.target.value)}
                  className="pl-8 h-9 text-xs"
                />
              </div>

              <div>
                <Select
                  value={filter.status ?? '__all__'}
                  onValueChange={(val) => terapkanFilter('status', val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Status Kepatuhan" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__all__">Semua Status Kepatuhan</SelectItem>
                    <SelectItem value="Valid">Valid</SelectItem>
                    <SelectItem value="SegeraJatuhTempo">Segera Jatuh Tempo</SelectItem>
                    <SelectItem value="Terlambat">Terlambat</SelectItem>
                    <SelectItem value="TidakAktif">Tidak Aktif</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Select
                  value={filter.jenisKalibrasiId ?? '__all__'}
                  onValueChange={(val) => terapkanFilter('jenisKalibrasiId', val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Jenis Kalibrasi" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__all__">Semua Jenis Kalibrasi</SelectItem>
                    {jenisKalibrasi.map((jk) => (
                      <SelectItem key={jk.Id} value={jk.Id}>
                        {jk.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Select
                  value={filter.asetId ?? '__all__'}
                  onValueChange={(val) => terapkanFilter('asetId', val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Pilih Aset Spesifik" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__all__">Semua Aset</SelectItem>
                    {aset.map((a) => (
                      <SelectItem key={a.Id} value={a.Id}>
                        {a.KodeAset} - {a.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardHeader>

          <CardContent className="p-0">
            {filteredList.length === 0 ? (
              <div className="py-12">
                <EmptyState
                  judul="Belum ada rencana kalibrasi."
                  deskripsi="Belum ada rencana kalibrasi yang terdaftar atau cocok dengan kriteria filter."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-muted-foreground border-b border-border">
                    <tr>
                      <th className="px-4 py-3 font-medium">Aset / Instrumen</th>
                      <th className="px-4 py-3 font-medium">Jenis Kalibrasi</th>
                      <th className="px-3 py-3 font-medium">Penyedia / Lab</th>
                      <th className="px-3 py-3 font-medium">Interval</th>
                      <th className="px-3 py-3 font-medium">Jatuh Tempo</th>
                      <th className="px-3 py-3 font-medium text-center">Status</th>
                      <th className="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {filteredList.map((rk) => {
                      const badge = statusKalibrasiBadge(rk.StatusKalibrasi);
                      return (
                        <tr key={rk.Id} className="hover:bg-permukaan-50 transition-colors">
                          <td className="px-4 py-3 font-medium text-foreground">
                            <Link
                              href={`/kalibrasi/rencana/${rk.Id}`}
                              className="font-semibold text-foreground hover:underline block"
                            >
                              {rk.aset?.Nama ?? 'Aset'}
                            </Link>
                            <span className="font-mono text-[11px] text-muted-foreground">
                              {rk.aset?.KodeAset}
                            </span>
                          </td>
                          <td className="px-4 py-3 text-muted-foreground">
                            {rk.jenisKalibrasi?.Nama ?? '—'}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground">
                            {rk.penyedia?.Nama ? (
                              <span>{rk.penyedia.Nama}</span>
                            ) : (
                              <span className="text-muted-foreground italic">Internal</span>
                            )}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground whitespace-nowrap">
                            Setiap {rk.IntervalHari} hari
                          </td>
                          <td className="px-3 py-3 text-muted-foreground whitespace-nowrap">
                            <div className="font-mono">{rk.TanggalBerikutnya}</div>
                            {rk.SisaHari !== undefined && (
                              <div
                                className={`text-[11px] ${
                                  rk.SisaHari < 0
                                    ? 'text-bahaya-600 font-semibold'
                                    : rk.SisaHari <= rk.PeringatanHariSebelum
                                    ? 'text-safety-600 font-medium'
                                    : 'text-muted-foreground'
                                }`}
                              >
                                {rk.SisaHari < 0
                                  ? `Terlambat ${Math.abs(rk.SisaHari)} hari`
                                  : `${rk.SisaHari} hari lagi`}
                              </div>
                            )}
                          </td>
                          <td className="px-3 py-3 text-center whitespace-nowrap">
                            <Badge variant="outline" className={badge.className}>
                              {badge.label}
                            </Badge>
                          </td>
                          <td className="px-4 py-3 text-right whitespace-nowrap">
                            <div className="flex items-center justify-end gap-1">
                              <Button asChild variant="ghost" size="sm" className="h-7 text-xs">
                                <Link href={`/kalibrasi/rencana/${rk.Id}`}>
                                  Detail
                                  <ArrowRight className="size-3 ml-1" />
                                </Link>
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => bukaModalEdit(rk)}
                                className="h-7 w-7 text-muted-foreground hover:text-foreground"
                              >
                                <Pencil className="size-3.5" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => hapusRencana(rk)}
                                className="h-7 w-7 text-bahaya-600 hover:text-bahaya-700 hover:bg-rose-50"
                              >
                                <Trash2 className="size-3.5" />
                              </Button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Modal Dialog Buat/Edit Rencana Kalibrasi */}
      <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {rencanaDiedit ? 'Edit Rencana Kalibrasi' : 'Buat Rencana Kalibrasi Baru'}
            </DialogTitle>
            <DialogDescription>
              Tentukan aset, siklus periode kalibrasi ulang, dan jendela peringatan jatuh tempo.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={onSubmit} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="AsetId">Pilih Aset / Instrumen *</Label>
              <Select
                value={form.data.AsetId}
                onValueChange={(val) => form.setData('AsetId', val)}
                required
              >
                <SelectTrigger className="h-9 text-xs">
                  <SelectValue placeholder="Pilih Aset" />
                </SelectTrigger>
                <SelectContent className="max-h-56">
                  {aset.map((a) => (
                    <SelectItem key={a.Id} value={a.Id}>
                      {a.KodeAset} - {a.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.AsetId && (
                <p className="text-xs text-rose-600">{form.errors.AsetId}</p>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="JenisKalibrasiId">Jenis Kalibrasi</Label>
                <Select
                  value={form.data.JenisKalibrasiId || '__none__'}
                  onValueChange={(val) => form.setData('JenisKalibrasiId', val === '__none__' ? '' : val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Pilih Jenis (Opsional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__none__">Tanpa Spesifikasi Jenis</SelectItem>
                    {jenisKalibrasi.map((jk) => (
                      <SelectItem key={jk.Id} value={jk.Id}>
                        {jk.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="PenyediaId">Penyedia / Laboratorium Rekanan</Label>
                <Select
                  value={form.data.PenyediaId || '__internal__'}
                  onValueChange={(val) => form.setData('PenyediaId', val === '__internal__' ? '' : val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Internal / Rekanan" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__internal__">Internal Perusahaan</SelectItem>
                    {penyedia.map((p) => (
                      <SelectItem key={p.Id} value={p.Id}>
                        {p.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="IntervalHari">Interval (Hari) *</Label>
                <Input
                  id="IntervalHari"
                  type="number"
                  min={1}
                  placeholder="365"
                  value={form.data.IntervalHari}
                  onChange={(e) => {
                    const interval = Number(e.target.value);
                    form.setData({
                      IntervalHari: interval,
                      TanggalBerikutnya: hitungTanggalBerikutnya(form.data.TanggalMulai, interval),
                    });
                  }}
                  required
                  className="h-9 text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="TanggalMulai">Tanggal Mulai *</Label>
                <Input
                  id="TanggalMulai"
                  type="date"
                  value={form.data.TanggalMulai}
                  onChange={(e) => {
                    const tglMulai = e.target.value;
                    form.setData({
                      TanggalMulai: tglMulai,
                      TanggalBerikutnya: hitungTanggalBerikutnya(tglMulai, form.data.IntervalHari),
                    });
                  }}
                  required
                  className="h-9 text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="TanggalBerikutnya">Jatuh Tempo Berikutnya *</Label>
                <Input
                  id="TanggalBerikutnya"
                  type="date"
                  value={form.data.TanggalBerikutnya}
                  onChange={(e) => form.setData('TanggalBerikutnya', e.target.value)}
                  required
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="PeringatanHariSebelum">Jendela Pengingat Peringatan (Hari Sebelum)</Label>
              <Input
                id="PeringatanHariSebelum"
                type="number"
                min={1}
                placeholder="30"
                value={form.data.PeringatanHariSebelum}
                onChange={(e) => form.setData('PeringatanHariSebelum', Number(e.target.value))}
                className="h-9 text-xs"
              />
              <p className="text-[11px] text-zinc-500">
                Sistem akan memicu status "Segera Jatuh Tempo" dan mengirim notifikasi saat waktu tersisa mencapai nilai ini.
              </p>
            </div>

            <div className="flex items-center justify-between p-2.5 rounded-lg border border-border">
              <div className="space-y-0.5">
                <Label htmlFor="AktifRencana">Status Aktif</Label>
                <p className="text-xs text-zinc-500">Rencana aktif diperhitungkan dalam kepatuhan dan notifikasi.</p>
              </div>
              <Switch
                id="AktifRencana"
                checked={form.data.Aktif}
                onCheckedChange={(checked) => form.setData('Aktif', checked)}
              />
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => setBukaDialog(false)}
              >
                Batal
              </Button>
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Menyimpan...' : 'Simpan Rencana'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
