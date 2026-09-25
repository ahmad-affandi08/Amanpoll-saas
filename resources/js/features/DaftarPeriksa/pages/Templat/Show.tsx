import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, Plus, Trash2, Edit2, Copy, CheckCircle2, Camera, Hash, List, Type } from 'lucide-react';
import type {
  ButirTemplatDaftarPeriksa,
  TemplatDaftarPeriksa,
  TipeJawabanDaftarPeriksa,
} from '@/features/PreventifInspeksi/types';
import { ruteDaftarPeriksa } from '@/features/DaftarPeriksa/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { formatAngkaUkur } from '@/lib/angka';

interface Props {
  templat: TemplatDaftarPeriksa;
  kategoriAset: { Id: string; Nama: string }[];
  modelAset: { Id: string; Nama: string; KategoriAsetId?: string }[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function DaftarPeriksaTemplatShow({ templat, kategoriAset, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
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
      PemicuNilai: [true, 'Ya', 'ya', '1'].includes(b.MemicuTemuanJika?.nilai as string | boolean)
        ? 'Ya'
        : 'Tidak',
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

    // Pemicu hanya bermakna untuk Ya/Tidak; tipe lain dinilai dari batas atau pilihannya sendiri.
    payload.MemicuTemuanJika =
      formButir.data.TipeJawaban === 'YaTidak' ? { nilai: formButir.data.PemicuNilai } : null;

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

  const hapusButir = async (butirId: string) => {
    if (
      await konfirmasi({
        judul: 'Hapus butir pertanyaan ini?',
        deskripsi: 'Pelaksanaan yang sudah berjalan tetap memakai versi templat lamanya.',
        ragam: 'bahaya',
      })
    ) {
      router.delete(ruteDaftarPeriksa.butirDetail(templat.Id, butirId));
    }
  };

  const buatVersiBaru = async () => {
    if (
      await konfirmasi({
        judul: `Buat versi baru dari templat "${templat.Nama}"?`,
        deskripsi: `Versi saat ini akan diarsipkan.`,
        ragam: 'perhatian',
      })
    ) {
      router.post(ruteDaftarPeriksa.versiBaru(templat.Id));
    }
  };

  return (
    <KerangkaAplikasi>
      <Head title={`Builder: ${templat.Nama}`} />
      <BreadcrumbHalaman />

      <div className="space-y-5">
        {/* Breadcrumb & Navigation */}
        <div className="flex items-center gap-2 text-sm text-grafit-500">
          <Link
            href={ruteDaftarPeriksa.index}
            className="inline-flex min-h-11 items-center gap-1 rounded-[5px] underline-offset-4 hover:text-grafit-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Templat</span>
          </Link>
        </div>

        {/* Header Kartu Templat */}
        <div className="rounded-md border border-border bg-card p-5">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <div className="flex items-center gap-2.5">
                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded-sm bg-permukaan-100 text-grafit-700 border border-garis-300">
                  {templat.Kode}
                </span>
                <Badge variant="outline" className="bg-info-600/10 text-info-700 border-info-600/25">
                  Versi {templat.VersiTemplat}
                </Badge>
                <Badge
                  variant={templat.Aktif ? 'default' : 'secondary'}
                  className={templat.Aktif ? 'bg-sukses-50 text-sukses-700 border-sukses-200' : ''}
                >
                  {templat.Aktif ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>

              <h1 className="text-[15px] font-semibold text-foreground mt-2">{templat.Nama}</h1>
              <p className="text-sm text-grafit-500 mt-1">
                Kategori Aset: {templat.kategori_aset?.Nama ?? 'Semua Kategori'} • Jenis: {templat.Jenis}
              </p>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" className="cursor-pointer gap-1.5" onClick={buatVersiBaru}>
                <Copy className="h-4 w-4" />
                Buat Versi Baru
              </Button>
              <Button className="cursor-pointer" onClick={bukaTambahButir}>
                <Plus className="h-4 w-4" />
                Tambah Pertanyaan
              </Button>
            </div>
          </div>
        </div>

        {/* Daftar Butir Pertanyaan */}
        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-sm font-semibold text-foreground">
              Daftar Butir Pemeriksaan ({templat.butir?.length ?? 0})
            </h2>
            <span className="text-xs text-grafit-500">
              Pertanyaan akan ditampilkan secara berurutan pada lembar periksa teknisi.
            </span>
          </div>

          {!templat.butir || templat.butir.length === 0 ? (
            <div className="rounded-md border border-dashed border-garis-300 bg-card p-5 text-center">
              <p className="text-grafit-500 text-sm">Belum ada butir pertanyaan pada templat ini.</p>
              <Button className="mt-3 cursor-pointer" onClick={bukaTambahButir}>
                <Plus className="h-3.5 w-3.5" />
                Tambahkan Pertanyaan Pertama
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {templat.butir.map((b, idx) => (
                <div
                  key={b.Id}
                  className="bg-card border border-border rounded-md p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-garis-300 transition-colors"
                >
                  <div className="flex items-start gap-3">
                    <span className="flex-shrink-0 size-6 rounded-sm bg-permukaan-100 text-grafit-700 text-xs font-medium tabular-nums flex items-center justify-center mt-0.5">
                      {idx + 1}
                    </span>

                    <div className="space-y-1">
                      <div className="flex items-center gap-2 flex-wrap">
                        {b.Kode && (
                          <span className="font-mono text-xs font-medium text-grafit-500">[{b.Kode}]</span>
                        )}
                        <span className="font-medium text-grafit-950 text-sm">{b.Pertanyaan}</span>
                        {b.Wajib && (
                          <Badge
                            variant="destructive"
                            className="text-[10px] px-1.5 py-0 border border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700"
                          >
                            Wajib
                          </Badge>
                        )}
                        {b.BuktiFotoWajib && (
                          <Badge
                            variant="outline"
                            className="text-[10px] px-1.5 py-0 border-info-600/25 bg-info-600/10 text-info-700 gap-1"
                          >
                            <Camera className="h-2.5 w-2.5" /> Foto Wajib
                          </Badge>
                        )}
                      </div>

                      <div className="flex items-center gap-3 text-xs text-grafit-500 flex-wrap">
                        <span className="inline-flex items-center gap-1">
                          {b.TipeJawaban === 'Angka' && <Hash className="h-3.5 w-3.5 text-info-600" />}
                          {b.TipeJawaban === 'YaTidak' && (
                            <CheckCircle2 className="h-3.5 w-3.5 text-sukses-600" />
                          )}
                          {b.TipeJawaban === 'Pilihan' && <List className="h-3.5 w-3.5 text-safety-600" />}
                          {b.TipeJawaban === 'Teks' && <Type className="h-3.5 w-3.5 text-grafit-500" />}
                          Tipe: <strong className="text-grafit-700">{b.TipeJawaban}</strong>
                        </span>

                        {b.TipeJawaban === 'Angka' && (
                          <span>
                            Rentang: <strong>{formatAngkaUkur(b.NilaiMinimum)}</strong> s/d{' '}
                            <strong>{formatAngkaUkur(b.NilaiMaksimum)}</strong> {b.Satuan || ''}
                          </span>
                        )}

                        {b.TipeJawaban === 'Pilihan' && b.Pilihan && (
                          <span>Pilihan: {b.Pilihan.join(' | ')}</span>
                        )}

                        {b.TipeJawaban === 'YaTidak' && (
                          <span className="text-safety-700 font-medium">
                            Temuan bila dijawab "
                            {[true, 'Ya', 'ya', '1'].includes(b.MemicuTemuanJika?.nilai as string | boolean)
                              ? 'Ya'
                              : 'Tidak'}
                            "
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
                      className="cursor-pointer h-8 px-2.5 text-xs text-destructive hover:text-bahaya-700 hover:bg-bahaya-600/10 border-bahaya-600/25"
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
          <AturanWajibProvider aturan={wajib.butir}>
            <form onSubmit={simpanButir}>
              <DialogHeader>
                <DialogTitle>{butirDiedit ? 'Edit Butir Pertanyaan' : 'Tambah Butir Pertanyaan'}</DialogTitle>
              </DialogHeader>

              <div className="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-1">
                <div className="space-y-1.5">
                  <Label nama="Pertanyaan" htmlFor="Pertanyaan">
                    Pertanyaan / Parameter Pemeriksaan <span className="text-destructive">*</span>
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
                    <Label nama="Kode" htmlFor="Kode">
                      Kode Parameter (Opsional)
                    </Label>
                    <Input
                      id="Kode"
                      placeholder="Misal: P1"
                      value={formButir.data.Kode}
                      onChange={(e) => formButir.setData('Kode', e.target.value)}
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label nama="TipeJawaban" htmlFor="TipeJawaban">
                      Tipe Jawaban
                    </Label>
                    <Select
                      value={formButir.data.TipeJawaban}
                      onValueChange={(val) =>
                        formButir.setData('TipeJawaban', val as TipeJawabanDaftarPeriksa)
                      }
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
                  <div className="grid grid-cols-3 gap-3 p-3 bg-permukaan-50 rounded-md border border-border">
                    <div className="space-y-1">
                      <Label nama="NilaiMinimum" htmlFor="NilaiMinimum">
                        Nilai Minimum
                      </Label>
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
                      <Label nama="NilaiMaksimum" htmlFor="NilaiMaksimum">
                        Nilai Maksimum
                      </Label>
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
                      <Label nama="Satuan" htmlFor="Satuan">
                        Satuan
                      </Label>
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
                  <div className="space-y-1.5 p-3 bg-permukaan-50 rounded-md border border-border">
                    <Label nama="PilihanTeks" htmlFor="PilihanTeks">
                      Daftar Pilihan (Pisahkan dengan koma)
                    </Label>
                    <Input
                      id="PilihanTeks"
                      placeholder="Normal, Aus Ringan, Rusak Berat"
                      value={formButir.data.PilihanTeks}
                      onChange={(e) => formButir.setData('PilihanTeks', e.target.value)}
                    />
                  </div>
                )}

                {formButir.data.TipeJawaban === 'YaTidak' && (
                  <div className="space-y-1.5 p-3 bg-permukaan-50 rounded-md border border-border">
                    <Label nama="PemicuNilai" htmlFor="PemicuNilai">
                      Nilai yang Memicu Temuan / Ketidaksesuaian
                    </Label>
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

                <div className="flex flex-wrap items-center gap-x-5 gap-y-2 pt-2">
                  <label className="flex items-center gap-2 cursor-pointer text-sm">
                    <input
                      type="checkbox"
                      className="rounded border-garis-300 text-teknisi-600 focus:ring-teknisi-500 cursor-pointer"
                      checked={formButir.data.Wajib}
                      onChange={(e) => formButir.setData('Wajib', e.target.checked)}
                    />
                    <span>Pertanyaan Wajib Dijawab</span>
                  </label>

                  <label className="flex items-center gap-2 cursor-pointer text-sm">
                    <input
                      type="checkbox"
                      className="rounded border-garis-300 text-teknisi-600 focus:ring-teknisi-500 cursor-pointer"
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
                <Button type="submit" className="cursor-pointer" disabled={formButir.processing}>
                  {formButir.processing ? 'Menyimpan...' : 'Simpan Butir'}
                </Button>
              </DialogFooter>
            </form>
          </AturanWajibProvider>
        </DialogContent>
      </Dialog>
    </KerangkaAplikasi>
  );
}
