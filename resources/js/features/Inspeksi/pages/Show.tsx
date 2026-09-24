import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, Calendar, Building, Wrench, FileCheck, PlusCircle, ExternalLink } from 'lucide-react';
import type { Inspeksi } from '@/features/PreventifInspeksi/types';
import { statusInspeksiBadge, hasilInspeksiBadge } from '@/features/PreventifInspeksi/status';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { ruteDaftarPeriksa } from '@/features/DaftarPeriksa/api';
import { tanggalHariIni, tanggalLokal } from '@/lib/waktu';

interface Props {
  inspeksi: Inspeksi;
}

export default function InspeksiShow({ inspeksi }: Props) {
  const [bukaDialogHasil, setBukaDialogHasil] = useState(false);
  const [bukaDialogPK, setBukaDialogPK] = useState(false);

  const formHasil = useForm({
    Hasil: inspeksi.Hasil || 'Lolos',
    Temuan: inspeksi.Temuan || '',
    TindakLanjut: inspeksi.TindakLanjut || '',
    DilaksanakanPada: tanggalHariIni(),
  });

  const formPK = useForm({
    Judul: `Tindak Lanjut Inspeksi ${inspeksi.Nomor}`,
    Deskripsi: inspeksi.Temuan || 'Temuan inspeksi memerlukan perbaikan.',
    Prioritas: 'Tinggi',
  });

  const simpanHasil = (e: FormEvent) => {
    e.preventDefault();
    formHasil.post(ruteInspeksi.laksanakan(inspeksi.Id), {
      onSuccess: () => {
        setBukaDialogHasil(false);
      },
    });
  };

  const buatPerintahKerjaKorektif = (e: FormEvent) => {
    e.preventDefault();
    formPK.post(ruteInspeksi.buatPerintahKerja(inspeksi.Id), {
      onSuccess: () => {
        setBukaDialogPK(false);
      },
    });
  };

  const statusBadge = statusInspeksiBadge[inspeksi.Status] ?? { label: inspeksi.Status, kelas: '' };
  const hasilBadge = inspeksi.Hasil ? hasilInspeksiBadge[inspeksi.Hasil] : null;

  return (
    <KerangkaAplikasi>
      <Head title={`Inspeksi ${inspeksi.Nomor}`} />
      <BreadcrumbHalaman />

      <div className="space-y-5 max-w-4xl mx-auto">
        {/* Navigasi Balik */}
        <div className="flex items-center gap-2 text-sm text-grafit-500">
          <Link
            href={ruteInspeksi.index}
            className="inline-flex min-h-11 items-center gap-1 rounded-[5px] underline-offset-4 hover:text-grafit-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Inspeksi</span>
          </Link>
        </div>

        {/* Header Kartu Inspeksi */}
        <div className="rounded-md border border-border bg-card p-5">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <span className="font-mono text-sm font-semibold text-grafit-950">{inspeksi.Nomor}</span>
                <Badge variant="outline" className={statusBadge.kelas}>
                  {statusBadge.label}
                </Badge>
                {hasilBadge && (
                  <Badge variant="outline" className={hasilBadge.kelas}>
                    {hasilBadge.label}
                  </Badge>
                )}
              </div>

              <h1 className="text-[15px] font-semibold text-foreground">
                {inspeksi.templat_inspeksi?.Nama ?? 'Inspeksi Aset'}
              </h1>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-grafit-700 pt-2">
                <div className="flex items-center gap-1.5">
                  <Building className="h-3.5 w-3.5 text-grafit-500" />
                  <span>
                    Aset:{' '}
                    <strong>
                      {inspeksi.aset?.KodeAset} - {inspeksi.aset?.Nama}
                    </strong>
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Calendar className="h-3.5 w-3.5 text-grafit-500" />
                  <span>
                    Jadwal:{' '}
                    <strong>{inspeksi.DijadwalkanPada ? tanggalLokal(inspeksi.DijadwalkanPada) : '-'}</strong>
                  </span>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-2 self-start">
              {inspeksi.Status !== 'Selesai' && (
                <Button className="cursor-pointer" onClick={() => setBukaDialogHasil(true)}>
                  <FileCheck className="h-4 w-4" />
                  Catat Hasil Inspeksi
                </Button>
              )}

              {inspeksi.PerintahKerjaId ? (
                <Button asChild variant="outline">
                  <Link href={rutePerintahKerja.detail(inspeksi.PerintahKerjaId)}>
                    <Wrench className="h-3.5 w-3.5" />
                    Buka WO ({inspeksi.perintah_kerja?.Nomor ?? 'Tindak Lanjut'})
                  </Link>
                </Button>
              ) : (
                (inspeksi.Hasil === 'Gagal' ||
                  inspeksi.Hasil === 'PerluPerhatian' ||
                  Boolean(inspeksi.Temuan)) && (
                  <Button
                    variant="destructive"
                    className="cursor-pointer"
                    onClick={() => setBukaDialogPK(true)}
                  >
                    <PlusCircle className="h-4 w-4" />
                    Buat WO Korektif
                  </Button>
                )
              )}
            </div>
          </div>
        </div>

        {/* Tautan Lembar Checklist jika terhubung */}
        {inspeksi.PelaksanaanDaftarPeriksaId && (
          <div className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-info-600/25 bg-info-600/5 px-4 py-3">
            <div className="flex items-center gap-3">
              <FileCheck aria-hidden="true" className="size-4 shrink-0 text-info-700" />
              <div>
                <h3 className="font-semibold text-info-700 text-sm">Lembar Checklist Terlampir</h3>
                <p className="text-xs text-info-700">
                  Inspeksi ini dilengkapi lembar parameter periksa lapangan terstandar.
                </p>
              </div>
            </div>

            <Button asChild variant="outline">
              <Link href={ruteDaftarPeriksa.pelaksanaanDetail(inspeksi.PelaksanaanDaftarPeriksaId)}>
                <span>Buka Checklist</span>
                <ExternalLink className="h-3.5 w-3.5" />
              </Link>
            </Button>
          </div>
        )}

        {/* Rincian Hasil & Temuan */}
        <div className="rounded-md border border-border bg-card p-5 space-y-4">
          <h2 className="text-sm font-semibold text-foreground">Hasil & Catatan Temuan Lapangan</h2>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="p-4 bg-permukaan-50 rounded-md space-y-1">
              <span className="text-xs text-grafit-500 font-medium">Kesimpulan Kondisi</span>
              <div className="pt-1">
                {hasilBadge ? (
                  <Badge variant="outline" className={hasilBadge.kelas}>
                    {hasilBadge.label}
                  </Badge>
                ) : (
                  <span className="text-sm text-grafit-500 font-medium">Belum dilakukan inspeksi</span>
                )}
              </div>
            </div>

            <div className="p-4 bg-permukaan-50 rounded-md space-y-1">
              <span className="text-xs text-grafit-500 font-medium">Waktu & Pelaksana</span>
              <div className="text-xs font-semibold text-grafit-950 pt-1">
                {inspeksi.DilaksanakanPada ? (
                  <span>
                    Dilaksanakan pada {tanggalLokal(inspeksi.DilaksanakanPada)} oleh{' '}
                    {inspeksi.dilaksanakan_oleh?.Nama ?? 'Petugas'}
                  </span>
                ) : (
                  <span className="text-grafit-500 font-normal">Belum dilaksanakan</span>
                )}
              </div>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label className="text-xs text-grafit-500 font-medium">Deskripsi Temuan Lapangan</Label>
            <div className="p-3 bg-card border border-border rounded-md text-sm text-grafit-950 min-h-[60px] whitespace-pre-wrap">
              {inspeksi.Temuan || <span className="text-grafit-500 italic">Tidak ada temuan khusus.</span>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label className="text-xs text-grafit-500 font-medium">Rekomendasi Tindak Lanjut</Label>
            <div className="p-3 bg-card border border-border rounded-md text-sm text-grafit-950 min-h-[60px] whitespace-pre-wrap">
              {inspeksi.TindakLanjut || (
                <span className="text-grafit-500 italic">Belum ada rekomendasi tindak lanjut.</span>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modal Catat Hasil Inspeksi */}
      <Dialog open={bukaDialogHasil} onOpenChange={setBukaDialogHasil}>
        <DialogContent className="sm:max-w-md">
          <form onSubmit={simpanHasil}>
            <DialogHeader>
              <DialogTitle>Catat Hasil Inspeksi</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 py-4">
              <div className="space-y-1.5">
                <Label htmlFor="Hasil">
                  Hasil Evaluasi <span className="text-destructive">*</span>
                </Label>
                <Select
                  value={formHasil.data.Hasil}
                  onValueChange={(val) =>
                    formHasil.setData('Hasil', val as 'Lolos' | 'PerluPerhatian' | 'Gagal')
                  }
                >
                  <SelectTrigger id="Hasil" className="cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Lolos">Lolos (Aset Beroperasi Normal)</SelectItem>
                    <SelectItem value="PerluPerhatian">Perlu Perhatian (Ada Penurunan Kondisi)</SelectItem>
                    <SelectItem value="Gagal">Gagal / Rusak (Memerlukan Perbaikan)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="Temuan">Deskripsi Temuan Lapangan</Label>
                <Textarea
                  id="Temuan"
                  placeholder="Catat kondisi fisik, kelainan suara, kebocoran, atau suhu tidak wajar..."
                  value={formHasil.data.Temuan}
                  onChange={(e) => formHasil.setData('Temuan', e.target.value)}
                  rows={3}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="TindakLanjut">Rekomendasi Tindak Lanjut</Label>
                <Textarea
                  id="TindakLanjut"
                  placeholder="Langkah perbaikan atau penggantian suku cadang yang dianjurkan..."
                  value={formHasil.data.TindakLanjut}
                  onChange={(e) => formHasil.setData('TindakLanjut', e.target.value)}
                  rows={2}
                />
              </div>
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                className="cursor-pointer"
                onClick={() => setBukaDialogHasil(false)}
              >
                Batal
              </Button>
              <Button type="submit" className="cursor-pointer" disabled={formHasil.processing}>
                {formHasil.processing ? 'Menyimpan...' : 'Simpan Hasil'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Modal Buat Perintah Kerja Korektif */}
      <Dialog open={bukaDialogPK} onOpenChange={setBukaDialogPK}>
        <DialogContent className="sm:max-w-md">
          <form onSubmit={buatPerintahKerjaKorektif}>
            <DialogHeader>
              <DialogTitle>Buat Perintah Kerja Korektif</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 py-4">
              <div className="space-y-1.5">
                <Label htmlFor="Judul">
                  Judul Pekerjaan <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="Judul"
                  value={formPK.data.Judul}
                  onChange={(e) => formPK.setData('Judul', e.target.value)}
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="Prioritas">Prioritas</Label>
                <Select
                  value={formPK.data.Prioritas}
                  onValueChange={(val) => formPK.setData('Prioritas', val)}
                >
                  <SelectTrigger id="Prioritas" className="cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Normal">Normal</SelectItem>
                    <SelectItem value="Tinggi">Tinggi</SelectItem>
                    <SelectItem value="Darurat">Darurat</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="Deskripsi">Deskripsi Masalah / Instruksi Kerja</Label>
                <Textarea
                  id="Deskripsi"
                  value={formPK.data.Deskripsi}
                  onChange={(e) => formPK.setData('Deskripsi', e.target.value)}
                  rows={3}
                  required
                />
              </div>
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                className="cursor-pointer"
                onClick={() => setBukaDialogPK(false)}
              >
                Batal
              </Button>
              <Button
                type="submit"
                variant="destructive"
                className="cursor-pointer"
                disabled={formPK.processing}
              >
                {formPK.processing ? 'Membuat WO...' : 'Buat Perintah Kerja'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </KerangkaAplikasi>
  );
}
