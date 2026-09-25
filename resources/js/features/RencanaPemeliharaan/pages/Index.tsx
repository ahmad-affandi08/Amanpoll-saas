import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
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
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Plus, Search, Play, ArrowRight, CheckCircle2 } from 'lucide-react';
import type { RencanaPemeliharaan } from '@/features/PreventifInspeksi/types';
import { ruteRencanaPemeliharaan } from '@/features/RencanaPemeliharaan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { TANPA_PILIHAN, opsiDari, opsiUnitPengelola } from '@/lib/pilihan';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import {
  BidangStrategiJadwal,
  ringkasPemicu,
  type StrategiJadwal,
} from '@/features/RencanaPemeliharaan/components/BidangStrategiJadwal';

interface Props {
  rencana: RencanaPemeliharaan[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
  /** Organisasi memakai unit pengelola (PRD 8.21); bila tidak, halaman tampil seperti sebelumnya. */
  unitPengelolaDipakai: boolean;
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  saringanUnitPengelola: UnitPengelolaRingkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function RencanaPemeliharaanIndex({
  rencana,
  templatDaftarPeriksa,
  wajib,
  unitPengelolaDipakai,
  pilihanUnitPengelola,
  saringanUnitPengelola,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');
  const [saringanUnit, setSaringanUnit] = useState(TANPA_PILIHAN);
  const [menjalankanScheduler, setMenjalankanScheduler] = useState(false);

  const form = useForm({
    Kode: '',
    Nama: '',
    Jenis: 'Preventif',
    TemplatDaftarPeriksaId: '',
    Prioritas: 'Normal',
    StrategiJadwal: 'Interval' as StrategiJadwal,
    IntervalNilai: 30 as number | null,
    IntervalSatuan: 'Hari' as string | null,
    AmbangMeter: null as number | string | null,
    BuatPerintahKerjaHariSebelum: 7,
    Aktif: true,
    UnitPengelolaId: TANPA_PILIHAN,
  });

  const daftarTersaring = rencana.filter(
    (r) =>
      (saringanUnit === TANPA_PILIHAN || r.UnitPengelolaId === saringanUnit) &&
      (r.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
        r.Kode.toLowerCase().includes(pencarian.toLowerCase())),
  );

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({
      ...data,
      UnitPengelolaId: data.UnitPengelolaId === TANPA_PILIHAN ? null : data.UnitPengelolaId,
    }));
    form.post(ruteRencanaPemeliharaan.index, {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  const jalankanScheduler = async () => {
    if (
      await konfirmasi({
        judul: 'Jalankan penjadwalan preventif sekarang?',
        deskripsi: 'Perintah kerja dibuat otomatis untuk aset yang jatuh tempo dalam horizon waktu rencana.',
        ragam: 'perhatian',
        labelAksi: 'Jalankan',
      })
    ) {
      setMenjalankanScheduler(true);
      router.post(
        ruteRencanaPemeliharaan.jalankanScheduler,
        {},
        {
          onFinish: () => setMenjalankanScheduler(false),
        },
      );
    }
  };

  const totalAsetTerdaftar = rencana.reduce(
    (acc, curr) => acc + (curr.aset_count ?? curr.aset?.length ?? 0),
    0,
  );

  return (
    <KerangkaAplikasi>
      <Head title="Rencana Pemeliharaan Preventif" />

      <div className="space-y-5">
        {/* Header */}
        <KepalaHalaman
          judul="Rencana Pemeliharaan Preventif"
          deskripsi="Otomatisasi siklus pemeliharaan berkala, pencegahan downtime, dan kepatuhan servis aset."
          aksi={
            <>
              <TombolEkspor url={ruteRencanaPemeliharaan.ekspor} />
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  className="cursor-pointer gap-2"
                  onClick={jalankanScheduler}
                  disabled={menjalankanScheduler}
                >
                  <Play className="h-4 w-4" />
                  {menjalankanScheduler ? 'Menjadwalkan...' : 'Jalankan Penjadwal'}
                </Button>

                <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
                  <DialogTrigger asChild>
                    <Button className="cursor-pointer gap-2">
                      <Plus className="h-4 w-4" />
                      Buat Rencana Baru
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="sm:max-w-md">
                    <AturanWajibProvider aturan={wajib.rencana}>
                      <form onSubmit={onSubmit}>
                        <DialogHeader>
                          <DialogTitle>Buat Rencana Pemeliharaan</DialogTitle>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                          <BidangKode
                            nilai={form.data.Kode}
                            onUbah={(nilai) => form.setData('Kode', nilai)}
                            galat={form.errors.Kode}
                            label="Kode Rencana"
                            contoh="Misal: PM-CHILLER-BULANAN"
                          />

                          <div className="space-y-1.5">
                            <Label nama="Nama" htmlFor="Nama">
                              Nama Rencana <span className="text-destructive">*</span>
                            </Label>
                            <Input
                              id="Nama"
                              placeholder="Misal: Servis Rutin Bulanan Chiller Utama"
                              value={form.data.Nama}
                              onChange={(e) => form.setData('Nama', e.target.value)}
                              required
                            />
                            {form.errors.Nama && (
                              <p className="text-xs text-destructive">{form.errors.Nama}</p>
                            )}
                          </div>

                          <BidangStrategiJadwal
                            nilai={form.data}
                            ubah={(perubahan) => form.setData((data) => ({ ...data, ...perubahan }))}
                            galat={form.errors}
                          />

                          <div className="space-y-1.5">
                            <Label nama="TemplatDaftarPeriksaId" htmlFor="TemplatDaftarPeriksaId">
                              Hubungkan Templat Checklist (Opsional)
                            </Label>
                            <Combobox
                              nilai={form.data.TemplatDaftarPeriksaId || '__none__'}
                              onPilih={(val) =>
                                form.setData('TemplatDaftarPeriksaId', val === '__none__' ? '' : val)
                              }
                              opsi={[
                                { nilai: '__none__', label: '-- Tanpa Checklist Otomatis --' },
                                ...opsiDari(templatDaftarPeriksa, (t) => `${t.Kode} - ${t.Nama}`),
                              ]}
                              placeholder="Pilih Templat Checklist"
                              className="cursor-pointer"
                            />
                          </div>

                          {unitPengelolaDipakai && (
                            <div className="space-y-1.5">
                              <Label nama="UnitPengelolaId" htmlFor="UnitPengelolaId">
                                Unit Pengelola
                              </Label>
                              <Combobox
                                nilai={form.data.UnitPengelolaId}
                                onPilih={(val) => form.setData('UnitPengelolaId', val)}
                                opsi={opsiUnitPengelola(pilihanUnitPengelola)}
                                placeholder="Pilih unit pengelola"
                                className="cursor-pointer"
                              />
                              <p className="text-xs text-muted-foreground">
                                Dipakai untuk tiket preventif bila asetnya belum punya unit pengelola.
                              </p>
                              {form.errors.UnitPengelolaId && (
                                <p className="text-xs text-destructive">{form.errors.UnitPengelolaId}</p>
                              )}
                            </div>
                          )}

                          <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                              <Label nama="Prioritas" htmlFor="Prioritas">
                                Prioritas Perintah Kerja
                              </Label>
                              <Select
                                value={form.data.Prioritas}
                                onValueChange={(val) => form.setData('Prioritas', val)}
                              >
                                <SelectTrigger id="Prioritas" className="cursor-pointer">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="Rendah">Rendah</SelectItem>
                                  <SelectItem value="Normal">Normal</SelectItem>
                                  <SelectItem value="Tinggi">Tinggi</SelectItem>
                                  <SelectItem value="Darurat">Darurat</SelectItem>
                                </SelectContent>
                              </Select>
                            </div>

                            <div className="space-y-1.5">
                              <Label nama="BuatPerintahKerjaHariSebelum" htmlFor="BuatHariSebelum">
                                Buat WO (Hari Sebelum)
                              </Label>
                              <Input
                                id="BuatHariSebelum"
                                type="number"
                                min={0}
                                value={form.data.BuatPerintahKerjaHariSebelum}
                                onChange={(e) =>
                                  form.setData('BuatPerintahKerjaHariSebelum', Number(e.target.value))
                                }
                              />
                            </div>
                          </div>
                        </div>

                        <DialogFooter>
                          <Button
                            type="button"
                            variant="outline"
                            className="cursor-pointer"
                            onClick={() => setBukaDialog(false)}
                          >
                            Batal
                          </Button>
                          <Button type="submit" className="cursor-pointer" disabled={form.processing}>
                            {form.processing ? 'Menyimpan...' : 'Simpan Rencana'}
                          </Button>
                        </DialogFooter>
                      </form>
                    </AturanWajibProvider>
                  </DialogContent>
                </Dialog>
              </div>
            </>
          }
        />

        {/* Ringkasan Metrik */}
        <DeretStatistik kolom={3}>
          <KartuStatistik
            menyatu
            label="Total Rencana Aktif"
            nilai={`${rencana.filter((r) => r.Aktif).length} / ${rencana.length}`}
          />
          <KartuStatistik menyatu label="Total Aset Terjadwal" nilai={`${totalAsetTerdaftar} Unit`} />
          <KartuStatistik
            menyatu
            label="Siklus Penjadwalan"
            nilai={
              <span className="flex items-center gap-1.5 text-sm font-medium tracking-normal">
                <CheckCircle2 aria-hidden="true" className="size-4 text-sukses-600" />
                Otomatis (Harian Pukul 01:00)
              </span>
            }
          />
        </DeretStatistik>

        {/* Filter Pencarian */}
        <div className="flex flex-wrap items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
            />
            <Input
              type="text"
              aria-label="Cari kode atau nama rencana"
              placeholder="Cari kode atau nama rencana..."
              className="pl-8"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
            />
          </div>
          {unitPengelolaDipakai && (
            <Select value={saringanUnit} onValueChange={setSaringanUnit}>
              <SelectTrigger className="w-full cursor-pointer sm:w-48" aria-label="Saring unit pengelola">
                <SelectValue placeholder="Semua unit pengelola" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_PILIHAN} className="cursor-pointer">
                  Semua unit pengelola
                </SelectItem>
                {saringanUnitPengelola.map((unit) => (
                  <SelectItem key={unit.Id} value={unit.Id} className="cursor-pointer">
                    {unit.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </div>

        {/* Daftar Kartu Rencana */}
        {daftarTersaring.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/pemeliharaan-jadwal.webp"
            judul="Belum Ada Rencana Pemeliharaan"
            deskripsi="Rencana pemeliharaan preventif yang dibuat akan muncul di sini untuk mengotomatisasi perintah kerja berkala."
          />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {daftarTersaring.map((r) => (
              <div
                key={r.Id}
                className="flex flex-col justify-between rounded-md border border-border bg-card p-5 transition-colors hover:border-teknisi-300"
              >
                <div className="space-y-3">
                  <div className="flex items-center justify-between gap-2">
                    <span className="rounded-sm bg-permukaan-100 px-2 py-0.5 font-mono text-xs font-semibold text-grafit-700">
                      {r.Kode}
                    </span>
                    <Badge variant={r.Aktif ? 'sukses' : 'netral'}>{r.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                  </div>

                  <div>
                    <h3 className="text-[15px] font-semibold text-foreground">{r.Nama}</h3>
                    <p className="text-xs text-grafit-500 mt-0.5">Prioritas: {r.Prioritas}</p>
                  </div>

                  <div className="space-y-1.5 border-t border-border pt-2 text-xs text-grafit-700">
                    <div className="flex items-center justify-between">
                      <span className="text-grafit-500">Pemicu:</span>
                      <span className="text-right font-semibold text-grafit-950">{ringkasPemicu(r)}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-grafit-500">Aset Didaftarkan:</span>
                      <span className="font-semibold text-grafit-950">
                        {r.aset_count ?? r.aset?.length ?? 0} Aset
                      </span>
                    </div>
                    {unitPengelolaDipakai && (
                      <div className="flex items-center justify-between">
                        <span className="text-grafit-500">Unit Pengelola:</span>
                        <span className="font-semibold text-grafit-950">
                          {r.unit_pengelola?.Nama ?? 'Mengikuti aset'}
                        </span>
                      </div>
                    )}
                    {r.templat_daftar_periksa && (
                      <div className="flex items-center justify-between pt-1">
                        <span className="text-grafit-500">Checklist:</span>
                        <span
                          className="text-grafit-700 truncate max-w-[160px]"
                          title={r.templat_daftar_periksa.Nama}
                        >
                          {r.templat_daftar_periksa.Kode}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                <div className="mt-4 border-t border-border pt-4">
                  <Link
                    href={ruteRencanaPemeliharaan.detail(r.Id)}
                    className="inline-flex items-center justify-between w-full text-xs font-medium text-teknisi-600 hover:text-teknisi-700 cursor-pointer"
                  >
                    <span>Kelola Aset & Jadwal</span>
                    <ArrowRight className="h-3.5 w-3.5" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
