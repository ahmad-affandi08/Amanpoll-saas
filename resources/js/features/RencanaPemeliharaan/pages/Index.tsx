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
import { opsiDari } from '@/lib/pilihan';

interface Props {
  rencana: RencanaPemeliharaan[];
  templatDaftarPeriksa: { Id: string; Nama: string; Kode: string }[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function RencanaPemeliharaanIndex({ rencana, templatDaftarPeriksa, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');
  const [menjalankanScheduler, setMenjalankanScheduler] = useState(false);

  const form = useForm({
    Kode: '',
    Nama: '',
    Jenis: 'Preventif',
    TemplatDaftarPeriksaId: '',
    Prioritas: 'Normal',
    StrategiJadwal: 'Interval',
    IntervalNilai: 30,
    IntervalSatuan: 'Hari',
    BuatPerintahKerjaHariSebelum: 7,
    Aktif: true,
  });

  const daftarTersaring = rencana.filter(
    (r) =>
      r.Nama.toLowerCase().includes(pencarian.toLowerCase()) ||
      r.Kode.toLowerCase().includes(pencarian.toLowerCase()),
  );

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
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

      <div className="space-y-6">
        {/* Header */}
        <KepalaHalaman
          judul="Rencana Pemeliharaan Preventif"
          deskripsi="Otomatisasi siklus pemeliharaan berkala, pencegahan downtime, dan kepatuhan servis aset."
          aksi={
            <>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  className="cursor-pointer gap-2"
                  onClick={jalankanScheduler}
                  disabled={menjalankanScheduler}
                >
                  <Play className="h-4 w-4 text-teknisi-600" />
                  {menjalankanScheduler ? 'Menjadwalkan...' : 'Jalankan Penjadwal'}
                </Button>

                <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
                  <DialogTrigger asChild>
                    <Button className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white gap-2">
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
                              Nama Rencana <span className="text-rose-500">*</span>
                            </Label>
                            <Input
                              id="Nama"
                              placeholder="Misal: Servis Rutin Bulanan Chiller Utama"
                              value={form.data.Nama}
                              onChange={(e) => form.setData('Nama', e.target.value)}
                              required
                            />
                            {form.errors.Nama && <p className="text-xs text-rose-500">{form.errors.Nama}</p>}
                          </div>

                          <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                              <Label nama="IntervalNilai" htmlFor="IntervalNilai">
                                Interval <span className="text-rose-500">*</span>
                              </Label>
                              <Input
                                id="IntervalNilai"
                                type="number"
                                min={1}
                                value={form.data.IntervalNilai}
                                onChange={(e) => form.setData('IntervalNilai', Number(e.target.value))}
                                required
                              />
                            </div>
                            <div className="space-y-1.5">
                              <Label nama="IntervalSatuan" htmlFor="IntervalSatuan">
                                Satuan Waktu
                              </Label>
                              <Select
                                value={form.data.IntervalSatuan}
                                onValueChange={(val) => form.setData('IntervalSatuan', val)}
                              >
                                <SelectTrigger id="IntervalSatuan" className="cursor-pointer">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="Hari">Hari</SelectItem>
                                  <SelectItem value="Minggu">Minggu</SelectItem>
                                  <SelectItem value="Bulan">Bulan</SelectItem>
                                  <SelectItem value="Tahun">Tahun</SelectItem>
                                </SelectContent>
                              </Select>
                            </div>
                          </div>

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
                          <Button
                            type="submit"
                            className="cursor-pointer bg-teknisi-600 hover:bg-teknisi-700 text-white"
                            disabled={form.processing}
                          >
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
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-permukaan-500 font-medium">Total Rencana Aktif</span>
            <div className="text-2xl font-bold text-permukaan-900 mt-1">
              {rencana.filter((r) => r.Aktif).length} / {rencana.length}
            </div>
          </div>
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-permukaan-500 font-medium">Total Aset Terjadwal</span>
            <div className="text-2xl font-bold text-teknisi-700 mt-1">{totalAsetTerdaftar} Unit</div>
          </div>
          <div className="bg-card border border-permukaan-200 rounded-xl p-4 shadow-sm">
            <span className="text-xs text-permukaan-500 font-medium">Siklus Penjadwalan</span>
            <div className="text-sm font-semibold text-emerald-700 mt-2 flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4" />
              Otomatis (Harian Pukul 01:00)
            </div>
          </div>
        </div>

        {/* Filter Pencarian */}
        <div className="flex items-center gap-2 max-w-sm">
          <div className="relative w-full">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-permukaan-400" />
            <Input
              type="text"
              placeholder="Cari kode atau nama rencana..."
              className="pl-9"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
            />
          </div>
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
                className="bg-card border border-permukaan-200 rounded-xl p-5 hover:border-teknisi-300 hover:shadow-sm transition-all flex flex-col justify-between"
              >
                <div className="space-y-3">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-permukaan-700">
                      {r.Kode}
                    </span>
                    <Badge
                      variant={r.Aktif ? 'default' : 'secondary'}
                      className={r.Aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ''}
                    >
                      {r.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </div>

                  <div>
                    <h3 className="font-semibold text-permukaan-900 text-base">{r.Nama}</h3>
                    <p className="text-xs text-permukaan-500 mt-0.5">Prioritas: {r.Prioritas}</p>
                  </div>

                  <div className="space-y-1.5 pt-2 border-t border-permukaan-100 text-xs text-permukaan-600">
                    <div className="flex items-center justify-between">
                      <span className="text-permukaan-500">Interval:</span>
                      <span className="font-semibold text-permukaan-800">
                        Setiap {r.IntervalNilai} {r.IntervalSatuan}
                      </span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-permukaan-500">Aset Didaftarkan:</span>
                      <span className="font-semibold text-teknisi-700">
                        {r.aset_count ?? r.aset?.length ?? 0} Aset
                      </span>
                    </div>
                    {r.templatDaftarPeriksa && (
                      <div className="flex items-center justify-between pt-1">
                        <span className="text-permukaan-500">Checklist:</span>
                        <span
                          className="text-permukaan-700 truncate max-w-[160px]"
                          title={r.templatDaftarPeriksa.Nama}
                        >
                          {r.templatDaftarPeriksa.Kode}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                <div className="pt-4 mt-4 border-t border-permukaan-100">
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
