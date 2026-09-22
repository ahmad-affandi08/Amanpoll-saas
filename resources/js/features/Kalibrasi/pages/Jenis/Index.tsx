import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Trash2, Search } from 'lucide-react';
import type { JenisKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogFormJenis } from '@/features/Kalibrasi/components/DialogFormJenis';
import { DialogTitikUkur } from '@/features/Kalibrasi/components/DialogTitikUkur';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  jenisKalibrasi: JenisKalibrasi[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function KalibrasiJenisIndex({ jenisKalibrasi, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const [pencarian, setPencarian] = useState('');

  const hapusJenis = async (jenis: JenisKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus jenis kalibrasi "${jenis.Nama}"?`,
        deskripsi: 'Jenis yang masih dipakai rencana atau pelaksanaan kalibrasi tidak dapat dihapus.',
        ragam: 'bahaya',
      })
    ) {
      router.delete(ruteKalibrasi.jenisDetail(jenis.Id));
    }
  };

  const filteredJenis = jenisKalibrasi.filter(
    (jk) =>
      jk.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
      jk.Kode.toLowerCase().includes(pencarian.toLowerCase()) ||
      (jk.Deskripsi ?? '').toLowerCase().includes(pencarian.toLowerCase()),
  );

  return (
    <KerangkaAplikasi>
      <Head title="Jenis Kalibrasi & Titik Ukur Standar" />

      <div className="space-y-6">
        {/* Header */}
        <KepalaHalaman
          judul="Jenis Kalibrasi"
          deskripsi="Atur metode, spesifikasi unit, dan template titik ukur standar untuk instrumen dan alat uji."
          aksi={<DialogFormJenis jenis={null} wajib={wajib.jenis} />}
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
                <KeadaanKosong
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
                    {filteredJenis.map((jk) => (
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
                          <DialogTitikUkur jenis={jk} wajib={wajib.titikUkur} />
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
                            <DialogFormJenis jenis={jk} wajib={wajib.jenis} />
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
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}
