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
import { Textarea } from '@/components/ui/textarea';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Edit2,
  Copy,
  CheckCircle2,
  AlertCircle,
  Camera,
  Hash,
  List,
  Type,
} from 'lucide-react';
import type {
  ButirTemplatDaftarPeriksa,
  TemplatDaftarPeriksa,
  TipeJawabanDaftarPeriksa,
} from '@/features/PreventifInspeksi/types';
import { ruteDaftarPeriksa } from '@/features/DaftarPeriksa/api';

interface Props {
  templat: TemplatDaftarPeriksa;
  kategoriAset: { Id: string; Nama: string }[];
  modelAset: { Id: string; Nama: string; KategoriAsetId?: string }[];
}

export default function ShowTemplat({ templat, kategoriAset }: Props) {
  const [bukaDialogButir, setBukaDialogButir] = useState(false);
  const [butirDiedit, setButirDiedit] = useState<ButirTemplatDaftarPeriksa | null>(null);

  const formButir = useForm({
    Pertanyaan: '',
    Kode: '',
    TipeJawaban: 'YaTidak' as TipeJawabanDaftarPeriksa,
    Satuan: '',
    Wajib: true,
    NilaiMinimum: '',
    NilaiMaksimum: '',
    PilihanTeks: '',
    BuktiFotoWajib: false,
    PemicuNilai: 'Tidak',
  });

  const bukaTambahButir = () => {
    setButirDiedit(null);
    formButir.reset();
    formButir.setData({
      Pertanyaan: '',
      Kode: '',
      TipeJawaban: 'YaTidak',
      Satuan: '',
      Wajib: true,
      NilaiMinimum: '',
      NilaiMaksimum: '',
      PilihanTeks: '',
      BuktiFotoWajib: false,
      PemicuNilai: 'Tidak',
    });
    setBukaDialogButir(true);
  };

  const bukaEditButir = (b: ButirTemplatDaftarPeriksa) => {
    setButirDiedit(b);
    formButir.setData({
      Pertanyaan: b.Pertanyaan,
      Kode: b.Kode || '',
      TipeJawaban: b.TipeJawaban,
      Satuan: b.Satuan || '',
      Wajib: b.Wajib,
      NilaiMinimum: b.NilaiMinimum !== null && b.NilaiMinimum !== undefined ? String(b.NilaiMinimum) : '',
      NilaiMaksimum: b.NilaiMaksimum !== null && b.NilaiMaksimum !== undefined ? String(b.NilaiMaksimum) : '',
      PilihanTeks: Array.isArray(b.Pilihan) ? b.Pilihan.join(', ') : '',
      BuktiFotoWajib: b.BuktiFotoWajib,
      PemicuNilai: (b.MemicuTemuanJika?.nilai as string) || 'Tidak',
    });
    setBukaDialogButir(true);
  };

  const simpanButir = (e: FormEvent) => {
    e.preventDefault();

    const payload: Record<string, any> = {
      Pertanyaan: formButir.data.Pertanyaan,
      Kode: formButir.data.Kode || null,
      TipeJawaban: formButir.data.TipeJawaban,
      Satuan: formButir.data.Satuan || null,
      Wajib: formButir.data.Wajib,
      BuktiFotoWajib: formButir.data.BuktiFotoWajib,
    };

    if (formButir.data.TipeJawaban === 'Angka') {
      payload.NilaiMinimum = formButir.data.NilaiMinimum ? Number(formButir.data.NilaiMinimum) : null;
      payload.NilaiMaksimum = formButir.data.NilaiMaksimum ? Number(formButir.data.NilaiMaksimum) : null;
    }

    if (formButir.data.TipeJawaban === 'Pilihan' && formButir.data.PilihanTeks) {
      payload.Pilihan = formButir.data.PilihanTeks.split(',')
        .map((s) => s.trim())
        .filter(Boolean);
    }

    if (formButir.data.PemicuNilai) {
      payload.MemicuTemuanJika = { nilai: formButir.data.PemicuNilai };
    }

    if (butirDiedit) {
      router.put(ruteDaftarPeriksa.butirDetail(templat.Id, butirDiedit.Id), payload, {
        onSuccess: () => setBukaDialogButir(false),
      });
    } else {
      router.post(ruteDaftarPeriksa.butir(templat.Id), payload, {
        onSuccess: () => setBukaDialogButir(false),
      });
    }
  };

  const hapusButir = (butirId: string) => {
    if (confirm('Apakah Anda yakin ingin menghapus butir pertanyaan ini?')) {
      router.delete(ruteDaftarPeriksa.butirDetail(templat.Id, butirId));
    }
  };

  const buatVersiBaru = () => {
    if (confirm(`Buat versi baru dari templat "${templat.Nama}"? Versi saat ini akan diarsipkan.`)) {
      router.post(ruteDaftarPeriksa.versiBaru(templat.Id));
    }
  };

  return (
    <AppLayout>
      <Head title={`Builder: ${templat.Nama}`} />

      <div className="space-y-6">
        {/* Breadcrumb & Navigation */}
        <div className="flex items-center gap-2 text-sm text-permukaan-500">
          <Link
            href={ruteDaftarPeriksa.index}
            className="hover:text-permukaan-700 flex items-center gap-1 cursor-pointer"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Templat</span>
          </Link>
        </div>

        {/* Header Kartu Templat */}
        <div className="bg-card border border-permukaan-200 rounded-xl p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <div className="flex items-center gap-2.5">
                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-permukaan-700 border border-permukaan-300">
                  {templat.Kode}
                </span>
                <Badge variant="outline" className="bg-sky-50 text-sky-700 border-sky-200">
                  Versi {templat.VersiTemplat}
                </Badge>
                <Badge
                  variant={templat.Aktif ? 'default' : 'secondary'}
                  className={templat.Aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ''}
                >
                  {templat.Aktif ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>

              <h1 className="text-xl font-bold text-permukaan-900 mt-2">{templat.Nama}</h1>
              <p className="text-sm text-permukaan-500 mt-1">
                Kategori Aset: {templat.kategoriAset?.Nama ?? 'Semua Kategori'} • Jenis: {templat.Jenis}
              </p>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" className="cursor-pointer gap-1.5" onClick={buatVersiBaru}>
                <Copy className="h-4 w-4" />
                Buat Versi Baru
              </Button>
              <Button
                className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2"
                onClick={bukaTambahButir}
              >
                <Plus className="h-4 w-4" />
                Tambah Pertanyaan
              </Button>
            </div>
          </div>
        </div>

        {/* Daftar Butir Pertanyaan */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-permukaan-900">
              Daftar Butir Pemeriksaan ({templat.butir?.length ?? 0})
            </h2>
            <span className="text-xs text-permukaan-500">
              Pertanyaan akan ditampilkan secara berurutan pada lembar periksa teknisi.
            </span>
          </div>

          {!templat.butir || templat.butir.length === 0 ? (
            <div className="bg-card border border-dashed border-permukaan-300 rounded-xl p-8 text-center">
              <p className="text-permukaan-500 text-sm">Belum ada butir pertanyaan pada templat ini.</p>
              <Button
                className="mt-3 cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white text-xs gap-1.5"
                onClick={bukaTambahButir}
              >
                <Plus className="h-3.5 w-3.5" />
                Tambahkan Pertanyaan Pertama
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {templat.butir.map((b, idx) => (
                <div
                  key={b.Id}
                  className="bg-card border border-permukaan-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-permukaan-300 transition-colors"
                >
                  <div className="flex items-start gap-3">
                    <span className="flex-shrink-0 w-7 h-7 rounded-full bg-permukaan-100 text-permukaan-700 text-xs font-bold flex items-center justify-center mt-0.5">
                      {idx + 1}
                    </span>

                    <div className="space-y-1">
                      <div className="flex items-center gap-2 flex-wrap">
                        {b.Kode && (
                          <span className="font-mono text-xs font-medium text-permukaan-500">[{b.Kode}]</span>
                        )}
                        <span className="font-medium text-permukaan-900 text-sm">{b.Pertanyaan}</span>
                        {b.Wajib && (
                          <Badge
                            variant="destructive"
                            className="text-[10px] px-1.5 py-0 bg-rose-50 text-rose-600 border border-rose-200"
                          >
                            Wajib
                          </Badge>
                        )}
                        {b.BuktiFotoWajib && (
                          <Badge
                            variant="outline"
                            className="text-[10px] px-1.5 py-0 bg-purple-50 text-purple-700 border-purple-200 gap-1"
                          >
                            <Camera className="h-2.5 w-2.5" /> Foto Wajib
                          </Badge>
                        )}
                      </div>

                      <div className="flex items-center gap-3 text-xs text-permukaan-500 flex-wrap">
                        <span className="inline-flex items-center gap-1">
                          {b.TipeJawaban === 'Angka' && <Hash className="h-3.5 w-3.5 text-blue-500" />}
                          {b.TipeJawaban === 'YaTidak' && (
                            <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />
                          )}
                          {b.TipeJawaban === 'Pilihan' && <List className="h-3.5 w-3.5 text-amber-500" />}
                          {b.TipeJawaban === 'Teks' && <Type className="h-3.5 w-3.5 text-permukaan-500" />}
                          Tipe: <strong className="text-permukaan-700">{b.TipeJawaban}</strong>
                        </span>

                        {b.TipeJawaban === 'Angka' && (
                          <span>
                            Rentang: <strong>{b.NilaiMinimum ?? '∞'}</strong> s/d{' '}
                            <strong>{b.NilaiMaksimum ?? '∞'}</strong> {b.Satuan || ''}
                          </span>
                        )}

                        {b.TipeJawaban === 'Pilihan' && b.Pilihan && (
                          <span>Pilihan: {b.Pilihan.join(' | ')}</span>
                        )}

                        {b.MemicuTemuanJika?.nilai !== undefined && (
                          <span className="text-amber-600 font-medium">
                            Pemicu Temuan: "{String(b.MemicuTemuanJika.nilai)}"
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 self-end sm:self-center">
                    <Button
                      variant="outline"
                      size="sm"
                      className="cursor-pointer h-8 px-2.5 gap-1 text-xs"
                      onClick={() => bukaEditButir(b)}
                    >
                      <Edit2 className="h-3.5 w-3.5" />
                      Edit
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      className="cursor-pointer h-8 px-2.5 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200"
                      onClick={() => hapusButir(b.Id)}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Modal Tambah / Edit Butir */}
      <Dialog open={bukaDialogButir} onOpenChange={setBukaDialogButir}>
        <DialogContent className="sm:max-w-lg">
          <form onSubmit={simpanButir}>
            <DialogHeader>
              <DialogTitle>{butirDiedit ? 'Edit Butir Pertanyaan' : 'Tambah Butir Pertanyaan'}</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-1">
              <div className="space-y-1.5">
                <Label htmlFor="Pertanyaan">
                  Pertanyaan / Parameter Pemeriksaan <span className="text-rose-500">*</span>
                </Label>
                <Textarea
                  id="Pertanyaan"
                  placeholder="Misal: Periksa kebocoran oli pada seal motor"
                  value={formButir.data.Pertanyaan}
                  onChange={(e) => formButir.setData('Pertanyaan', e.target.value)}
                  rows={2}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="Kode">Kode Parameter (Opsional)</Label>
                  <Input
                    id="Kode"
                    placeholder="Misal: P1"
                    value={formButir.data.Kode}
                    onChange={(e) => formButir.setData('Kode', e.target.value)}
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="TipeJawaban">Tipe Jawaban</Label>
                  <Select
                    value={formButir.data.TipeJawaban}
                    onValueChange={(val) => formButir.setData('TipeJawaban', val as TipeJawabanDaftarPeriksa)}
                  >
                    <SelectTrigger id="TipeJawaban" className="cursor-pointer">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="YaTidak">Ya / Tidak (Kesesuaian)</SelectItem>
                      <SelectItem value="Angka">Angka (Nilai Terukur)</SelectItem>
                      <SelectItem value="Pilihan">Pilihan Ganda (Dropdown)</SelectItem>
                      <SelectItem value="Teks">Teks Bebas</SelectItem>
                      <SelectItem value="Foto">Foto Dokumentasi</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {formButir.data.TipeJawaban === 'Angka' && (
                <div className="grid grid-cols-3 gap-3 p-3 bg-permukaan-50 rounded-lg border border-permukaan-200">
                  <div className="space-y-1">
                    <Label htmlFor="NilaiMinimum">Nilai Minimum</Label>
                    <Input
                      id="NilaiMinimum"
                      type="number"
                      step="any"
                      placeholder="Min"
                      value={formButir.data.NilaiMinimum}
                      onChange={(e) => formButir.setData('NilaiMinimum', e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="NilaiMaksimum">Nilai Maksimum</Label>
                    <Input
                      id="NilaiMaksimum"
                      type="number"
                      step="any"
                      placeholder="Max"
                      value={formButir.data.NilaiMaksimum}
                      onChange={(e) => formButir.setData('NilaiMaksimum', e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="Satuan">Satuan</Label>
                    <Input
                      id="Satuan"
                      placeholder="bar, °C, dll"
                      value={formButir.data.Satuan}
                      onChange={(e) => formButir.setData('Satuan', e.target.value)}
                    />
                  </div>
                </div>
              )}

              {formButir.data.TipeJawaban === 'Pilihan' && (
                <div className="space-y-1.5 p-3 bg-permukaan-50 rounded-lg border border-permukaan-200">
                  <Label htmlFor="PilihanTeks">Daftar Pilihan (Pisahkan dengan koma)</Label>
                  <Input
                    id="PilihanTeks"
                    placeholder="Normal, Aus Ringan, Rusak Berat"
                    value={formButir.data.PilihanTeks}
                    onChange={(e) => formButir.setData('PilihanTeks', e.target.value)}
                  />
                </div>
              )}

              {formButir.data.TipeJawaban === 'YaTidak' && (
                <div className="space-y-1.5 p-3 bg-permukaan-50 rounded-lg border border-permukaan-200">
                  <Label htmlFor="PemicuNilai">Nilai yang Memicu Temuan / Ketidaksesuaian</Label>
                  <Select
                    value={formButir.data.PemicuNilai}
                    onValueChange={(val) => formButir.setData('PemicuNilai', val)}
                  >
                    <SelectTrigger id="PemicuNilai" className="cursor-pointer">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Tidak">Jawaban "Tidak" memicu temuan</SelectItem>
                      <SelectItem value="Ya">Jawaban "Ya" memicu temuan</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              )}

              <div className="flex items-center gap-6 pt-2">
                <label className="flex items-center gap-2 cursor-pointer text-sm">
                  <input
                    type="checkbox"
                    className="rounded border-permukaan-300 text-teknisi-600 focus:ring-teknisi-500 cursor-pointer"
                    checked={formButir.data.Wajib}
                    onChange={(e) => formButir.setData('Wajib', e.target.checked)}
                  />
                  <span>Pertanyaan Wajib Dijawab</span>
                </label>

                <label className="flex items-center gap-2 cursor-pointer text-sm">
                  <input
                    type="checkbox"
                    className="rounded border-permukaan-300 text-teknisi-600 focus:ring-teknisi-500 cursor-pointer"
                    checked={formButir.data.BuktiFotoWajib}
                    onChange={(e) => formButir.setData('BuktiFotoWajib', e.target.checked)}
                  />
                  <span>Wajib Unggah Foto</span>
                </label>
              </div>
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                className="cursor-pointer"
                onClick={() => setBukaDialogButir(false)}
              >
                Batal
              </Button>
              <Button
                type="submit"
                className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                disabled={formButir.processing}
              >
                {formButir.processing ? 'Menyimpan...' : 'Simpan Butir'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
