import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Sliders } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import type { JenisKalibrasi, TitikUkurKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogTitikUkur({ jenis, wajib }: { jenis: JenisKalibrasi; wajib: AturanWajib }) {
  const konfirmasi = useKonfirmasi();
  const [buka, setBuka] = useState(false);
  const [titikDiedit, setTitikDiedit] = useState<TitikUkurKalibrasi | null>(null);
  const titikUkur = jenis.titikUkur ?? [];

  const form = useForm({
    Nama: '',
    Satuan: '',
    NilaiReferensi: '' as string | number,
    ToleransiMinus: '0' as string | number,
    ToleransiPlus: '0' as string | number,
    Urutan: titikUkur.length + 1,
    Aktif: true,
  });

  const kosongkan = (urutan: number) => {
    setTitikDiedit(null);
    form.setData({
      Nama: '',
      Satuan: '',
      NilaiReferensi: '',
      ToleransiMinus: '0',
      ToleransiPlus: '0',
      Urutan: urutan,
      Aktif: true,
    });
  };

  /** useForm mengunci nilai saat mount, jadi isinya disegarkan tiap kali dialog dibuka. */
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      kosongkan(titikUkur.length + 1);
      form.clearErrors();
    }
    setBuka(terbuka);
  };

  const mulaiEdit = (titik: TitikUkurKalibrasi) => {
    setTitikDiedit(titik);
    form.setData({
      Nama: titik.Nama,
      Satuan: titik.Satuan ?? '',
      NilaiReferensi: titik.NilaiReferensi ?? '',
      ToleransiMinus: titik.ToleransiMinus ?? '0',
      ToleransiPlus: titik.ToleransiPlus ?? '0',
      Urutan: titik.Urutan,
      Aktif: titik.Aktif,
    });
  };

  const simpan = (e: FormEvent) => {
    e.preventDefault();

    if (titikDiedit) {
      form.put(ruteKalibrasi.titikUkurDetail(titikDiedit.Id), {
        preserveScroll: true,
        onSuccess: () => kosongkan(titikUkur.length + 1),
      });
      return;
    }

    const berikutnya = Number(form.data.Urutan) + 1;
    form.post(ruteKalibrasi.jenisTitikUkur(jenis.Id), {
      preserveScroll: true,
      onSuccess: () => kosongkan(berikutnya),
    });
  };

  const hapus = async (titik: TitikUkurKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus titik ukur standar "${titik.Nama}"?`,
        deskripsi: 'Titik ukur ini tidak lagi muncul pada pelaksanaan kalibrasi berikutnya.',
        ragam: 'bahaya',
      })
    ) {
      form.delete(ruteKalibrasi.titikUkurDetail(titik.Id), { preserveScroll: true });
    }
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" className="h-7 text-xs gap-1.5">
          <Sliders className="size-3.5 text-teknisi-700" />
          {titikUkur.length} Titik Standar
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Sliders className="h-5 w-5 text-primary" />
            Titik Ukur Standar: {jenis.Nama}
          </DialogTitle>
          <DialogDescription>
            Definisikan titik uji acuan, toleransi deviasi plus/minus, dan satuan yang akan otomatis disalin
            saat kalibrasi dijadwalkan.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 pt-2">
          <AturanWajibProvider aturan={wajib}>
            <form onSubmit={simpan} className="p-4 rounded-md bg-permukaan-50 border border-border space-y-3">
              <div className="font-semibold text-xs text-grafit-950 flex items-center justify-between">
                <span>{titikDiedit ? 'Edit Titik Ukur' : 'Tambah Titik Ukur Baru'}</span>
                {titikDiedit && (
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => kosongkan(titikUkur.length + 1)}
                    className="h-6 text-[11px] text-grafit-500"
                  >
                    Batal Edit
                  </Button>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="space-y-1">
                  <Label nama="TitikNama" htmlFor="TitikNama" className="text-xs">
                    Nama Titik Uji *
                  </Label>
                  <Input
                    id="TitikNama"
                    placeholder="mis. Suhu Titik Didih Air"
                    value={form.data.Nama}
                    onChange={(e) => form.setData('Nama', e.target.value)}
                    required
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label nama="TitikSatuan" htmlFor="TitikSatuan" className="text-xs">
                    Satuan
                  </Label>
                  <Input
                    id="TitikSatuan"
                    placeholder="mis. °C, bar, psi, mm, V"
                    value={form.data.Satuan}
                    onChange={(e) => form.setData('Satuan', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div className="space-y-1">
                  <Label nama="NilaiReferensi" htmlFor="NilaiReferensi" className="text-xs">
                    Nilai Referensi
                  </Label>
                  <Input
                    id="NilaiReferensi"
                    type="number"
                    step="any"
                    placeholder="100"
                    value={form.data.NilaiReferensi}
                    onChange={(e) => form.setData('NilaiReferensi', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label nama="ToleransiMinus" htmlFor="ToleransiMinus" className="text-xs">
                    Toleransi (-) *
                  </Label>
                  <Input
                    id="ToleransiMinus"
                    type="number"
                    step="any"
                    placeholder="0.5"
                    value={form.data.ToleransiMinus}
                    onChange={(e) => form.setData('ToleransiMinus', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label nama="ToleransiPlus" htmlFor="ToleransiPlus" className="text-xs">
                    Toleransi (+) *
                  </Label>
                  <Input
                    id="ToleransiPlus"
                    type="number"
                    step="any"
                    placeholder="0.5"
                    value={form.data.ToleransiPlus}
                    onChange={(e) => form.setData('ToleransiPlus', e.target.value)}
                    className="h-8 text-xs"
                  />
                </div>
                <div className="space-y-1">
                  <Label nama="TitikUrutan" htmlFor="TitikUrutan" className="text-xs">
                    Urutan
                  </Label>
                  <Input
                    id="TitikUrutan"
                    type="number"
                    value={form.data.Urutan}
                    onChange={(e) => form.setData('Urutan', Number(e.target.value))}
                    className="h-8 text-xs"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-1">
                <Button type="submit" size="sm" className="h-8 text-xs gap-1" disabled={form.processing}>
                  <Plus className="h-3.5 w-3.5" />
                  {titikDiedit ? 'Perbarui Titik' : 'Tambahkan ke Daftar'}
                </Button>
              </div>
            </form>
          </AturanWajibProvider>

          <div className="space-y-2">
            <h4 className="text-xs font-semibold text-grafit-700">
              Daftar Titik Ukur Terdaftar ({titikUkur.length})
            </h4>

            {titikUkur.length === 0 ? (
              <div className="p-4 border border-dashed border-border rounded-md text-center text-xs text-grafit-500">
                Belum ada titik ukur standar untuk jenis kalibrasi ini.
              </div>
            ) : (
              <div className="border border-border rounded-md overflow-hidden">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-grafit-500 border-b border-border">
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
                    {titikUkur.map((tu, idx) => (
                      <tr key={tu.Id} className="hover:bg-accent">
                        <td className="px-3 py-2 text-center text-grafit-500 font-mono">
                          {tu.Urutan ?? idx + 1}
                        </td>
                        <td className="px-3 py-2 font-medium text-grafit-950">{tu.Nama}</td>
                        <td className="px-3 py-2 text-grafit-700">{tu.NilaiReferensi ?? '-'}</td>
                        <td className="px-3 py-2 font-mono text-[11px] text-grafit-700">
                          -{tu.ToleransiMinus ?? 0} / +{tu.ToleransiPlus ?? 0}
                        </td>
                        <td className="px-3 py-2 text-grafit-700">{tu.Satuan ?? '-'}</td>
                        <td className="px-3 py-2 text-right">
                          <div className="flex items-center justify-end gap-1">
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => mulaiEdit(tu)}
                              className="sm:size-6 text-grafit-500 hover:text-grafit-950"
                            >
                              <Pencil className="h-3 w-3" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => hapus(tu)}
                              className="sm:size-6 text-destructive hover:text-bahaya-700"
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
          <Button variant="outline" onClick={() => setBuka(false)}>
            Tutup
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
