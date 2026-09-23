import { useState } from 'react';
import { router } from '@inertiajs/react';
import { AlertTriangle, Plus, Save, Trash2, Check } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { PelaksanaanKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';

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

export function EditorTitikUkur({
  pelaksanaan,
  sudahVerifikasi,
}: {
  pelaksanaan: PelaksanaanKalibrasi;
  sudahVerifikasi: boolean;
}) {
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

  const adaTitikGagal = titikRows.some((r) => r.Hasil === 'Gagal');

  return (
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
                      className={`transition-colors ${isGagal ? 'bg-bahaya-600/5 hover:bg-bahaya-600/10' : 'hover:bg-accent'}`}
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
                              isGagal ? 'border-bahaya-600 bg-bahaya-600/5 text-bahaya-700' : ''
                            }`}
                          />
                        )}
                      </td>

                      <td className="px-3 py-2 font-mono text-foreground">
                        {row.Koreksi !== '' ? (
                          <span className={Number(row.Koreksi) !== 0 ? 'text-teknisi-700 font-semibold' : ''}>
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
                            className="border-sukses-200 bg-sukses-50 text-sukses-700 text-[10px] py-0 px-2 gap-1"
                          >
                            <Check className="size-3" /> Lolos
                          </Badge>
                        )}
                        {isGagal && (
                          <Badge
                            variant="outline"
                            className="border-bahaya-600/25 bg-bahaya-600/10 text-bahaya-700 text-[10px] py-0 px-2 gap-1"
                          >
                            <AlertTriangle className="size-3" /> Gagal
                          </Badge>
                        )}
                        {!isLolos && !isGagal && (
                          <Badge
                            variant="outline"
                            className="border-garis-300 bg-permukaan-100 text-grafit-700 text-[10px] py-0 px-2"
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
                            className="sm:size-6 text-muted-foreground hover:bg-bahaya-600/10 hover:text-bahaya-700"
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
          <div className="p-3 bg-safety-500/10 border-t border-safety-600/30 text-xs text-safety-700 flex items-center gap-2">
            <AlertTriangle className="size-4 shrink-0 text-safety-700" />
            <span>
              Perhatian: Terdapat nilai titik ukur di luar batas toleransi yang diizinkan. Pertimbangkan untuk
              memberi status <strong>Gagal</strong> atau <strong>Lolos dengan Catatan</strong> saat
              finalisasi.
            </span>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
