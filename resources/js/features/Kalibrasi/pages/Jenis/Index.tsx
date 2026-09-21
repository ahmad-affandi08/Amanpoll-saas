import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
  Gauge,
  Plus,
  Pencil,
  Trash2,
  Sliders,
  CheckCircle2,
  XCircle,
  Search,
  ListOrdered,
} from 'lucide-react';
import type { JenisKalibrasi, TitikUkurKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  jenisKalibrasi: JenisKalibrasi[];
}

export default function JenisKalibrasiIndex({ jenisKalibrasi }: Props) {
  const konfirmasi = useKonfirmasi();
  const [pencarian, setPencarian] = useState('');
  const [bukaDialogJenis, setBukaDialogJenis] = useState(false);
  const [jenisDiedit, setJenisDiedit] = useState<JenisKalibrasi | null>(null);

  // Dialog Titik Ukur
  const [bukaDialogTitik, setBukaDialogTitik] = useState(false);
  const [jenisAktifTitik, setJenisAktifTitik] = useState<JenisKalibrasi | null>(null);
  const [titikDiedit, setTitikDiedit] = useState<TitikUkurKalibrasi | null>(null);

  // Form Jenis Kalibrasi
  const formJenis = useForm({
    Kode: '',
    Nama: '',
    Deskripsi: '',
    Aktif: true,
  });

  // Form Titik Ukur
  const formTitik = useForm({
    Nama: '',
    Satuan: '',
    NilaiReferensi: '' as string | number,
    ToleransiMinus: '0' as string | number,
    ToleransiPlus: '0' as string | number,
    Urutan: 1,
    Aktif: true,
  });

  const bukaModalTambahJenis = () => {
    setJenisDiedit(null);
    formJenis.reset();
    formJenis.setData({
      Kode: '',
      Nama: '',
      Deskripsi: '',
      Aktif: true,
    });
    setBukaDialogJenis(true);
  };

  const bukaModalEditJenis = (jenis: JenisKalibrasi) => {
    setJenisDiedit(jenis);
    formJenis.setData({
      Kode: jenis.Kode,
      Nama: jenis.Nama,
      Deskripsi: jenis.Deskripsi ?? '',
      Aktif: jenis.Aktif,
    });
    setBukaDialogJenis(true);
  };

  const simpanJenis = (e: FormEvent) => {
    e.preventDefault();
    if (jenisDiedit) {
      formJenis.put(ruteKalibrasi.jenisDetail(jenisDiedit.Id), {
        onSuccess: () => {
          setBukaDialogJenis(false);
          formJenis.reset();
        },
      });
    } else {
      formJenis.post(ruteKalibrasi.jenis, {
        onSuccess: () => {
          setBukaDialogJenis(false);
          formJenis.reset();
        },
      });
    }
  };

  const hapusJenis = async (jenis: JenisKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus jenis kalibrasi "${jenis.Nama}"?`,
        deskripsi: 'Jenis yang masih dipakai rencana atau pelaksanaan kalibrasi tidak dapat dihapus.',
        ragam: 'bahaya',
      })
    ) {
      formJenis.delete(ruteKalibrasi.jenisDetail(jenis.Id));
    }
  };

  // Manajemen Titik Ukur
  const kelolaTitikUkur = (jenis: JenisKalibrasi) => {
    setJenisAktifTitik(jenis);
    setTitikDiedit(null);
    formTitik.reset();
    formTitik.setData({
      Nama: '',
      Satuan: '',
      NilaiReferensi: '',
      ToleransiMinus: '0',
      ToleransiPlus: '0',
      Urutan: (jenis.titikUkur?.length ?? 0) + 1,
      Aktif: true,
    });
    setBukaDialogTitik(true);
  };

  const bukaEditTitik = (titik: TitikUkurKalibrasi) => {
    setTitikDiedit(titik);
    formTitik.setData({
      Nama: titik.Nama,
      Satuan: titik.Satuan ?? '',
      NilaiReferensi: titik.NilaiReferensi ?? '',
      ToleransiMinus: titik.ToleransiMinus ?? '0',
      ToleransiPlus: titik.ToleransiPlus ?? '0',
      Urutan: titik.Urutan,
      Aktif: titik.Aktif,
    });
  };

  const simpanTitik = (e: FormEvent) => {
    e.preventDefault();
    if (!jenisAktifTitik) return;

    if (titikDiedit) {
      formTitik.put(ruteKalibrasi.titikUkurDetail(titikDiedit.Id), {
        onSuccess: () => {
          setTitikDiedit(null);
          formTitik.reset();
        },
      });
    } else {
      formTitik.post(ruteKalibrasi.jenisTitikUkur(jenisAktifTitik.Id), {
        onSuccess: () => {
          formTitik.reset();
          formTitik.setData({
            Nama: '',
            Satuan: '',
            NilaiReferensi: '',
            ToleransiMinus: '0',
            ToleransiPlus: '0',
            Urutan: (jenisAktifTitik.titikUkur?.length ?? 0) + 2,
            Aktif: true,
          });
        },
      });
    }
  };

  const hapusTitik = async (titik: TitikUkurKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus titik ukur standar "${titik.Nama}"?`,
        deskripsi: 'Titik ukur ini tidak lagi muncul pada pelaksanaan kalibrasi berikutnya.',
        ragam: 'bahaya',
      })
    ) {
      formTitik.delete(ruteKalibrasi.titikUkurDetail(titik.Id));
    }
  };

  const filteredJenis = jenisKalibrasi.filter(
    (jk) =>
      jk.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
      jk.Kode.toLowerCase().includes(pencarian.toLowerCase()) ||
      (jk.Deskripsi ?? '').toLowerCase().includes(pencarian.toLowerCase()),
  );

  return (
    <AppLayout>
      <Head title="Jenis Kalibrasi & Titik Ukur Standar" />

      <div className="space-y-6">
        {/* Header */}
        <PageHeader
          judul="Jenis Kalibrasi"
          deskripsi="Atur metode, spesifikasi unit, dan template titik ukur standar untuk instrumen dan alat uji."
          aksi={
            <>
              <Button onClick={bukaModalTambahJenis} size="sm">
                <Plus className="mr-1.5 size-4" />
                Tambah Jenis Kalibrasi
              </Button>
            </>
          }
        />

        {/* List Card */}
        <Card className="border-border">
          <CardHeader className="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div className="relative w-full sm:w-72">
              <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
              <Input
                placeholder="Cari kode atau metode kalibrasi..."
                value={pencarian}
                onChange={(e) => setPencarian(e.target.value)}
                className="pl-8 h-9 text-xs"
              />
            </div>
            <span className="text-xs text-muted-foreground">Menampilkan {filteredJenis.length} jenis</span>
          </CardHeader>

          <CardContent className="p-0">
            {filteredJenis.length === 0 ? (
              <div className="py-12">
                <EmptyState
                  judul="Belum ada jenis kalibrasi."
                  deskripsi="Tambahkan jenis kalibrasi seperti Kalibrasi Suhu, Tekanan, Dimensi, atau Listrik."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-muted-foreground border-b border-border">
                    <tr>
                      <th className="px-4 py-3 font-medium">Kode</th>
                      <th className="px-4 py-3 font-medium">Nama Metode / Jenis</th>
                      <th className="px-4 py-3 font-medium">Deskripsi & Standar Acuan</th>
                      <th className="px-3 py-3 font-medium text-center">Titik Ukur Default</th>
                      <th className="px-3 py-3 font-medium text-center">Status</th>
                      <th className="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {filteredJenis.map((jk) => {
                      const jumlahTitik = jk.titikUkur?.length ?? 0;
                      return (
                        <tr key={jk.Id} className="hover:bg-permukaan-50 transition-colors">
                          <td className="px-4 py-3 font-mono font-semibold text-foreground whitespace-nowrap">
                            {jk.Kode}
                          </td>
                          <td className="px-4 py-3 font-medium text-foreground">{jk.Nama}</td>
                          <td className="px-4 py-3 text-muted-foreground max-w-xs truncate">
                            {jk.Deskripsi || (
                              <span className="text-muted-foreground italic">Tidak ada deskripsi</span>
                            )}
                          </td>
                          <td className="px-3 py-3 text-center whitespace-nowrap">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => kelolaTitikUkur(jk)}
                              className="h-7 text-xs gap-1.5"
                            >
                              <Sliders className="size-3.5 text-teknisi-700" />
                              {jumlahTitik} Titik Standar
                            </Button>
                          </td>
                          <td className="px-3 py-3 text-center whitespace-nowrap">
                            {jk.Aktif ? (
                              <Badge
                                variant="outline"
                                className="bg-emerald-50 text-emerald-700 border-emerald-200"
                              >
                                Aktif
                              </Badge>
                            ) : (
                              <Badge variant="outline" className="bg-zinc-100 text-zinc-600 border-zinc-200">
                                Nonaktif
                              </Badge>
                            )}
                          </td>
                          <td className="px-4 py-3 text-right whitespace-nowrap">
                            <div className="flex items-center justify-end gap-1">
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => bukaModalEditJenis(jk)}
                                className="h-7 w-7 text-muted-foreground hover:text-foreground"
                              >
                                <Pencil className="size-3.5" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => hapusJenis(jk)}
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

      {/* Dialog Form Jenis Kalibrasi */}
      <Dialog open={bukaDialogJenis} onOpenChange={setBukaDialogJenis}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{jenisDiedit ? 'Edit Jenis Kalibrasi' : 'Tambah Jenis Kalibrasi'}</DialogTitle>
            <DialogDescription>
              Tentukan kode unik, nama klasifikasi kalibrasi, dan metode atau unit pengukuran acuan.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={simpanJenis} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="Kode">Kode Jenis *</Label>
              <Input
                id="Kode"
                placeholder="mis. CAL-TEMP, CAL-PRESS"
                value={formJenis.data.Kode}
                onChange={(e) => formJenis.setData('Kode', e.target.value)}
                required
              />
              {formJenis.errors.Kode && <p className="text-xs text-rose-600">{formJenis.errors.Kode}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="Nama">Nama Jenis Kalibrasi *</Label>
              <Input
                id="Nama"
                placeholder="mis. Kalibrasi Suhu dan Thermocouple"
                value={formJenis.data.Nama}
                onChange={(e) => formJenis.setData('Nama', e.target.value)}
                required
              />
              {formJenis.errors.Nama && <p className="text-xs text-rose-600">{formJenis.errors.Nama}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="Deskripsi">Deskripsi & Standar Acuan Metrologi</Label>
              <Textarea
                id="Deskripsi"
                placeholder="mis. Acuan SNI/ISO 17025. Satuan acuan Celsius (°C), rentang 0 - 500 °C."
                rows={3}
                value={formJenis.data.Deskripsi}
                onChange={(e) => formJenis.setData('Deskripsi', e.target.value)}
              />
            </div>

            <div className="flex items-center justify-between p-2.5 rounded-lg border border-border">
              <div className="space-y-0.5">
                <Label htmlFor="Aktif">Status Aktif</Label>
                <p className="text-xs text-zinc-500">
                  Jenis ini dapat dipilih saat membuat rencana kalibrasi baru.
                </p>
              </div>
              <Switch
                id="Aktif"
                checked={formJenis.data.Aktif}
                onCheckedChange={(checked) => formJenis.setData('Aktif', checked)}
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setBukaDialogJenis(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={formJenis.processing}>
                {formJenis.processing ? 'Menyimpan...' : 'Simpan'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Dialog Titik Ukur Standar */}
      <Dialog open={bukaDialogTitik} onOpenChange={setBukaDialogTitik}>
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Sliders className="h-5 w-5 text-primary" />
              Titik Ukur Standar: {jenisAktifTitik?.Nama}
            </DialogTitle>
            <DialogDescription>
              Definisikan titik uji acuan, toleransi deviasi plus/minus, dan satuan yang akan otomatis disalin
              saat kalibrasi dijadwalkan.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-6 pt-2">
            {/* Form Input Titik Ukur */}
            <form
              onSubmit={simpanTitik}
              className="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-border space-y-3"
            >
              <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                <span>{titikDiedit ? 'Edit Titik Ukur' : 'Tambah Titik Ukur Baru'}</span>
                {titikDiedit && (
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setTitikDiedit(null);
                      formTitik.reset();
                    }}
                    className="h-6 text-[11px] text-zinc-500"
                  >
                    Batal Edit
                  </Button>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="space-y-1">
                  <Label htmlFor="TitikNama" className="text-xs">
                    Nama Titik Uji *
                  </Label>
                  <Input
                    id="TitikNama"
                    placeholder="mis. Suhu Titik Didih Air"
                    value={formTitik.data.Nama}
                    onChange={(e) => formTitik.setData('Nama', e.target.value)}
                    required
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="TitikSatuan" className="text-xs">
                    Satuan
                  </Label>
                  <Input
                    id="TitikSatuan"
                    placeholder="mis. °C, bar, psi, mm, V"
                    value={formTitik.data.Satuan}
                    onChange={(e) => formTitik.setData('Satuan', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div className="space-y-1">
                  <Label htmlFor="NilaiReferensi" className="text-xs">
                    Nilai Referensi
                  </Label>
                  <Input
                    id="NilaiReferensi"
                    type="number"
                    step="any"
                    placeholder="100"
                    value={formTitik.data.NilaiReferensi}
                    onChange={(e) => formTitik.setData('NilaiReferensi', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="ToleransiMinus" className="text-xs">
                    Toleransi (-) *
                  </Label>
                  <Input
                    id="ToleransiMinus"
                    type="number"
                    step="any"
                    placeholder="0.5"
                    value={formTitik.data.ToleransiMinus}
                    onChange={(e) => formTitik.setData('ToleransiMinus', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="ToleransiPlus" className="text-xs">
                    Toleransi (+) *
                  </Label>
                  <Input
                    id="ToleransiPlus"
                    type="number"
                    step="any"
                    placeholder="0.5"
                    value={formTitik.data.ToleransiPlus}
                    onChange={(e) => formTitik.setData('ToleransiPlus', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="TitikUrutan" className="text-xs">
                    Urutan
                  </Label>
                  <Input
                    id="TitikUrutan"
                    type="number"
                    value={formTitik.data.Urutan}
                    onChange={(e) => formTitik.setData('Urutan', Number(e.target.value))}
                    className="h-8 text-xs"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-1">
                <Button type="submit" size="sm" className="h-8 text-xs gap-1" disabled={formTitik.processing}>
                  <Plus className="h-3.5 w-3.5" />
                  {titikDiedit ? 'Perbarui Titik' : 'Tambahkan ke Daftar'}
                </Button>
              </div>
            </form>

            {/* List Titik Ukur yang Ada */}
            <div className="space-y-2">
              <h4 className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Daftar Titik Ukur Terdaftar ({jenisAktifTitik?.titikUkur?.length ?? 0})
              </h4>

              {(jenisAktifTitik?.titikUkur?.length ?? 0) === 0 ? (
                <div className="p-4 border border-dashed rounded-lg text-center text-xs text-zinc-500">
                  Belum ada titik ukur standar untuk jenis kalibrasi ini.
                </div>
              ) : (
                <div className="border border-border rounded-lg overflow-hidden">
                  <table className="w-full text-xs text-left">
                    <thead className="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-500 border-b border-border">
                      <tr>
                        <th className="px-3 py-2 w-10 text-center">#</th>
                        <th className="px-3 py-2">Nama Titik</th>
                        <th className="px-3 py-2">Referensi</th>
                        <th className="px-3 py-2">Batas Toleransi</th>
                        <th className="px-3 py-2">Satuan</th>
                        <th className="px-3 py-2 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                      {jenisAktifTitik?.titikUkur?.map((tu, idx) => (
                        <tr key={tu.Id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-900/30">
                          <td className="px-3 py-2 text-center text-zinc-400 font-mono">
                            {tu.Urutan ?? idx + 1}
                          </td>
                          <td className="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">
                            {tu.Nama}
                          </td>
                          <td className="px-3 py-2 text-zinc-700 dark:text-zinc-300">
                            {tu.NilaiReferensi ?? '-'}
                          </td>
                          <td className="px-3 py-2 font-mono text-[11px] text-zinc-600 dark:text-zinc-400">
                            -{tu.ToleransiMinus ?? 0} / +{tu.ToleransiPlus ?? 0}
                          </td>
                          <td className="px-3 py-2 text-zinc-600 dark:text-zinc-400">{tu.Satuan ?? '-'}</td>
                          <td className="px-3 py-2 text-right">
                            <div className="flex items-center justify-end gap-1">
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => bukaEditTitik(tu)}
                                className="h-6 w-6 text-zinc-400 hover:text-zinc-900"
                              >
                                <Pencil className="h-3 w-3" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => hapusTitik(tu)}
                                className="h-6 w-6 text-rose-500 hover:text-rose-700"
                              >
                                <Trash2 className="h-3 w-3" />
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>

          <DialogFooter className="pt-2">
            <Button variant="outline" onClick={() => setBukaDialogTitik(false)}>
              Tutup
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
