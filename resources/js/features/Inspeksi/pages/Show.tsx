import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
  ArrowLeft,
  Calendar,
  Building,
  Wrench,
  CheckCircle2,
  AlertTriangle,
  XCircle,
  FileCheck,
  PlusCircle,
  ExternalLink,
} from 'lucide-react';
import type { Inspeksi } from '@/features/PreventifInspeksi/types';
import { statusInspeksiBadge, hasilInspeksiBadge } from '@/features/PreventifInspeksi/status';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';

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
    DilaksanakanPada: new Date().toISOString().split('T')[0],
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

      <div className="space-y-6 max-w-4xl mx-auto">
        {/* Navigasi Balik */}
        <div className="flex items-center gap-2 text-sm text-permukaan-500">
          <Link
            href={ruteInspeksi.index}
            className="hover:text-permukaan-700 flex items-center gap-1 cursor-pointer"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Inspeksi</span>
          </Link>
        </div>

        {/* Header Kartu Inspeksi */}
        <div className="bg-card border border-permukaan-200 rounded-xl p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <span className="font-mono text-sm font-bold text-permukaan-900">{inspeksi.Nomor}</span>
                <Badge variant="outline" className={statusBadge.kelas}>
                  {statusBadge.label}
                </Badge>
                {hasilBadge && (
                  <Badge variant="outline" className={hasilBadge.kelas}>
                    {hasilBadge.label}
                  </Badge>
                )}
              </div>

              <h1 className="text-xl font-bold text-permukaan-900">
                {inspeksi.templatInspeksi?.Nama ?? 'Inspeksi Aset'}
              </h1>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-permukaan-600 pt-2">
                <div className="flex items-center gap-1.5">
                  <Building className="h-3.5 w-3.5 text-permukaan-400" />
                  <span>
                    Aset:{' '}
                    <strong>
                      {inspeksi.aset?.KodeAset} - {inspeksi.aset?.Nama}
                    </strong>
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Calendar className="h-3.5 w-3.5 text-permukaan-400" />
                  <span>
                    Jadwal: <strong>{inspeksi.DijadwalkanPada?.substring(0, 10) ?? '-'}</strong>
                  </span>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-2 self-start">
              {inspeksi.Status !== 'Selesai' && (
                <Button
                  className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-1.5"
                  onClick={() => setBukaDialogHasil(true)}
                >
                  <FileCheck className="h-4 w-4" />
                  Catat Hasil Inspeksi
                </Button>
              )}

              {inspeksi.PerintahKerjaId ? (
                <Link
                  href={`/pemeliharaan/perintah-kerja/${inspeksi.PerintahKerjaId}`}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold bg-teknisi-50 text-teknisi-700 border border-teknisi-200 hover:bg-teknisi-100 cursor-pointer"
                >
                  <Wrench className="h-3.5 w-3.5" />
                  Buka WO ({inspeksi.perintahKerja?.Nomor ?? 'Tindak Lanjut'})
                </Link>
              ) : (
                (inspeksi.Hasil === 'Gagal' ||
                  inspeksi.Hasil === 'PerluPerhatian' ||
                  Boolean(inspeksi.Temuan)) && (
                  <Button
                    className="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white gap-1.5 text-xs"
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
          <div className="bg-sky-50/50 border border-sky-200 rounded-xl p-5 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <FileCheck className="h-5 w-5 text-sky-600" />
              <div>
                <h3 className="font-semibold text-sky-900 text-sm">Lembar Checklist Terlampir</h3>
                <p className="text-xs text-sky-600">
                  Inspeksi ini dilengkapi lembar parameter periksa lapangan terstandar.
                </p>
              </div>
            </div>

            <Link
              href={`/preventif-inspeksi/pelaksanaan-daftar-periksa/${inspeksi.PelaksanaanDaftarPeriksaId}`}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-sky-700 border border-sky-300 hover:bg-sky-50 shadow-sm cursor-pointer"
            >
              <span>Buka Checklist</span>
              <ExternalLink className="h-3.5 w-3.5" />
            </Link>
          </div>
        )}

        {/* Rincian Hasil & Temuan */}
        <div className="bg-card border border-permukaan-200 rounded-xl p-6 space-y-4">
          <h2 className="text-base font-semibold text-permukaan-900">Hasil & Catatan Temuan Lapangan</h2>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="p-4 bg-permukaan-50 rounded-lg space-y-1">
              <span className="text-xs text-permukaan-500 font-medium">Kesimpulan Kondisi</span>
              <div className="pt-1">
                {hasilBadge ? (
                  <Badge variant="outline" className={hasilBadge.kelas}>
                    {hasilBadge.label}
                  </Badge>
                ) : (
                  <span className="text-sm text-permukaan-400 font-medium">Belum dilakukan inspeksi</span>
                )}
              </div>
            </div>

            <div className="p-4 bg-permukaan-50 rounded-lg space-y-1">
              <span className="text-xs text-permukaan-500 font-medium">Waktu & Pelaksana</span>
              <div className="text-xs font-semibold text-permukaan-800 pt-1">
                {inspeksi.DilaksanakanPada ? (
                  <span>
                    Dilaksanakan pada {inspeksi.DilaksanakanPada.substring(0, 10)} oleh{' '}
                    {inspeksi.dilaksanakanOleh?.Nama ?? 'Petugas'}
                  </span>
                ) : (
                  <span className="text-permukaan-400 font-normal">Belum dilaksanakan</span>
                )}
              </div>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label className="text-xs text-permukaan-500 font-medium">Deskripsi Temuan Lapangan</Label>
            <div className="p-3 bg-white border border-permukaan-200 rounded-lg text-sm text-permukaan-800 min-h-[60px] whitespace-pre-wrap">
              {inspeksi.Temuan || <span className="text-permukaan-400 italic">Tidak ada temuan khusus.</span>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label className="text-xs text-permukaan-500 font-medium">Rekomendasi Tindak Lanjut</Label>
            <div className="p-3 bg-white border border-permukaan-200 rounded-lg text-sm text-permukaan-800 min-h-[60px] whitespace-pre-wrap">
              {inspeksi.TindakLanjut || (
                <span className="text-permukaan-400 italic">Belum ada rekomendasi tindak lanjut.</span>
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
                  Hasil Evaluasi <span className="text-rose-500">*</span>
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
              <Button
                type="submit"
                className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                disabled={formHasil.processing}
              >
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
                  Judul Pekerjaan <span className="text-rose-500">*</span>
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
                className="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white"
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
