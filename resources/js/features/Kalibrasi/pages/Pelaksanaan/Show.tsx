import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import {
  ArrowLeft,
  AlertTriangle,
  FileBadge,
  Calendar,
  Plus,
  Trash2,
  Save,
  Check,
  ShieldCheck,
} from 'lucide-react';
import type { PelaksanaanKalibrasi } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';

interface Props {
  pelaksanaan: PelaksanaanKalibrasi;
  teknisi: { Id: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
}

interface ItemTitikUkurRow {
  [key: string]: any;
  Id?: string;
  TitikUkurKalibrasiId?: string | null;
  NamaTitik: string;
  NilaiReferensi: string | number;
  ToleransiMinus: string | number;
  ToleransiPlus: string | number;
  NilaiTerukur: string | number;
  Koreksi: string | number;
  Ketidakpastian: string | number;
  Satuan: string;
  Hasil: string;
  Catatan: string;
}

export default function KalibrasiPelaksanaanShow({ pelaksanaan, teknisi, penyedia }: Props) {
  const badgeHasil = hasilKalibrasiBadge(pelaksanaan.Hasil);
  const sudahVerifikasi = Boolean(pelaksanaan.DiverifikasiPada);

  // Inisialisasi baris titik ukur dari data yang ada
  const [titikRows, setTitikRows] = useState<ItemTitikUkurRow[]>(() => {
    if (pelaksanaan.hasilTitikUkur && pelaksanaan.hasilTitikUkur.length > 0) {
      return pelaksanaan.hasilTitikUkur.map((h) => ({
        Id: h.Id,
        TitikUkurKalibrasiId: h.TitikUkurKalibrasiId,
        NamaTitik: h.NamaTitik,
        NilaiReferensi: h.NilaiReferensi ?? '',
        ToleransiMinus: h.titikUkurKalibrasi?.ToleransiMinus ?? '0',
        ToleransiPlus: h.titikUkurKalibrasi?.ToleransiPlus ?? '0',
        NilaiTerukur: h.NilaiTerukur ?? '',
        Koreksi: h.Koreksi ?? '',
        Ketidakpastian: h.Ketidakpastian ?? '',
        Satuan: h.Satuan ?? '',
        Hasil: h.Hasil ?? 'BelumDiuji',
        Catatan: h.Catatan ?? '',
      }));
    }
    return [];
  });

  const [bukaModalFinalisasi, setBukaModalFinalisasi] = useState(false);

  // Form Finalisasi
  const formFinalisasi = useForm({
    Hasil: pelaksanaan.Hasil === 'Terjadwal' ? 'Lolos' : pelaksanaan.Hasil,
    NomorSertifikat: pelaksanaan.NomorSertifikat ?? '',
    TanggalKalibrasi: pelaksanaan.TanggalKalibrasi,
    TanggalBerlakuSampai: pelaksanaan.TanggalBerlakuSampai ?? '',
    Laboratorium: pelaksanaan.Laboratorium ?? '',
    Catatan: pelaksanaan.Catatan ?? '',
    KondisiLingkungan: {
      Suhu: (pelaksanaan.KondisiLingkungan as any)?.Suhu ?? '20 °C',
      Kelembapan: (pelaksanaan.KondisiLingkungan as any)?.Kelembapan ?? '55% RH',
    },
  });

  // Evaluasi dinamis saat nilai terukur diubah
  const updateNilaiTerukur = (index: number, nilai: string) => {
    setTitikRows((prev) => {
      const copy = [...prev];
      const row = { ...copy[index] };
      row.NilaiTerukur = nilai;

      if (nilai !== '' && row.NilaiReferensi !== '') {
        const terukur = parseFloat(nilai);
        const ref = parseFloat(String(row.NilaiReferensi));
        const minus = parseFloat(String(row.ToleransiMinus || '0'));
        const plus = parseFloat(String(row.ToleransiPlus || '0'));

        const koreksiVal = terukur - ref;
        row.Koreksi = isNaN(koreksiVal) ? '' : Number(koreksiVal.toFixed(4));

        const min = ref - minus;
        const max = ref + plus;
        row.Hasil = terukur >= min && terukur <= max ? 'Lolos' : 'Gagal';
      } else {
        row.Koreksi = '';
        row.Hasil = 'BelumDiuji';
      }

      copy[index] = row;
      return copy;
    });
  };

  const updateFieldRow = (index: number, field: keyof ItemTitikUkurRow, val: any) => {
    setTitikRows((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: val };
      return copy;
    });
  };

