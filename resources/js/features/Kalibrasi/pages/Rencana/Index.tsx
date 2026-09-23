import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Trash2, ArrowRight, Filter, Search } from 'lucide-react';
import type { RencanaKalibrasi } from '@/features/Kalibrasi/types';
import { statusKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogFormRencana } from '@/features/Kalibrasi/components/DialogFormRencana';
import type { AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

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
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function KalibrasiRencanaIndex({
  rencanaKalibrasi,
  aset,
  jenisKalibrasi,
  penyedia,
  filter,
  wajib,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const [pencarian, setPencarian] = useState('');

  const hapusRencana = async (rk: RencanaKalibrasi) => {
    if (
      await konfirmasi({
        judul: `Hapus rencana kalibrasi aset "${rk.aset?.Nama}"?`,
        deskripsi: 'Jadwal kalibrasi berikutnya untuk aset ini tidak akan dibuat lagi.',
        ragam: 'bahaya',
      })
    ) {
      router.delete(ruteKalibrasi.rencanaDetail(rk.Id));
    }
  };

  const terapkanFilter = (field: string, value: string) => {
    router.get(
      ruteKalibrasi.rencana,
      {
        ...filter,
        [field]: value === '__all__' ? undefined : value,
      },
      { preserveState: true },
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
    <KerangkaAplikasi>
      <Head title="Rencana Kalibrasi Berkala" />

      <div className="space-y-6">
        {/* Header */}
        <KepalaHalaman
          judul="Rencana Kalibrasi"
          deskripsi={
            <>
              Atur siklus interval, tanggal jatuh tempo, dan mitra kalibrasi untuk setiap instrumen
              operasional.
            </>
          }
          aksi={
            <>
              <TombolEkspor url="/kalibrasi/rencana/ekspor" filter={filter as Record<string, string>} />
              <DialogFormRencana
                rencana={null}
                aset={aset}
                jenisKalibrasi={jenisKalibrasi}
                penyedia={penyedia}
                wajib={wajib.rencana}
              />
            </>
          }
        />

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
                <Combobox
                  nilai={filter.jenisKalibrasiId ?? '__all__'}
                  onPilih={(val) => terapkanFilter('jenisKalibrasiId', val)}
                  opsi={[
                    { nilai: '__all__', label: 'Semua Jenis Kalibrasi' },
                    ...opsiDari(jenisKalibrasi, (jk) => jk.Nama),
                  ]}
                  placeholder="Jenis Kalibrasi"
                  className="h-9 text-xs"
                />
              </div>

              <div>
                <Combobox
                  nilai={filter.asetId ?? '__all__'}
                  onPilih={(val) => terapkanFilter('asetId', val)}
                  opsi={[
                    { nilai: '__all__', label: 'Semua Aset' },
                    ...opsiDari(aset, (a) => `${a.KodeAset} - ${a.Nama}`),
                  ]}
                  placeholder="Pilih Aset Spesifik"
                  className="h-9 text-xs"
                />
              </div>
            </div>
          </CardHeader>

          <CardContent className="p-0">
            {filteredList.length === 0 ? (
              <div className="py-12">
                <KeadaanKosong
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
                              href={ruteKalibrasi.rencanaDetail(rk.Id)}
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
                                <Link href={ruteKalibrasi.rencanaDetail(rk.Id)}>
                                  Detail
                                  <ArrowRight className="size-3 ml-1" />
                                </Link>
                              </Button>
                              <DialogFormRencana
                                rencana={rk}
                                aset={aset}
                                jenisKalibrasi={jenisKalibrasi}
                                penyedia={penyedia}
                                wajib={wajib.rencana}
                              />
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
    </KerangkaAplikasi>
  );
}
