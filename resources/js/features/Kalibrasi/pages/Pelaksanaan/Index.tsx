import { FormEvent, useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { tanggal } from '@/components/shared/riwayat';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Plus, Search, ArrowRight, Filter, FileBadge } from 'lucide-react';
import type { PelaksanaanKalibrasi } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { DatePicker } from '@/components/ui/date-picker';
import { tanggalHariIni } from '@/lib/waktu';

interface Props {
  pelaksanaanKalibrasi: PelaksanaanKalibrasi[];
  aset: { Id: string; KodeAset: string; Nama: string }[];
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
  rencanaKalibrasi: {
    Id: string;
    AsetId: string;
    JenisKalibrasiId?: string | null;
    IntervalHari: number;
    TanggalBerikutnya: string;
  }[];
  teknisi: { Id: string; Nama: string }[];
  filter: {
    hasil?: string;
    asetId?: string;
    nomor?: string;
  };
}

export default function KalibrasiPelaksanaanIndex({
  pelaksanaanKalibrasi,
  aset,
  jenisKalibrasi,
  penyedia,
  rencanaKalibrasi,
  teknisi,
  filter,
}: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState('');

  const form = useForm({
    AsetId: '',
    RencanaKalibrasiId: '',
    JenisKalibrasiId: '',
    PenyediaId: '',
    TanggalKalibrasi: tanggalHariIni(),
    Laboratorium: '',
    DilaksanakanOleh: '',
    Catatan: '',
  });

  // Cek query string URL untuk auto open modal (mis. dari rencana show)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('bukaModal') === '1') {
      const asetId = params.get('asetId') ?? '';
      const rencanaId = params.get('rencanaId') ?? '';
      form.setData((prev) => ({
        ...prev,
        AsetId: asetId,
        RencanaKalibrasiId: rencanaId,
      }));
      setBukaDialog(true);
    }
  }, []);

  const onAsetChange = (asetId: string) => {
    // Cari rencana kalibrasi aktif untuk aset ini jika ada
    const matchRencana = rencanaKalibrasi.find((rk) => rk.AsetId === asetId);
    form.setData({
      ...form.data,
      AsetId: asetId,
      RencanaKalibrasiId: matchRencana ? matchRencana.Id : '',
      JenisKalibrasiId: matchRencana?.JenisKalibrasiId ?? form.data.JenisKalibrasiId,
    });
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteKalibrasi.pelaksanaan, {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  const terapkanFilter = (field: string, value: string) => {
    router.get(
      ruteKalibrasi.pelaksanaan,
      {
        ...filter,
        [field]: value === '__all__' ? undefined : value,
      },
      { preserveState: true },
    );
  };

  const filteredList = pelaksanaanKalibrasi.filter((pk) => {
    return (
      pk.Nomor.toLowerCase().includes(pencarian.toLowerCase()) ||
      (pk.aset?.Nama ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (pk.NomorSertifikat ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (pk.Laboratorium ?? '').toLowerCase().includes(pencarian.toLowerCase())
    );
  });

  return (
    <KerangkaAplikasi>
      <Head title="Pelaksanaan & Sertifikat Kalibrasi" />

      <div className="space-y-6">
        {/* Header */}
        <KepalaHalaman
          judul="Pelaksanaan Kalibrasi"
          deskripsi={
            <>
              Catat hasil pengujian titik ukur, verifikasi sertifikat lab, dan pantau pengesahan kalibrasi
              instrumen.
            </>
          }
          aksi={
            <>
              <TombolEkspor url={ruteKalibrasi.pelaksanaanEkspor} filter={filter as Record<string, string>} />
              <Button onClick={() => setBukaDialog(true)} size="sm">
                <Plus className="mr-1.5 size-4" />
                Jadwalkan Kalibrasi
              </Button>
            </>
          }
        />

        {/* Filter & Search */}
        <Card className="border-border">
          <CardHeader className="p-4 sm:p-5 border-b border-border">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="relative">
                <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                <Input
                  placeholder="Cari nomor, sertifikat, atau lab..."
                  value={pencarian}
                  onChange={(e) => setPencarian(e.target.value)}
                  className="pl-8 h-9 text-xs"
                />
              </div>

              <div>
                <Select
                  value={filter.hasil ?? '__all__'}
                  onValueChange={(val) => terapkanFilter('hasil', val)}
                >
                  <SelectTrigger className="h-9 text-xs">
                    <SelectValue placeholder="Semua Hasil Pengujian" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__all__">Semua Hasil Pengujian</SelectItem>
                    <SelectItem value="Terjadwal">Terjadwal</SelectItem>
                    <SelectItem value="Lolos">Lolos</SelectItem>
                    <SelectItem value="LolosDenganCatatan">Lolos dengan Catatan</SelectItem>
                    <SelectItem value="Gagal">Gagal (Di luar toleransi)</SelectItem>
                  </SelectContent>
                </Select>
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
                  judul="Belum ada riwayat pelaksanaan kalibrasi."
                  deskripsi="Kegiatan pengujian kalibrasi yang didaftarkan akan ditampilkan di sini."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-muted-foreground border-b border-border">
                    <tr>
                      <th className="px-4 py-3 font-medium">Nomor Pelaksanaan</th>
                      <th className="px-4 py-3 font-medium">Aset / Instrumen</th>
                      <th className="px-3 py-3 font-medium">Tanggal</th>
                      <th className="px-3 py-3 font-medium">Hasil</th>
                      <th className="px-4 py-3 font-medium">Sertifikat & Lab</th>
                      <th className="px-3 py-3 font-medium">Berlaku Sampai</th>
                      <th className="px-3 py-3 font-medium">Pelaksana</th>
                      <th className="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {filteredList.map((pk) => {
                      const badge = hasilKalibrasiBadge(pk.Hasil);
                      return (
                        <tr key={pk.Id} className="hover:bg-accent transition-colors">
                          <td className="px-4 py-3 font-mono font-semibold text-foreground whitespace-nowrap">
                            <Link href={ruteKalibrasi.pelaksanaanDetail(pk.Id)} className="hover:underline">
                              {pk.Nomor}
                            </Link>
                          </td>
                          <td className="px-4 py-3 font-medium text-foreground">
                            <div className="font-semibold">{pk.aset?.Nama ?? 'Aset'}</div>
                            <div className="font-mono text-[11px] text-muted-foreground">
                              {pk.aset?.KodeAset}
                            </div>
                          </td>
                          <td className="px-3 py-3 text-muted-foreground whitespace-nowrap font-mono">
                            {tanggal(pk.TanggalKalibrasi)}
                          </td>
                          <td className="px-3 py-3 whitespace-nowrap">
                            <Badge variant="outline" className={badge.className}>
                              {badge.label}
                            </Badge>
                          </td>
                          <td className="px-4 py-3 text-muted-foreground">
                            {pk.NomorSertifikat ? (
                              <div className="flex items-center gap-1.5">
                                <FileBadge className="size-3.5 text-sukses-600 shrink-0" />
                                <span className="font-medium font-mono text-foreground">
                                  {pk.NomorSertifikat}
                                </span>
                              </div>
                            ) : (
                              <span className="text-muted-foreground italic">Belum terbit</span>
                            )}
                            {pk.Laboratorium && (
                              <div className="text-[11px] text-muted-foreground mt-0.5">
                                {pk.Laboratorium}
                              </div>
                            )}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground whitespace-nowrap font-mono">
                            {tanggal(pk.TanggalBerlakuSampai ?? null)}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground whitespace-nowrap">
                            {pk.dilaksanakanOleh?.Nama ?? pk.penyedia?.Nama ?? '—'}
                          </td>
                          <td className="px-4 py-3 text-right whitespace-nowrap">
                            <Button asChild variant="outline" size="sm" className="h-7 text-xs gap-1">
                              <Link href={ruteKalibrasi.pelaksanaanDetail(pk.Id)}>
                                Detail
                                <ArrowRight className="size-3" />
                              </Link>
                            </Button>
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

      {/* Modal Jadwalkan Pelaksanaan */}
      <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Jadwalkan Pelaksanaan Kalibrasi</DialogTitle>
            <DialogDescription>
              Buat agenda kalibrasi baru. Titik ukur standar akan otomatis diinisialisasi jika jenis kalibrasi
              memiliki template.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={onSubmit} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="AsetId">Pilih Aset / Instrumen *</Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={onAsetChange}
                opsi={opsiDari(aset, (a) => `${a.KodeAset} - ${a.Nama}`)}
                placeholder="Pilih Aset"
                className="h-9 text-xs"
              />
              {form.errors.AsetId && <p className="text-xs text-destructive">{form.errors.AsetId}</p>}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="JenisKalibrasiId">Jenis Kalibrasi</Label>
                <Combobox
                  nilai={form.data.JenisKalibrasiId || '__none__'}
                  onPilih={(val) => form.setData('JenisKalibrasiId', val === '__none__' ? '' : val)}
                  opsi={[
                    { nilai: '__none__', label: 'Tanpa Spesifikasi' },
                    ...opsiDari(jenisKalibrasi, (jk) => jk.Nama),
                  ]}
                  placeholder="Pilih Jenis"
                  className="h-9 text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="PenyediaId">Penyedia / Laboratorium Eksternal</Label>
                <Combobox
                  nilai={form.data.PenyediaId || '__internal__'}
                  onPilih={(val) => form.setData('PenyediaId', val === '__internal__' ? '' : val)}
                  opsi={[
                    { nilai: '__internal__', label: 'Internal Perusahaan' },
                    ...opsiDari(penyedia, (p) => p.Nama),
                  ]}
                  placeholder="Pilih Rekanan"
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="TanggalKalibrasi">Tanggal Kalibrasi *</Label>
                <DatePicker
                  value={form.data.TanggalKalibrasi}
                  onChange={(nilai) => form.setData('TanggalKalibrasi', nilai)}
                  id="TanggalKalibrasi"
                  className="h-9 text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="DilaksanakanOleh">Teknisi / Pelaksana Internal</Label>
                <Combobox
                  nilai={form.data.DilaksanakanOleh}
                  onPilih={(val) => form.setData('DilaksanakanOleh', val)}
                  opsi={opsiDari(teknisi, (t) => t.Nama)}
                  placeholder="Pilih Pelaksana"
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="Laboratorium">Nama Laboratorium Uji / Lokasi Kalibrasi</Label>
              <Input
                id="Laboratorium"
                placeholder="mis. Lab Metrologi Internal / Balai Kalibrasi Nasional"
                value={form.data.Laboratorium}
                onChange={(e) => form.setData('Laboratorium', e.target.value)}
                className="h-9 text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="Catatan">Catatan / Instruksi Khusus</Label>
              <Textarea
                id="Catatan"
                placeholder="mis. Pastikan kondisi suhu ruang stabil pada 20 ± 2 °C sebelum pengujian."
                rows={2}
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setBukaDialog(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Menjadwalkan...' : 'Jadwalkan Kalibrasi'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </KerangkaAplikasi>
  );
}