  const tambahBarisBaru = () => {
    setTitikRows((prev) => [
      ...prev,
      {
        NamaTitik: `Titik Ukur ${prev.length + 1}`,
        NilaiReferensi: '',
        ToleransiMinus: '0.1',
        ToleransiPlus: '0.1',
        NilaiTerukur: '',
        Koreksi: '',
        Ketidakpastian: '',
        Satuan: prev[0]?.Satuan ?? '',
        Hasil: 'BelumDiuji',
        Catatan: '',
      },
    ]);
  };

  const hapusBaris = (index: number) => {
    setTitikRows((prev) => prev.filter((_, idx) => idx !== index));
  };

  const simpanTitikUkurKeServer = () => {
    router.put(
      ruteKalibrasi.pelaksanaanHasilTitikUkur(pelaksanaan.Id),
      { hasil: titikRows },
      { preserveScroll: true },
    );
  };

  const submitFinalisasi = (e: FormEvent) => {
    e.preventDefault();
    formFinalisasi.post(ruteKalibrasi.pelaksanaanFinalisasi(pelaksanaan.Id), {
      onSuccess: () => {
        setBukaModalFinalisasi(false);
      },
    });
  };

  const adaTitikGagal = titikRows.some((r) => r.Hasil === 'Gagal');

  return (
    <KerangkaAplikasi>
      <Head title={`Kalibrasi ${pelaksanaan.Nomor} - ${pelaksanaan.aset?.Nama}`} />
      <BreadcrumbHalaman />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-center gap-3">
            <Button asChild variant="outline" size="icon" className="size-8">
              <Link href={ruteKalibrasi.pelaksanaan}>
                <ArrowLeft className="size-4" />
              </Link>
            </Button>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-semibold tracking-tight text-foreground font-mono">
                  {pelaksanaan.Nomor}
                </h1>
                <Badge variant="outline" className={badgeHasil.className}>
                  {badgeHasil.label}
                </Badge>
                {sudahVerifikasi && (
                  <Badge variant="outline" className="bg-sukses-50 text-sukses-600 border-sukses-200 gap-1">
                    <ShieldCheck className="size-3" />
                    Terverifikasi
                  </Badge>
                )}
              </div>
              <p className="text-xs text-muted-foreground mt-0.5">
                Aset: <span className="font-semibold text-foreground">{pelaksanaan.aset?.Nama}</span> (
                <span className="font-mono">{pelaksanaan.aset?.KodeAset}</span>)
                {pelaksanaan.rencanaKalibrasi && ` • Terhubung ke Rencana Kalibrasi`}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {!sudahVerifikasi && (
              <Button onClick={() => setBukaModalFinalisasi(true)} size="sm">
                <FileBadge className="mr-1.5 size-4" />
                Finalisasi & Sahkan Sertifikat
              </Button>
            )}
          </div>
        </div>

        {/* Certificate Authorized Banner if verified */}
        {sudahVerifikasi && (
          <div className="p-4 rounded-[8px] bg-sukses-50 border border-sukses-200 flex items-start gap-3">
            <FileBadge className="size-5 text-sukses-600 mt-0.5 shrink-0" />
            <div className="space-y-1 text-xs">
              <div className="font-semibold text-foreground">
                Sertifikat Kalibrasi Resmi Terotorisasi:{' '}
                <span className="font-mono">{pelaksanaan.NomorSertifikat}</span>
              </div>
              <p className="text-grafit-700">
                Diverifikasi oleh{' '}
                <span className="font-medium">
                  {pelaksanaan.diverifikasiOleh?.Nama ?? 'Petugas Berwenang'}
                </span>{' '}
                pada {pelaksanaan.DiverifikasiPada}. Berlaku sampai dengan{' '}
                <span className="font-bold font-mono">{pelaksanaan.TanggalBerlakuSampai ?? '—'}</span>. Siklus
                kalibrasi berikutnya pada instrumen telah otomatis diperbarui.
              </p>
            </div>
          </div>
        )}

        {/* Metadata Overview Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                <Calendar className="size-4 text-teknisi-700" />
                Informasi Kalibrasi
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Tanggal Kalibrasi:</span>
                <span className="font-medium font-mono text-foreground">{pelaksanaan.TanggalKalibrasi}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Berlaku Sampai:</span>
                <span className="font-semibold font-mono text-foreground">
                  {pelaksanaan.TanggalBerlakuSampai || (
                    <span className="text-muted-foreground italic">Belum diatur</span>
                  )}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Laboratorium Uji:</span>
                <span className="font-medium text-foreground">{pelaksanaan.Laboratorium || '—'}</span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Nomor Sertifikat:</span>
                <span className="font-mono font-bold text-foreground">
                  {pelaksanaan.NomorSertifikat || (
                    <span className="text-muted-foreground font-normal italic">Belum terbit</span>
                  )}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                Pelaksana & Verifikator
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Teknisi / Pelaksana:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.dilaksanakanOleh?.Nama ?? '—'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Penyedia / Mitra:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.penyedia?.Nama ?? 'Internal'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Diverifikasi Oleh:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.diverifikasiOleh?.Nama ?? (
                    <span className="text-muted-foreground italic">Menunggu Finalisasi</span>
                  )}
                </span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Waktu Verifikasi:</span>
                <span className="text-muted-foreground font-mono">{pelaksanaan.DiverifikasiPada ?? '—'}</span>
              </div>
            </CardContent>
          </Card>

          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                Kondisi Lingkungan & Catatan
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Suhu Ruang:</span>
                <span className="font-medium text-foreground">
                  {(pelaksanaan.KondisiLingkungan as any)?.Suhu || '—'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Kelembapan Udara:</span>
                <span className="font-medium text-foreground">
                  {(pelaksanaan.KondisiLingkungan as any)?.Kelembapan || '—'}
                </span>
              </div>
              <div className="py-1">
                <span className="text-muted-foreground block mb-1">Catatan Pengujian:</span>
                <p className="text-grafit-700 italic bg-permukaan-100 p-2 rounded-[6px] text-[11px] border border-border">
                  {pelaksanaan.Catatan || 'Tidak ada catatan khusus.'}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Tabel Titik Ukur & Hasil Kalibrasi (14.04 TitikUkur & Pass/Fail) */}
        <Card className="border-border">
          <CardHeader className="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <CardTitle className="text-base font-semibold text-foreground">
                Hasil Uji Titik Ukur Instrumen
              </CardTitle>
              <p className="text-xs text-muted-foreground mt-0.5">
                Nilai terukur, toleransi kesalahan maksimum, koreksi aktual, dan evaluasi lolos/gagal
              </p>
            </div>

            <div className="flex items-center gap-2">
              {!sudahVerifikasi && (
                <>
                  <Button type="button" variant="outline" size="sm" onClick={tambahBarisBaru}>
                    <Plus className="mr-1 size-3.5" />
                    Tambah Titik
                  </Button>
                  <Button type="button" size="sm" onClick={simpanTitikUkurKeServer}>
                    <Save className="mr-1 size-3.5" />
                    Simpan Titik Ukur
                  </Button>
                </>
              )}
            </div>
          </CardHeader>

          <CardContent className="p-0">
            {titikRows.length === 0 ? (
              <div className="py-12">
                <KeadaanKosong
                  judul="Belum ada titik ukur."
                  deskripsi="Gunakan tombol Tambah Titik di atas untuk mendefinisikan parameter pengujian."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-muted-foreground border-b border-border">
                    <tr>
                      <th className="px-3 py-2.5 w-8 text-center">#</th>
                      <th className="px-3 py-2.5 font-medium">Nama Titik Uji</th>
                      <th className="px-3 py-2.5 font-medium w-28">Nilai Referensi</th>
                      <th className="px-3 py-2.5 font-medium w-24">Toleransi (-)</th>
                      <th className="px-3 py-2.5 font-medium w-24">Toleransi (+)</th>
                      <th className="px-3 py-2.5 font-medium w-32">Nilai Terukur *</th>
                      <th className="px-3 py-2.5 font-medium w-24">Koreksi</th>
                      <th className="px-3 py-2.5 font-medium w-20">Satuan</th>
                      <th className="px-3 py-2.5 font-medium w-28 text-center">Evaluasi</th>
                      <th className="px-3 py-2.5 font-medium">Catatan</th>
                      {!sudahVerifikasi && <th className="px-3 py-2.5 w-10 text-right"></th>}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {titikRows.map((row, idx) => {
                      const isLolos = row.Hasil === 'Lolos';
                      const isGagal = row.Hasil === 'Gagal';

                      return (
                        <tr
                          key={idx}
                          className={`hover:bg-permukaan-50 transition-colors ${
                            isGagal ? 'bg-rose-50/50' : ''
                          }`}
                        >
                          <td className="px-3 py-2 text-center text-muted-foreground font-mono text-[11px]">
                            {idx + 1}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="font-medium text-foreground">{row.NamaTitik}</span>
                            ) : (
                              <Input
                                value={row.NamaTitik}
                                onChange={(e) => updateFieldRow(idx, 'NamaTitik', e.target.value)}
                                className="h-7 text-xs"
                              />
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="font-mono text-foreground">{row.NilaiReferensi}</span>
                            ) : (
                              <Input
                                type="number"
                                step="any"
                                value={row.NilaiReferensi}
                                onChange={(e) => updateFieldRow(idx, 'NilaiReferensi', e.target.value)}
                                className="h-7 text-xs font-mono"
                              />
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="font-mono text-muted-foreground">{row.ToleransiMinus}</span>
                            ) : (
                              <Input
                                type="number"
                                step="any"
                                value={row.ToleransiMinus}
                                onChange={(e) => updateFieldRow(idx, 'ToleransiMinus', e.target.value)}
                                className="h-7 text-xs font-mono"
                              />
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="font-mono text-muted-foreground">{row.ToleransiPlus}</span>
                            ) : (
                              <Input
                                type="number"
                                step="any"
                                value={row.ToleransiPlus}
                                onChange={(e) => updateFieldRow(idx, 'ToleransiPlus', e.target.value)}
                                className="h-7 text-xs font-mono"
                              />
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="font-mono font-bold text-foreground">
                                {row.NilaiTerukur || '—'}
                              </span>
                            ) : (
                              <Input
                                type="number"
                                step="any"
                                placeholder="Input..."
                                value={row.NilaiTerukur}
                                onChange={(e) => updateNilaiTerukur(idx, e.target.value)}
                                className={`h-7 text-xs font-mono font-semibold ${
                                  isGagal ? 'border-bahaya-600 bg-rose-50 text-bahaya-600' : ''
                                }`}
                              />
                            )}
                          </td>

                          <td className="px-3 py-2 font-mono text-foreground">
                            {row.Koreksi !== '' ? (
                              <span
                                className={Number(row.Koreksi) !== 0 ? 'text-teknisi-700 font-semibold' : ''}
                              >
                                {Number(row.Koreksi) > 0 ? `+${row.Koreksi}` : row.Koreksi}
                              </span>
                            ) : (
                              '—'
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="text-muted-foreground">{row.Satuan}</span>
                            ) : (
                              <Input
                                value={row.Satuan}
                                onChange={(e) => updateFieldRow(idx, 'Satuan', e.target.value)}
                                className="h-7 text-xs w-16"
                              />
                            )}
                          </td>

                          <td className="px-3 py-2 text-center whitespace-nowrap">
                            {isLolos && (
                              <Badge
                                variant="outline"
                                className="bg-sukses-50 text-sukses-600 border-sukses-200 text-[10px] py-0 px-2 gap-1"
                              >
                                <Check className="size-3" /> Lolos
                              </Badge>
                            )}
                            {isGagal && (
                              <Badge
                                variant="outline"
                                className="bg-rose-50 text-bahaya-600 border-rose-200 text-[10px] py-0 px-2 gap-1"
                              >
                                <AlertTriangle className="size-3" /> Gagal
                              </Badge>
                            )}
                            {!isLolos && !isGagal && (
                              <Badge
                                variant="outline"
                                className="bg-permukaan-100 text-muted-foreground border-garis-300 text-[10px] py-0 px-2"
                              >
                                Belum Diuji
                              </Badge>
                            )}
                          </td>

                          <td className="px-3 py-2">
                            {sudahVerifikasi ? (
                              <span className="text-muted-foreground">{row.Catatan || '—'}</span>
                            ) : (
                              <Input
                                placeholder="Keterangan deviasi..."
                                value={row.Catatan}
                                onChange={(e) => updateFieldRow(idx, 'Catatan', e.target.value)}
                                className="h-7 text-xs"
                              />
                            )}
                          </td>

                          {!sudahVerifikasi && (
                            <td className="px-3 py-2 text-right">
                              <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => hapusBaris(idx)}
                                className="size-6 text-muted-foreground hover:text-bahaya-600"
                              >
                                <Trash2 className="size-3.5" />
                              </Button>
                            </td>
                          )}
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}

            {adaTitikGagal && !sudahVerifikasi && (
              <div className="p-3 bg-amber-50 border-t border-safety-500/30 text-xs text-safety-600 flex items-center gap-2">
                <AlertTriangle className="size-4 shrink-0 text-safety-600" />
                <span>
                  Perhatian: Terdapat nilai titik ukur di luar batas toleransi yang diizinkan. Pertimbangkan
                  untuk memberi status <strong>Gagal</strong> atau <strong>Lolos dengan Catatan</strong> saat
                  finalisasi.
                </span>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Dialog Finalisasi & Otorisasi Sertifikat (14.03 & Gate 14) */}
      <Dialog open={bukaModalFinalisasi} onOpenChange={setBukaModalFinalisasi}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2 text-foreground">
              <FileBadge className="size-5 text-teknisi-700" />
              Finalisasi Kalibrasi & Otorisasi Sertifikat
            </DialogTitle>
            <DialogDescription>
              Pengesahan hasil pengujian. Jika kalibrasi lolos dan terhubung ke rencana kalibrasi, siklus
              tanggal kalibrasi berikutnya akan otomatis dimajukan.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={submitFinalisasi} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="HasilFinal">Hasil Kesimpulan Kalibrasi *</Label>
              <Select
                value={formFinalisasi.data.Hasil}
                onValueChange={(val) => formFinalisasi.setData('Hasil', val)}
              >
                <SelectTrigger className="h-9 text-xs">
                  <SelectValue placeholder="Pilih Hasil" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Lolos">Lolos (Memenuhi seluruh toleransi)</SelectItem>
                  <SelectItem value="LolosDenganCatatan">Lolos dengan Catatan Deviasi</SelectItem>
                  <SelectItem value="Gagal">Gagal (Tidak memenuhi standar)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="NomorSertifikat">Nomor Sertifikat Resmi *</Label>
              <Input
                id="NomorSertifikat"
                placeholder="mis. CERT-CAL/2026/09/0081"
                value={formFinalisasi.data.NomorSertifikat}
                onChange={(e) => formFinalisasi.setData('NomorSertifikat', e.target.value)}
                required
                className="h-9 text-xs font-mono"
              />
              {formFinalisasi.errors.NomorSertifikat && (
                <p className="text-xs text-bahaya-600">{formFinalisasi.errors.NomorSertifikat}</p>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="TglKalibrasi">Tanggal Pengujian *</Label>
                <Input
                  id="TglKalibrasi"
                  type="date"
                  value={formFinalisasi.data.TanggalKalibrasi}
                  onChange={(e) => formFinalisasi.setData('TanggalKalibrasi', e.target.value)}
                  required
                  className="h-9 text-xs font-mono"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="TglBerlaku">Berlaku Sampai (Jatuh Tempo)</Label>
                <Input
                  id="TglBerlaku"
                  type="date"
                  value={formFinalisasi.data.TanggalBerlakuSampai}
                  onChange={(e) => formFinalisasi.setData('TanggalBerlakuSampai', e.target.value)}
                  className="h-9 text-xs font-mono"
                />
                <p className="text-[10px] text-muted-foreground">
                  Kosongkan jika ingin dihitung otomatis dari interval rencana kalibrasi.
                </p>
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="LaboratoriumUji">Nama Laboratorium Penguji</Label>
              <Input
                id="LaboratoriumUji"
                placeholder="mis. Balai Kalibrasi Standar Industri"
                value={formFinalisasi.data.Laboratorium}
                onChange={(e) => formFinalisasi.setData('Laboratorium', e.target.value)}
                className="h-9 text-xs"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="KondisiSuhu">Suhu Lingkungan Ruang</Label>
                <Input
                  id="KondisiSuhu"
                  placeholder="20 ± 2 °C"
                  value={formFinalisasi.data.KondisiLingkungan.Suhu}
                  onChange={(e) =>
                    formFinalisasi.setData('KondisiLingkungan', {
                      ...formFinalisasi.data.KondisiLingkungan,
                      Suhu: e.target.value,
                    })
                  }
                  className="h-9 text-xs"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="KondisiKelembapan">Kelembapan Udara</Label>
                <Input
                  id="KondisiKelembapan"
                  placeholder="55 ± 5 % RH"
                  value={formFinalisasi.data.KondisiLingkungan.Kelembapan}
                  onChange={(e) =>
                    formFinalisasi.setData('KondisiLingkungan', {
                      ...formFinalisasi.data.KondisiLingkungan,
                      Kelembapan: e.target.value,
                    })
                  }
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="CatatanFinal">Catatan Verifikasi & Kesimpulan</Label>
              <Textarea
                id="CatatanFinal"
                placeholder="mis. Alat telah dikalibrasi sesuai metode perbandingan standar dan memenuhi spesifikasi kelas akurasi."
                rows={2}
                value={formFinalisasi.data.Catatan}
                onChange={(e) => formFinalisasi.setData('Catatan', e.target.value)}
              />
            </div>

            <DialogFooter className="pt-2">
              <Button type="button" variant="outline" onClick={() => setBukaModalFinalisasi(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={formFinalisasi.processing}>
                {formFinalisasi.processing ? 'Menyahkan...' : 'Sahkan Sertifikat'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </KerangkaAplikasi>
  );
}
