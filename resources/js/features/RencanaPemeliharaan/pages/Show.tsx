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
import { Label } from '@/components/ui/label';
import { DatePicker } from '@/components/ui/date-picker';
import { ArrowLeft, Plus, Trash2, Calendar, Wrench, Clock } from 'lucide-react';
import type { RencanaPemeliharaan } from '@/features/PreventifInspeksi/types';
import { ruteRencanaPemeliharaan } from '@/features/RencanaPemeliharaan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { tanggalHariIni } from '@/lib/waktu';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { DialogUnitPengelolaRencana } from '@/features/RencanaPemeliharaan/components/DialogUnitPengelolaRencana';

interface Props {
  rencana: RencanaPemeliharaan;
  asetTersedia: { Id: string; KodeAset: string; Nama: string; LokasiId?: string | null }[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
  /** Organisasi memakai unit pengelola (PRD 8.21). */
  unitPengelolaDipakai: boolean;
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  /** Unit pengelola bersama seluruh aset rencana ini, bila semuanya sama. */
  saranUnitPengelolaId: string | null;
}

export default function RencanaPemeliharaanShow({
  rencana,
  asetTersedia,
  wajib,
  unitPengelolaDipakai,
  pilihanUnitPengelola,
  saranUnitPengelolaId,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const [bukaDialogAset, setBukaDialogAset] = useState(false);

  const formAset = useForm({
    AsetId: '',
    TanggalMulai: tanggalHariIni(),
    TanggalBerikutnya: '',
  });

  const daftarkanAset = (e: FormEvent) => {
    e.preventDefault();
    formAset.post(ruteRencanaPemeliharaan.aset(rencana.Id), {
      onSuccess: () => {
        setBukaDialogAset(false);
        formAset.reset();
      },
    });
  };

  const lepasAset = async (asetId: string, namaAset: string) => {
    if (
      await konfirmasi({
        judul: `Lepas aset "${namaAset}" dari rencana ini?`,
        deskripsi: 'Jadwal preventif berikutnya tidak lagi dibuat untuk aset tersebut.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      })
    ) {
      router.delete(ruteRencanaPemeliharaan.asetDetail(rencana.Id, asetId));
    }
  };

  // Filter aset yang belum didaftarkan di rencana ini
  const asetTerdaftarIds = new Set(rencana.aset?.map((a) => a.AsetId) ?? []);
  const asetBelumTerdaftar = asetTersedia.filter((a) => !asetTerdaftarIds.has(a.Id));

  return (
    <KerangkaAplikasi>
      <Head title={`Rencana: ${rencana.Nama}`} />
      <BreadcrumbHalaman />

      <div className="space-y-6">
        {/* Breadcrumb */}
        <div className="flex items-center gap-2 text-sm text-grafit-500">
          <Link
            href={ruteRencanaPemeliharaan.index}
            className="inline-flex min-h-11 items-center gap-1 rounded-[5px] underline-offset-4 hover:text-grafit-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>Kembali ke Daftar Rencana</span>
          </Link>
        </div>

        {/* Info Rencana */}
        <div className="bg-card border border-garis-200 rounded-xl p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2.5">
                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-grafit-700 border border-garis-300">
                  {rencana.Kode}
                </span>
                <Badge
                  variant={rencana.Aktif ? 'default' : 'secondary'}
                  className={rencana.Aktif ? 'bg-sukses-50 text-sukses-700 border-sukses-200' : ''}
                >
                  {rencana.Aktif ? 'Aktif' : 'Nonaktif'}
                </Badge>
                <Badge variant="outline">Prioritas: {rencana.Prioritas}</Badge>
              </div>

              <h1 className="text-xl font-bold text-grafit-950">{rencana.Nama}</h1>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 text-xs text-grafit-700">
                <div className="flex items-center gap-1.5">
                  <Clock className="h-4 w-4 text-grafit-500" />
                  <span>
                    Interval:{' '}
                    <strong>
                      Setiap {rencana.IntervalNilai} {rencana.IntervalSatuan}
                    </strong>
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  <Calendar className="h-4 w-4 text-grafit-500" />
                  <span>
                    Horizon WO: <strong>{rencana.BuatPerintahKerjaHariSebelum} Hari Sebelum</strong>
                  </span>
                </div>
                {rencana.templat_daftar_periksa && (
                  <div className="flex items-center gap-1.5">
                    <Wrench className="h-4 w-4 text-grafit-500" />
                    <span>
                      Checklist: <strong>{rencana.templat_daftar_periksa.Kode}</strong>
                    </span>
                  </div>
                )}
              </div>

              {unitPengelolaDipakai && (
                <div className="flex flex-wrap items-center gap-2 pt-1 text-xs text-grafit-700">
                  <span>
                    Unit pengelola: <strong>{rencana.unit_pengelola?.Nama ?? 'Mengikuti aset'}</strong>
                  </span>
                  <DialogUnitPengelolaRencana
                    rencana={rencana}
                    pilihanUnitPengelola={pilihanUnitPengelola}
                    saranUnitPengelolaId={saranUnitPengelolaId}
                    wajib={wajib.rencana}
                  />
                </div>
              )}
            </div>

            <Dialog open={bukaDialogAset} onOpenChange={setBukaDialogAset}>
              <DialogTrigger asChild>
                <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
                  <Plus className="h-4 w-4" />
                  Daftarkan Aset ke Rencana
                </Button>
              </DialogTrigger>
              <DialogContent className="sm:max-w-md">
                <AturanWajibProvider aturan={wajib.aset}>
                  <form onSubmit={daftarkanAset}>
                    <DialogHeader>
                      <DialogTitle>Daftarkan Aset</DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                      <div className="space-y-1.5">
                        <Label nama="AsetId" htmlFor="AsetId">
                          Pilih Aset <span className="text-destructive">*</span>
                        </Label>
                        <Combobox
                          nilai={formAset.data.AsetId}
                          onPilih={(val) => formAset.setData('AsetId', val)}
                          opsi={opsiDari(asetBelumTerdaftar, (a) => `${a.KodeAset} - ${a.Nama}`)}
                          placeholder="Pilih unit aset..."
                          className="cursor-pointer"
                        />
                        {formAset.errors.AsetId && (
                          <p className="text-xs text-destructive">{formAset.errors.AsetId}</p>
                        )}
                      </div>

                      <div className="space-y-1.5">
                        <Label nama="TanggalMulai" htmlFor="TanggalMulai">
                          Tanggal Mulai Berlaku <span className="text-destructive">*</span>
                        </Label>
                        <DatePicker
                          value={formAset.data.TanggalMulai}
                          onChange={(val) => formAset.setData('TanggalMulai', val)}
                          placeholder="Pilih tanggal mulai..."
                        />
                      </div>

                      <div className="space-y-1.5">
                        <Label nama="TanggalBerikutnya" htmlFor="TanggalBerikutnya">
                          Tanggal Jatuh Tempo Pertama (Opsional)
                        </Label>
                        <DatePicker
                          value={formAset.data.TanggalBerikutnya}
                          onChange={(val) => formAset.setData('TanggalBerikutnya', val)}
                          placeholder="Otomatis dihitung jika kosong"
                        />
                        <p className="text-[11px] text-grafit-500">
                          Kosongkan agar otomatis dihitung: Tanggal Mulai + {rencana.IntervalNilai}{' '}
                          {rencana.IntervalSatuan}.
                        </p>
                      </div>
                    </div>

                    <DialogFooter>
                      <Button
                        type="button"
                        variant="outline"
                        className="cursor-pointer"
                        onClick={() => setBukaDialogAset(false)}
                      >
                        Batal
                      </Button>
                      <Button
                        type="submit"
                        className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                        disabled={formAset.processing || !formAset.data.AsetId}
                      >
                        {formAset.processing ? 'Mendaftarkan...' : 'Daftarkan Aset'}
                      </Button>
                    </DialogFooter>
                  </form>
                </AturanWajibProvider>
              </DialogContent>
            </Dialog>
          </div>
        </div>

        {/* Tabel Aset Terdaftar */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-grafit-950">
              Aset Terdaftar ({rencana.aset?.length ?? 0})
            </h2>
            <span className="text-xs text-grafit-500">
              Setiap aset memiliki tanggal jatuh tempo pemeliharaan preventif tersendiri.
            </span>
          </div>

          {!rencana.aset || rencana.aset.length === 0 ? (
            <div className="bg-card border border-dashed border-garis-300 rounded-xl p-8 text-center">
              <p className="text-grafit-500 text-sm">Belum ada aset yang didaftarkan pada rencana ini.</p>
            </div>
          ) : (
            <div className="bg-card border border-garis-200 rounded-xl overflow-hidden shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="bg-permukaan-50 border-b border-garis-200 text-xs font-semibold text-grafit-700 uppercase">
                    <tr>
                      <th className="px-5 py-3">Aset</th>
                      <th className="px-5 py-3">Lokasi</th>
                      <th className="px-5 py-3">Tanggal Mulai</th>
                      <th className="px-5 py-3">Jatuh Tempo Berikutnya</th>
                      <th className="px-5 py-3">Riwayat Jadwal</th>
                      <th className="px-5 py-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-permukaan-100">
                    {rencana.aset.map((item) => (
                      <tr key={item.Id} className="hover:bg-accent transition-colors">
                        <td className="px-5 py-4 font-medium text-grafit-950">
                          <div>
                            <span className="font-mono text-xs font-semibold text-grafit-500">
                              {item.aset?.KodeAset}
                            </span>
                            <div className="font-semibold text-grafit-950">{item.aset?.Nama}</div>
                          </div>
                        </td>
                        <td className="px-5 py-4 text-grafit-700 text-xs">
                          {item.aset?.lokasi?.Nama ?? '-'}
                        </td>
                        <td className="px-5 py-4 text-grafit-700 text-xs">{item.TanggalMulai}</td>
                        <td className="px-5 py-4">
                          <span className="inline-flex items-center gap-1.5 font-semibold text-xs px-2.5 py-1 rounded-full bg-teknisi-50 text-teknisi-700 border border-teknisi-200">
                            <Calendar className="h-3.5 w-3.5" />
                            {item.TanggalBerikutnya ?? 'Belum dijadwalkan'}
                          </span>
                        </td>
                        <td className="px-5 py-4 text-xs text-grafit-500">
                          {item.jadwal && item.jadwal.length > 0 ? (
                            <span>{item.jadwal.length} Jadwal tercatat</span>
                          ) : (
                            <span className="text-grafit-500">Menunggu siklus</span>
                          )}
                        </td>
                        <td className="px-5 py-4 text-right">
                          <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer h-8 px-2.5 text-xs text-destructive hover:text-bahaya-700 hover:bg-bahaya-600/10 border-bahaya-600/25"
                            onClick={() => lepasAset(item.AsetId, item.aset?.Nama ?? 'Aset')}
                          >
                            <Trash2 className="h-3.5 w-3.5 mr-1" />
                            Lepas
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
