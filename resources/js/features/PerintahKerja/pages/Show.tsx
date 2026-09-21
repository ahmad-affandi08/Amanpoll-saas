import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { DatePicker } from '@/components/ui/date-picker';
import { Textarea } from '@/components/ui/textarea';
import type {
  KodeKegagalan,
  PenugasanPerintahKerjaItem,
  PerintahKerja,
  StatusPerintahKerja,
} from '@/features/PerintahKerja/types';
import {
  VARIAN_PRIORITAS_PERINTAH_KERJA,
  VARIAN_STATUS_PERINTAH_KERJA,
} from '@/features/PerintahKerja/status';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';

interface TeknisiOpsi {
  Id: string;
  Nama: string;
  Jabatan: string | null;
  BebanAktif: number;
}

interface StokOpsi {
  GudangId: string;
  NamaGudang: string | null;
  SukuCadangId: string;
  NamaSukuCadang: string | null;
  KodeSukuCadang: string | null;
  TersediaBersih: number;
}

interface GudangOpsi {
  Id: string;
  Nama: string;
}

interface PenyediaOpsi {
  Id: string;
  Nama: string;
}

interface Props {
  perintahKerja: PerintahKerja;
  dapatMengelola: boolean;
  dapatMengoperasikan: boolean;
  transisiDiizinkan: StatusPerintahKerja[];
  penugasanSaya: PenugasanPerintahKerjaItem | null;
  teknisi: TeknisiOpsi[];
  stok: StokOpsi[];
  gudang: GudangOpsi[];
  penyedia: PenyediaOpsi[];
  kodeKegagalan: KodeKegagalan[];
}

function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(nilai);
}

// 1. DIALOG UBAH STATUS
function DialogUbahStatus({
  perintahKerja,
  transisi,
}: {
  perintahKerja: PerintahKerja;
  transisi: StatusPerintahKerja[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: (transisi[0] ?? perintahKerja.Status) as StatusPerintahKerja,
    Catatan: '',
    RingkasanPenyelesaian: perintahKerja.RingkasanPenyelesaian ?? '',
    Versi: perintahKerja.Versi,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(rutePerintahKerja.status(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  const butuhRingkasan = form.data.Status === 'Selesai' || form.data.Status === 'Ditutup';

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button disabled={transisi.length === 0} className="cursor-pointer">
          Ubah Status
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Ubah Status Perintah Kerja</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Status Baru</Label>
            <Select
              value={form.data.Status}
              onValueChange={(val) => form.setData('Status', val as StatusPerintahKerja)}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {transisi.map((s) => (
                  <SelectItem key={s} value={s} className="cursor-pointer">
                    {s}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.Status && <p className="text-sm text-destructive">{form.errors.Status}</p>}
          </div>

          {butuhRingkasan && (
            <div className="space-y-1.5">
              <Label>Ringkasan Penyelesaian Pekerjaan</Label>
              <Textarea
                rows={3}
                value={form.data.RingkasanPenyelesaian}
                onChange={(e) => form.setData('RingkasanPenyelesaian', e.target.value)}
                placeholder="Rangkum hasil perbaikan, penggantian komponen, atau pengujian yang dilakukan..."
              />
              {form.errors.RingkasanPenyelesaian && (
                <p className="text-sm text-destructive">{form.errors.RingkasanPenyelesaian}</p>
              )}
            </div>
          )}

          <div className="space-y-1.5">
            <Label>Catatan Perubahan Status</Label>
            <Textarea
              rows={3}
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
              placeholder="Catatan opsional mengenai status baru ini..."
            />
            {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing} className="cursor-pointer">
              Simpan Perubahan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// 2. DIALOG PENUGASAN TEKNISI
function DialogTugaskanTeknisi({
  perintahKerja,
  teknisi,
}: {
  perintahKerja: PerintahKerja;
  teknisi: TeknisiOpsi[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    PenggunaIds: [] as string[],
    PeranTugas: 'Anggota',
    GantiPenugasanAktif: false,
  });

  const toggleTeknisi = (id: string) => {
    if (form.data.PenggunaIds.includes(id)) {
      form.setData(
        'PenggunaIds',
        form.data.PenggunaIds.filter((item) => item !== id),
      );
    } else {
      form.setData('PenggunaIds', [...form.data.PenggunaIds, id]);
    }
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.penugasan(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Tugaskan Teknisi
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Tugaskan Teknisi</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Peran Tugas</Label>
            <Select value={form.data.PeranTugas} onValueChange={(val) => form.setData('PeranTugas', val)}>
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Ketua" className="cursor-pointer">
                  Ketua Tim
                </SelectItem>
                <SelectItem value="Anggota" className="cursor-pointer">
                  Anggota Teknisi
                </SelectItem>
                <SelectItem value="Spesialis" className="cursor-pointer">
                  Spesialis / Vendor
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <Label>Pilih Teknisi</Label>
            <div className="max-h-48 overflow-y-auto rounded-md border border-input p-2 space-y-1">
              {teknisi.map((t) => {
                const dipilih = form.data.PenggunaIds.includes(t.Id);
                return (
                  <label
                    key={t.Id}
                    className="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-muted cursor-pointer"
                  >
                    <div className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={dipilih}
                        onChange={() => toggleTeknisi(t.Id)}
                        className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
                      />
                      <span className="font-medium">{t.Nama}</span>
                      {t.Jabatan && <span className="text-xs text-muted-foreground">({t.Jabatan})</span>}
                    </div>
                    <Badge variant={t.BebanAktif > 2 ? 'perhatian' : 'netral'}>
                      {t.BebanAktif} tugas aktif
                    </Badge>
                  </label>
                );
              })}
            </div>
            {form.errors.PenggunaIds && <p className="text-sm text-destructive">{form.errors.PenggunaIds}</p>}
          </div>

          <label className="flex items-center gap-2 text-sm cursor-pointer pt-1">
            <input
              type="checkbox"
              checked={form.data.GantiPenugasanAktif}
              onChange={(e) => form.setData('GantiPenugasanAktif', e.target.checked)}
              className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
            />
            Gantikan penugasan aktif sebelumnya
          </label>

          <DialogFooter>
            <Button
              type="submit"
              disabled={form.processing || form.data.PenggunaIds.length === 0}
              className="cursor-pointer"
            >
              Simpan Penugasan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// 3. DIALOG RESERVASI SUKU CADANG
function DialogReservasiSukuCadang({
  perintahKerja,
  stok,
}: {
  perintahKerja: PerintahKerja;
  stok: StokOpsi[];
}) {
  const [buka, setBuka] = useState(false);
  const [kombinasiPilihan, setKombinasiPilihan] = useState('');
  const form = useForm({
    SukuCadangId: '',
    GudangId: '',
    Jumlah: 1,
    Catatan: '',
  });

  const tanganiPilihStok = (val: string) => {
    setKombinasiPilihan(val);
    const [gudangId, sukuCadangId] = val.split(':');
    form.setData({
      ...form.data,
      GudangId: gudangId,
      SukuCadangId: sukuCadangId,
    });
  };

  const stokTerpilih = stok.find(
    (s) => s.GudangId === form.data.GudangId && s.SukuCadangId === form.data.SukuCadangId,
  );

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.reservasiSukuCadang(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
        setKombinasiPilihan('');
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Reservasi Suku Cadang
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Reservasi Suku Cadang</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Pilih Suku Cadang & Gudang</Label>
            <Select value={kombinasiPilihan} onValueChange={tanganiPilihStok}>
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue placeholder="Pilih suku cadang tersedia" />
              </SelectTrigger>
              <SelectContent>
                {stok.map((s) => (
                  <SelectItem
                    key={`${s.GudangId}:${s.SukuCadangId}`}
                    value={`${s.GudangId}:${s.SukuCadangId}`}
                    className="cursor-pointer"
                  >
                    {s.KodeSukuCadang} · {s.NamaSukuCadang} ({s.NamaGudang} - sisa {s.TersediaBersih})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.SukuCadangId && (
              <p className="text-sm text-destructive">{form.errors.SukuCadangId}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label>Jumlah Dibutuhkan</Label>
            <Input
              type="number"
              min={1}
              max={stokTerpilih?.TersediaBersih ?? 9999}
              value={form.data.Jumlah}
              onChange={(e) => form.setData('Jumlah', Number(e.target.value))}
            />
            {stokTerpilih && (
              <p className="text-xs text-muted-foreground">
                Tersedia bersih di gudang: {stokTerpilih.TersediaBersih} unit
              </p>
            )}
            {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Catatan (Opsional)</Label>
            <Input
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
              placeholder="Misal: untuk penggantian bearing motor A"
            />
          </div>

          <DialogFooter>
            <Button
              type="submit"
              disabled={form.processing || !form.data.SukuCadangId}
              className="cursor-pointer"
            >
              Simpan Reservasi
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// 4. DIALOG CATAT BIAYA
function DialogCatatBiaya({ perintahKerja }: { perintahKerja: PerintahKerja }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    JenisBiaya: 'Vendor',
    Deskripsi: '',
    Jumlah: 0,
    MataUang: 'IDR',
    TanggalBiaya: new Date().toISOString().slice(0, 10),
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.biaya(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Catat Biaya
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Biaya Pekerjaan</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Jenis Biaya</Label>
            <Select value={form.data.JenisBiaya} onValueChange={(val) => form.setData('JenisBiaya', val)}>
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Vendor" className="cursor-pointer">
                  Jasa Vendor / Eksternal
                </SelectItem>
                <SelectItem value="TenagaKerja" className="cursor-pointer">
                  Tenaga Kerja
                </SelectItem>
                <SelectItem value="Sparepart" className="cursor-pointer">
                  Suku Cadang
                </SelectItem>
                <SelectItem value="Lainnya" className="cursor-pointer">
                  Biaya Lainnya
                </SelectItem>
              </SelectContent>
            </Select>
            {form.errors.JenisBiaya && <p className="text-sm text-destructive">{form.errors.JenisBiaya}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Deskripsi Biaya</Label>
            <Input
              value={form.data.Deskripsi}
              onChange={(e) => form.setData('Deskripsi', e.target.value)}
              placeholder="Contoh: Jasa teknisi rewinding motor dinamo"
            />
            {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Nominal (IDR)</Label>
              <Input
                type="number"
                min={0}
                value={form.data.Jumlah}
                onChange={(e) => form.setData('Jumlah', Number(e.target.value))}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>

            <div className="space-y-1.5">
              <Label>Tanggal Biaya</Label>
              <DatePicker
                value={form.data.TanggalBiaya}
                onChange={(val) => form.setData('TanggalBiaya', val)}
                placeholder="Pilih tanggal biaya..."
              />
            </div>
          </div>

          <DialogFooter>
            <Button
              type="submit"
              disabled={form.processing || form.data.Jumlah <= 0}
              className="cursor-pointer"
            >
              Simpan Biaya
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// 5. DIALOG ANALISIS KEGAGALAN
function DialogAnalisisKegagalan({
  perintahKerja,
  kodeKegagalan,
}: {
  perintahKerja: PerintahKerja;
  kodeKegagalan: KodeKegagalan[];
}) {
  const [buka, setBuka] = useState(false);
  const analisis = perintahKerja.AnalisisKegagalan;

  const form = useForm({
    KodeMasalahId: analisis?.KodeMasalahId ?? '',
    KodePenyebabId: analisis?.KodePenyebabId ?? '',
    KodeTindakanId: analisis?.KodeTindakanId ?? '',
    AkarMasalah: analisis?.AkarMasalah ?? '',
    TindakanKorektif: analisis?.TindakanKorektif ?? '',
    TindakanPencegahan: analisis?.TindakanPencegahan ?? '',
  });

  const daftarMasalah = kodeKegagalan.filter((k) => k.Jenis === 'Masalah');
  const daftarPenyebab = kodeKegagalan.filter((k) => k.Jenis === 'Penyebab');
  const daftarTindakan = kodeKegagalan.filter((k) => k.Jenis === 'Tindakan');

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KodeMasalahId: data.KodeMasalahId || null,
      KodePenyebabId: data.KodePenyebabId || null,
      KodeTindakanId: data.KodeTindakanId || null,
    }));
    form.put(rutePerintahKerja.analisisKegagalan(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          {analisis ? 'Edit Analisis' : 'Isi Analisis Kegagalan'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Analisis Kegagalan (Problem / Cause / Remedy)</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Kode Masalah (Problem)</Label>
            <Select
              value={form.data.KodeMasalahId}
              onValueChange={(val) => form.setData('KodeMasalahId', val)}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue placeholder="Pilih kode masalah" />
              </SelectTrigger>
              <SelectContent>
                {daftarMasalah.map((k) => (
                  <SelectItem key={k.Id} value={k.Id} className="cursor-pointer">
                    {k.Kode} · {k.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <Label>Kode Penyebab (Cause)</Label>
            <Select
              value={form.data.KodePenyebabId}
              onValueChange={(val) => form.setData('KodePenyebabId', val)}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue placeholder="Pilih kode penyebab" />
              </SelectTrigger>
              <SelectContent>
                {daftarPenyebab.map((k) => (
                  <SelectItem key={k.Id} value={k.Id} className="cursor-pointer">
                    {k.Kode} · {k.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <Label>Kode Tindakan (Remedy)</Label>
            <Select
              value={form.data.KodeTindakanId}
              onValueChange={(val) => form.setData('KodeTindakanId', val)}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue placeholder="Pilih kode tindakan" />
              </SelectTrigger>
              <SelectContent>
                {daftarTindakan.map((k) => (
                  <SelectItem key={k.Id} value={k.Id} className="cursor-pointer">
                    {k.Kode} · {k.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <Label>Akar Masalah (Root Cause)</Label>
            <Textarea
              rows={2}
              value={form.data.AkarMasalah}
              onChange={(e) => form.setData('AkarMasalah', e.target.value)}
              placeholder="Uraian akar penyebab fisik, manusia, atau laten..."
            />
          </div>

          <div className="space-y-1.5">
            <Label>Tindakan Korektif</Label>
            <Textarea
              rows={2}
              value={form.data.TindakanKorektif}
              onChange={(e) => form.setData('TindakanKorektif', e.target.value)}
              placeholder="Tindakan yang telah dilakukan untuk memulihkan aset..."
            />
          </div>

          <div className="space-y-1.5">
            <Label>Tindakan Pencegahan (Preventive Action)</Label>
            <Textarea
              rows={2}
              value={form.data.TindakanPencegahan}
              onChange={(e) => form.setData('TindakanPencegahan', e.target.value)}
              placeholder="Rekomendasi inspeksi berkala, penggantian pelumas, atau SOP..."
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing} className="cursor-pointer">
              Simpan Analisis
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// 6. DIALOG DOWNTIME ASET
function DialogDowntimeAset({ perintahKerja }: { perintahKerja: PerintahKerja }) {
  const [buka, setBuka] = useState(false);
  const asetUtama = perintahKerja.Aset[0]?.Id ?? '';

  const form = useForm({
    AsetId: asetUtama,
    Aksi: 'Mulai' as 'Mulai' | 'Selesai',
    Jenis: 'TidakTerencana',
    Alasan: '',
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(rutePerintahKerja.waktuHenti(perintahKerja.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="cursor-pointer">
          Catat Downtime Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Downtime / Penghentian Aset</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Aset</Label>
            <Select value={form.data.AsetId} onValueChange={(val) => form.setData('AsetId', val)}>
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {perintahKerja.Aset.map((a) => (
                  <SelectItem key={a.Id} value={a.Id} className="cursor-pointer">
                    {a.KodeAset} · {a.Nama} {a.Utama && '(Utama)'}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <Label>Aksi Downtime</Label>
            <Select
              value={form.data.Aksi}
              onValueChange={(val) => form.setData('Aksi', val as 'Mulai' | 'Selesai')}
            >
              <SelectTrigger className="w-full cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Mulai" className="cursor-pointer">
                  Mulai Penghentian Mesin (Downtime Mulai)
                </SelectItem>
                <SelectItem value="Selesai" className="cursor-pointer">
                  Akhiri Penghentian Mesin (Mesin Kembali Beroperasi)
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          {form.data.Aksi === 'Mulai' && (
            <div className="space-y-1.5">
              <Label>Jenis Downtime</Label>
              <Select value={form.data.Jenis} onValueChange={(val) => form.setData('Jenis', val)}>
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="TidakTerencana" className="cursor-pointer">
                    Tidak Terencana (Breakdown / Kerusakan)
                  </SelectItem>
                  <SelectItem value="Terencana" className="cursor-pointer">
                    Terencana (Overhaul / Servis Rutin)
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          )}

          <div className="space-y-1.5">
            <Label>Alasan / Keterangan</Label>
            <Input
              value={form.data.Alasan}
              onChange={(e) => form.setData('Alasan', e.target.value)}
              placeholder="Contoh: Bearing macet, overheat, perbaikan elektrikal..."
            />
            {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing} className="cursor-pointer">
              Simpan Downtime
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

// MAIN PAGE COMPONENT
export default function PerintahKerjaShow({
  perintahKerja,
  dapatMengelola,
  dapatMengoperasikan,
  transisiDiizinkan,
  penugasanSaya,
  teknisi,
  stok,
  kodeKegagalan,
}: Props) {
  // Aksi respon penugasan teknisi
  const formResponsPenugasan = useForm({
    Respons: 'Terima' as 'Terima' | 'Tolak',
    Catatan: '',
  });

  const tanganiResponsPenugasan = (respons: 'Terima' | 'Tolak') => {
    if (!penugasanSaya) return;
    formResponsPenugasan.setData('Respons', respons);
    formResponsPenugasan.post(rutePerintahKerja.penugasanRespons(perintahKerja.Id, penugasanSaya.Id), {
      preserveScroll: true,
    });
  };

  // Aksi waktu kerja
  const formWaktuKerja = useForm({
    Aksi: 'Mulai' as 'Mulai' | 'Jeda' | 'Lanjut' | 'Selesai',
    Catatan: '',
  });

  const tanganiWaktuKerja = (aksi: 'Mulai' | 'Jeda' | 'Lanjut' | 'Selesai') => {
    formWaktuKerja.setData('Aksi', aksi);
    formWaktuKerja.post(rutePerintahKerja.waktuKerja(perintahKerja.Id), {
      preserveScroll: true,
    });
  };

  // Aksi konsumsi / kembalikan suku cadang
  const formSukuCadang = useForm({
    ReservasiSukuCadangId: '',
    Aksi: 'Pakai' as 'Pakai' | 'Kembalikan',
  });

  const tanganiAksiSukuCadang = (reservasiId: string, aksi: 'Pakai' | 'Kembalikan') => {
    formSukuCadang.setData({
      ReservasiSukuCadangId: reservasiId,
      Aksi: aksi,
    });
    formSukuCadang.post(rutePerintahKerja.sukuCadang(perintahKerja.Id), {
      preserveScroll: true,
    });
  };

  // Cek apakah ada sesi kerja aktif
  const sesiKerjaAktif = perintahKerja.WaktuKerja?.find((w) => w.SelesaiPada === null);
  const waktuHentiAktif = perintahKerja.WaktuHenti?.find((h) => h.SelesaiPada === null);

  return (
    <AppLayout>
      <Head title={`${perintahKerja.Nomor} - Perintah Kerja`} />

      <div className="mb-5">
        <Link
          href={rutePerintahKerja.index}
          className="text-sm text-muted-foreground hover:text-foreground cursor-pointer"
        >
          ← Kembali ke Daftar Perintah Kerja
        </Link>
      </div>

      {/* HEADER SECTION */}
      <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <span className="font-mono text-sm font-semibold text-muted-foreground">
              {perintahKerja.Nomor}
            </span>
            <Badge variant="outline">{perintahKerja.Jenis}</Badge>
            <Badge variant={VARIAN_PRIORITAS_PERINTAH_KERJA[perintahKerja.Prioritas]}>
              {perintahKerja.Prioritas}
            </Badge>
            <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[perintahKerja.Status]}>{perintahKerja.Status}</Badge>
            {perintahKerja.NomorKeluhan && (
              <Badge variant="info">Keluhan: {perintahKerja.NomorKeluhan}</Badge>
            )}
          </div>
          <h1 className="mt-2 text-2xl font-semibold tracking-tight text-foreground">
            {perintahKerja.Judul}
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Lokasi: {perintahKerja.NamaLokasi ?? '—'} · Persentase Selesai: {perintahKerja.PersentaseSelesai}%
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {/* Action Terima / Tolak Penugasan Saya */}
          {penugasanSaya && penugasanSaya.Status === 'Ditugaskan' && (
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                onClick={() => tanganiResponsPenugasan('Terima')}
                className="cursor-pointer bg-sukses-600 hover:bg-sukses-700 text-white"
              >
                Terima Tugas
              </Button>
              <Button
                size="sm"
                variant="destructive"
                onClick={() => tanganiResponsPenugasan('Tolak')}
                className="cursor-pointer"
              >
                Tolak
              </Button>
            </div>
          )}

          {/* Dialog Ubah Status */}
          <DialogUbahStatus perintahKerja={perintahKerja} transisi={transisiDiizinkan} />
        </div>
      </div>

      {/* BANNER NOTIFIKASI SESI AKTIF */}
      {(sesiKerjaAktif || waktuHentiAktif) && (
        <div className="mb-6 grid gap-3 sm:grid-cols-2">
          {sesiKerjaAktif && (
            <div className="flex items-center justify-between rounded-lg border border-teknisi-600/30 bg-teknisi-600/10 p-3 text-sm">
              <div>
                <span className="font-semibold text-teknisi-700">Sesi Kerja Berjalan: </span>
                <span>
                  {sesiKerjaAktif.NamaPengguna ?? 'Teknisi'} sejak {formatTanggal(sesiKerjaAktif.MulaiPada)}
                </span>
              </div>
              {dapatMengoperasikan && (
                <div className="flex gap-1.5">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => tanganiWaktuKerja('Jeda')}
                    className="cursor-pointer h-7 text-xs"
                  >
                    Jeda
                  </Button>
                  <Button
                    size="sm"
                    onClick={() => tanganiWaktuKerja('Selesai')}
                    className="cursor-pointer h-7 text-xs bg-teknisi-700 text-white"
                  >
                    Selesai Kerja
                  </Button>
                </div>
              )}
            </div>
          )}

          {waktuHentiAktif && (
            <div className="flex items-center justify-between rounded-lg border border-bahaya-600/30 bg-bahaya-600/10 p-3 text-sm">
              <div>
                <span className="font-semibold text-bahaya-600">Downtime Aktif: </span>
                <span>
                  {waktuHentiAktif.NamaAset ?? 'Aset'} ({waktuHentiAktif.Alasan})
                </span>
              </div>
            </div>
          )}
        </div>
      )}

      {/* MAIN TWO COLUMN LAYOUT */}
      <div className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        {/* KOLOM UTAMA */}
        <div className="space-y-6">
          {/* DETAIL & INSTRUKSI */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <CardTitle className="text-base font-semibold">Instruksi & Lingkup Pekerjaan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="whitespace-pre-wrap text-sm leading-relaxed text-foreground">
                {perintahKerja.Deskripsi || 'Tidak ada catatan deskripsi pekerjaan.'}
              </p>

              {perintahKerja.RingkasanPenyelesaian && (
                <div className="rounded-md border border-sukses-600/20 bg-sukses-600/5 p-3">
                  <div className="text-xs font-semibold text-sukses-600 uppercase tracking-wider">
                    Ringkasan Penyelesaian
                  </div>
                  <p className="mt-1 text-sm text-foreground">{perintahKerja.RingkasanPenyelesaian}</p>
                </div>
              )}

              <div className="grid gap-4 border-t border-border pt-4 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground block text-xs">Jadwal Mulai</span>
                  <span className="font-medium">{formatTanggal(perintahKerja.DijadwalkanMulaiPada)}</span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Jadwal Selesai</span>
                  <span className="font-medium">{formatTanggal(perintahKerja.DijadwalkanSelesaiPada)}</span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Downtime Mesin</span>
                  <span className="font-medium">
                    {perintahKerja.MembutuhkanWaktuHenti ? 'Diperlukan' : 'Tidak diperlukan'}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Persetujuan Hasil</span>
                  <span className="font-medium">
                    {perintahKerja.MembutuhkanPersetujuan ? 'Diperlukan' : 'Tidak diperlukan'}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* DAFTAR ASET TERKAIT */}
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base font-semibold">Aset yang Ditangani</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="divide-y divide-border">
                {perintahKerja.Aset?.map((aset) => (
                  <div key={aset.Id} className="flex items-center justify-between py-2.5 text-sm">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono text-xs font-semibold text-muted-foreground">
                          {aset.KodeAset}
                        </span>
                        <span className="font-medium">{aset.Nama}</span>
                        {aset.Utama && <Badge variant="secondary">Aset Utama</Badge>}
                      </div>
                      <div className="text-xs text-muted-foreground mt-0.5">
                        Kondisi Awal: {aset.KondisiAwal ?? '—'} · Kondisi Akhir: {aset.KondisiAkhir ?? '—'}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* SUKU CADANG & BAHAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Suku Cadang & Bahan</CardTitle>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Reservasi suku cadang dari gudang dan catat pemakaian aktualnya.
                </p>
              </div>
              {dapatMengoperasikan && <DialogReservasiSukuCadang perintahKerja={perintahKerja} stok={stok} />}
            </CardHeader>
            <CardContent className="space-y-4">
              {/* TABEL RESERVASI */}
              <div>
                <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                  Daftar Reservasi
                </h4>
                {perintahKerja.ReservasiSukuCadang && perintahKerja.ReservasiSukuCadang.length > 0 ? (
                  <div className="overflow-x-auto rounded border border-border">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-muted/40 text-muted-foreground border-b border-border">
                        <tr>
                          <th className="px-3 py-2">Suku Cadang</th>
                          <th className="px-3 py-2">Gudang</th>
                          <th className="px-3 py-2">Jumlah</th>
                          <th className="px-3 py-2">Status</th>
                          <th className="px-3 py-2 text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {perintahKerja.ReservasiSukuCadang.map((res) => (
                          <tr key={res.Id}>
                            <td className="px-3 py-2 font-medium">{res.NamaSukuCadang ?? '—'}</td>
                            <td className="px-3 py-2 text-muted-foreground">{res.NamaGudang ?? '—'}</td>
                            <td className="px-3 py-2">{res.Jumlah} unit</td>
                            <td className="px-3 py-2">
                              <Badge variant={res.Status === 'Disetujui' ? 'sukses' : 'perhatian'}>
                                {res.Status}
                              </Badge>
                            </td>
                            <td className="px-3 py-2 text-right">
                              {dapatMengoperasikan && res.Status !== 'Dipakai' && (
                                <div className="flex justify-end gap-1.5">
                                  <Button
                                    size="sm"
                                    onClick={() => tanganiAksiSukuCadang(res.Id, 'Pakai')}
                                    className="cursor-pointer h-6 px-2 text-[11px] bg-teknisi-700 text-white"
                                  >
                                    Pakai
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => tanganiAksiSukuCadang(res.Id, 'Kembalikan')}
                                    className="cursor-pointer h-6 px-2 text-[11px]"
                                  >
                                    Kembalikan
                                  </Button>
                                </div>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground italic">
                    Belum ada reservasi suku cadang untuk pekerjaan ini.
                  </p>
                )}
              </div>

              {/* PEMAKAIAN AKTUAL */}
              {perintahKerja.PemakaianSukuCadang && perintahKerja.PemakaianSukuCadang.length > 0 && (
                <div className="pt-2">
                  <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                    Pemakaian Aktual & Biaya
                  </h4>
                  <div className="overflow-x-auto rounded border border-border">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-muted/40 text-muted-foreground border-b border-border">
                        <tr>
                          <th className="px-3 py-2">Suku Cadang</th>
                          <th className="px-3 py-2">Jumlah</th>
                          <th className="px-3 py-2">Harga Satuan</th>
                          <th className="px-3 py-2">Total</th>
                          <th className="px-3 py-2">Waktu Pemakaian</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {perintahKerja.PemakaianSukuCadang.map((pem) => (
                          <tr key={pem.Id}>
                            <td className="px-3 py-2 font-medium">{pem.NamaSukuCadang ?? '—'}</td>
                            <td className="px-3 py-2">{pem.Jumlah} unit</td>
                            <td className="px-3 py-2">{formatRupiah(pem.HargaSatuan)}</td>
                            <td className="px-3 py-2 font-medium">
                              {formatRupiah(pem.Jumlah * pem.HargaSatuan)}
                            </td>
                            <td className="px-3 py-2 text-muted-foreground">
                              {formatTanggal(pem.DipakaiPada)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* ANALISIS KEGAGALAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Analisis Kegagalan & Akar Masalah</CardTitle>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Standar taksonomi Problem-Cause-Remedy untuk evaluasi keandalan aset.
                </p>
              </div>
              {dapatMengoperasikan && (
                <DialogAnalisisKegagalan perintahKerja={perintahKerja} kodeKegagalan={kodeKegagalan} />
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.AnalisisKegagalan ? (
                <dl className="grid gap-3 text-sm sm:grid-cols-3">
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">Akar Masalah</dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.AkarMasalah || '—'}
                    </dd>
                  </div>
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">
                      Tindakan Korektif
                    </dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.TindakanKorektif || '—'}
                    </dd>
                  </div>
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">
                      Tindakan Pencegahan
                    </dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.TindakanPencegahan || '—'}
                    </dd>
                  </div>
                </dl>
              ) : (
                <p className="text-xs text-muted-foreground italic">
                  Belum ada analisis kegagalan yang dicatat untuk pekerjaan ini.
                </p>
              )}
            </CardContent>
          </Card>

          {/* PANEL KOLABORASI (Berkas, Komentar, Tag, Kolom Kustom) */}
          <PanelKolaborasi jenisEntitas="PerintahKerja" entitasId={perintahKerja.Id} />
        </div>

        {/* KOLOM KANAN / SIDEBAR */}
        <div className="space-y-6">
          {/* PENUGASAN TEKNISI */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <CardTitle className="text-base font-semibold">Penugasan Teknisi</CardTitle>
              {dapatMengelola && <DialogTugaskanTeknisi perintahKerja={perintahKerja} teknisi={teknisi} />}
            </CardHeader>
            <CardContent>
              {perintahKerja.Penugasan && perintahKerja.Penugasan.length > 0 ? (
                <ul className="divide-y divide-border">
                  {perintahKerja.Penugasan.map((t) => (
                    <li key={t.Id} className="flex items-center justify-between py-2 text-sm">
                      <div>
                        <div className="font-medium">{t.NamaPengguna ?? 'Teknisi'}</div>
                        <div className="text-xs text-muted-foreground">
                          Peran: {t.PeranTugas} · Ditugaskan {formatTanggal(t.DitugaskanPada)}
                        </div>
                      </div>
                      <Badge
                        variant={
                          t.Status === 'Diterima'
                            ? 'sukses'
                            : t.Status === 'Ditolak'
                              ? 'destructive'
                              : 'perhatian'
                        }
                      >
                        {t.Status}
                      </Badge>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada teknisi ditugaskan.</p>
              )}
            </CardContent>
          </Card>

          {/* WAKTU KERJA / PENCATATAN JAM */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Waktu Kerja</CardTitle>
                <div className="text-xs font-semibold text-teknisi-700 mt-0.5">
                  Total: {perintahKerja.TotalWaktuKerjaMenit ?? 0} Menit
                </div>
              </div>
              {dapatMengoperasikan && !sesiKerjaAktif && (
                <Button
                  size="sm"
                  onClick={() => tanganiWaktuKerja('Mulai')}
                  className="cursor-pointer bg-teknisi-700 text-white h-7 text-xs"
                >
                  Mulai Kerja
                </Button>
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.WaktuKerja && perintahKerja.WaktuKerja.length > 0 ? (
                <div className="space-y-2 text-xs">
                  {perintahKerja.WaktuKerja.map((w) => (
                    <div
                      key={w.Id}
                      className="rounded border border-border p-2 flex justify-between items-center"
                    >
                      <div>
                        <span className="font-medium block">{w.NamaPengguna ?? 'Teknisi'}</span>
                        <span className="text-muted-foreground">
                          {formatTanggal(w.MulaiPada)} →{' '}
                          {w.SelesaiPada ? formatTanggal(w.SelesaiPada) : 'Berjalan'}
                        </span>
                      </div>
                      <span className="font-mono font-medium">
                        {w.DurasiMenit ? `${w.DurasiMenit}m` : 'Aktif'}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada jam kerja tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* DOWNTIME ASET */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Downtime Aset</CardTitle>
                <div className="text-xs font-semibold text-bahaya-600 mt-0.5">
                  Total: {perintahKerja.TotalDowntimeMenit ?? 0} Menit
                </div>
              </div>
              {dapatMengoperasikan && <DialogDowntimeAset perintahKerja={perintahKerja} />}
            </CardHeader>
            <CardContent>
              {perintahKerja.WaktuHenti && perintahKerja.WaktuHenti.length > 0 ? (
                <div className="space-y-2 text-xs">
                  {perintahKerja.WaktuHenti.map((h) => (
                    <div
                      key={h.Id}
                      className="rounded border border-border p-2 flex justify-between items-center"
                    >
                      <div>
                        <span className="font-medium block">{h.NamaAset ?? 'Aset'}</span>
                        <span className="text-muted-foreground">
                          {h.Jenis} · {h.Alasan}
                        </span>
                      </div>
                      <span className="font-mono font-medium">
                        {h.DurasiMenit ? `${h.DurasiMenit}m` : 'Mati'}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground italic">Tidak ada downtime mesin tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* BIAYA PEKERJAAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Biaya Pekerjaan</CardTitle>
                <div className="text-xs font-semibold text-foreground mt-0.5">
                  Total: {formatRupiah(perintahKerja.TotalBiaya ?? 0)}
                </div>
              </div>
              {dapatMengelola && <DialogCatatBiaya perintahKerja={perintahKerja} />}
            </CardHeader>
            <CardContent>
              {perintahKerja.Biaya && perintahKerja.Biaya.length > 0 ? (
                <ul className="divide-y divide-border text-xs">
                  {perintahKerja.Biaya.map((b) => (
                    <li key={b.Id} className="py-2 flex justify-between items-center">
                      <div>
                        <span className="font-medium block">{b.Deskripsi}</span>
                        <span className="text-muted-foreground">{b.JenisBiaya}</span>
                      </div>
                      <span className="font-medium">{formatRupiah(b.Jumlah)}</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada biaya tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* RIWAYAT STATUS */}
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base font-semibold">Riwayat Status</CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="space-y-4">
                {perintahKerja.RiwayatStatus?.map((riwayat) => (
                  <li key={riwayat.Id} className="relative border-l-2 border-border pl-4">
                    <div className="font-medium text-sm text-foreground">{riwayat.StatusSesudah}</div>
                    <div className="text-xs text-muted-foreground">
                      {formatTanggal(riwayat.DiubahPada)} · {riwayat.NamaPengubah ?? 'Sistem'}
                    </div>
                    {riwayat.Catatan && (
                      <p className="mt-1 text-xs text-muted-foreground">{riwayat.Catatan}</p>
                    )}
                  </li>
                ))}
              </ol>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
