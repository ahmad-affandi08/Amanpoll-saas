import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Ban, Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type { Kontrak, LayananKontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import { formatUang } from '@/lib/uang';

interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
interface Props {
  kontrak: Kontrak;
  aset: AsetRingkas[];
}

const VARIAN_STATUS = { Aktif: 'sukses', Berakhir: 'netral', Dibatalkan: 'bahaya' } as const;

function DialogTambahAset({ kontrak, aset }: Props) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    AsetId: '',
    MulaiPada: kontrak.MulaiPada,
    BerakhirPada: kontrak.BerakhirPada,
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, Catatan: data.Catatan || null }));
    form.post(ruteKontrak.aset(kontrak.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" className="min-h-11 sm:min-h-9">
          <Plus /> Tambah Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Cakupan Aset</DialogTitle>
          <DialogDescription>
            Periode cakupan harus berada di dalam periode kontrak {kontrak.MulaiPada} s.d.{' '}
            {kontrak.BerakhirPada}.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Aset</Label>
            <Select value={form.data.AsetId} onValueChange={(value) => form.setData('AsetId', value)}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih aset" />
              </SelectTrigger>
              <SelectContent>
                {aset.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.KodeAset} — {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="MulaiCakupan">Mulai</Label>
              <Input
                id="MulaiCakupan"
                type="date"
                value={form.data.MulaiPada}
                onChange={(event) => form.setData('MulaiPada', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="BerakhirCakupan">Berakhir</Label>
              <Input
                id="BerakhirCakupan"
                type="date"
                value={form.data.BerakhirPada}
                onChange={(event) => form.setData('BerakhirPada', event.target.value)}
              />
              {form.errors.BerakhirPada && (
                <p className="text-sm text-destructive">{form.errors.BerakhirPada}</p>
              )}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="CatatanCakupan">Catatan</Label>
            <Input
              id="CatatanCakupan"
              value={form.data.Catatan}
              onChange={(event) => form.setData('Catatan', event.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Tambahkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogTambahLayanan({ kontrak }: { kontrak: Kontrak }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: '', Deskripsi: '', Kuota: '', Satuan: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Deskripsi: data.Deskripsi || null,
      Kuota: data.Kuota || null,
      Satuan: data.Satuan || null,
    }));
    form.post(ruteKontrak.layanan(kontrak.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          <Plus /> Tambah Layanan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Layanan Kontrak</DialogTitle>
          <DialogDescription>
            Isi kuota bila layanan dibatasi; pemakaian yang melampaui kuota akan ditolak server.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="NamaLayanan">Nama Layanan</Label>
            <Input
              id="NamaLayanan"
              value={form.data.Nama}
              onChange={(event) => form.setData('Nama', event.target.value)}
            />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="Kuota">Kuota</Label>
              <Input
                id="Kuota"
                type="number"
                min="0"
                step="0.0001"
                value={form.data.Kuota}
                onChange={(event) => form.setData('Kuota', event.target.value)}
              />
              {form.errors.Kuota && <p className="text-sm text-destructive">{form.errors.Kuota}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="Satuan">Satuan</Label>
              <Input
                id="Satuan"
                placeholder="mis. kunjungan"
                value={form.data.Satuan}
                onChange={(event) => form.setData('Satuan', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="DeskripsiLayanan">Deskripsi</Label>
            <Textarea
              id="DeskripsiLayanan"
              rows={2}
              value={form.data.Deskripsi}
              onChange={(event) => form.setData('Deskripsi', event.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Tambahkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogPemakaian({ kontrak, layanan }: { kontrak: Kontrak; layanan: LayananKontrak }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Jumlah: '1' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKontrak.layananPemakaian(kontrak.Id, layanan.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          Catat Pemakaian
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Pemakaian {layanan.Nama}</DialogTitle>
          <DialogDescription>
            Terpakai {layanan.Terpakai}
            {layanan.Kuota ? ` dari kuota ${layanan.Kuota}` : ''} {layanan.Satuan ?? ''}.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="JumlahPemakaian">Jumlah</Label>
            <Input
              id="JumlahPemakaian"
              type="number"
              min="0.0001"
              step="0.0001"
              value={form.data.Jumlah}
              onChange={(event) => form.setData('Jumlah', event.target.value)}
            />
            {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Catat
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogBatalkan({ kontrak }: { kontrak: Kontrak }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKontrak.batalkan(kontrak.Id), { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="destructive" className="min-h-11 sm:min-h-9">
          <Ban /> Batalkan
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Batalkan kontrak {kontrak.Nomor}?</DialogTitle>
          <DialogDescription>
            Kontrak tidak dapat diaktifkan kembali; cakupan aset dan layanannya tetap tersimpan sebagai
            riwayat.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="AlasanBatal">Alasan pembatalan</Label>
            <Textarea
              id="AlasanBatal"
              rows={3}
              value={form.data.Alasan}
              onChange={(event) => form.setData('Alasan', event.target.value)}
            />
            {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" variant="destructive" disabled={form.processing}>
              Batalkan Kontrak
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KontrakShow({ kontrak, aset }: Props) {
  const konfirmasi = useKonfirmasi();
  const daftarAset = kontrak.Aset ?? [];
  const daftarLayanan = kontrak.Layanan ?? [];
  const aktif = kontrak.Status === 'Aktif';

  async function lepasAset(cakupanId: string, namaAset: string): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Lepas aset "${namaAset}" dari kontrak ini?`,
      deskripsi: 'Pekerjaan vendor pada aset tersebut tidak lagi tertaut ke kontrak ini.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKontrak.asetDetail(kontrak.Id, cakupanId), { preserveScroll: true });
  }

  async function hapusLayanan(layanan: LayananKontrak): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus layanan "${layanan.Nama}"?`,
      deskripsi: 'Layanan yang sudah memiliki pemakaian tidak dapat dihapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKontrak.layananDetail(kontrak.Id, layanan.Id), { preserveScroll: true });
  }

  return (
    <AppLayout>
      <Head title={`${kontrak.Nomor} — Kontrak`} />
      <div className="space-y-6">
        <Link
          href={ruteKontrak.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Kontrak
        </Link>

        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{kontrak.Nama}</h1>
              <Badge variant={VARIAN_STATUS[kontrak.Status]}>{kontrak.Status}</Badge>
            </div>
            <p className="font-mono text-sm text-muted-foreground">
              {kontrak.Nomor} · {kontrak.Jenis} · {kontrak.NamaPenyedia ?? 'Tanpa penyedia'}
            </p>
          </div>
          {aktif && <DialogBatalkan kontrak={kontrak} />}
        </header>

        {aktif && kontrak.SisaHari >= 0 && kontrak.SisaHari <= kontrak.PeringatanHariSebelum && (
          <Alert variant="perhatian">
            <AlertTitle>Kontrak akan berakhir dalam {kontrak.SisaHari} hari</AlertTitle>
            <AlertDescription>
              Berakhir {kontrak.BerakhirPada}. Siapkan perpanjangan atau pengadaan pengganti sebelum tanggal
              tersebut.
            </AlertDescription>
          </Alert>
        )}
        {aktif && kontrak.SisaHari < 0 && (
          <Alert variant="bahaya">
            <AlertTitle>Kontrak sudah lewat masa berlaku</AlertTitle>
            <AlertDescription>
              Berakhir {kontrak.BerakhirPada}. Status akan ditutup otomatis pada pemeriksaan harian
              berikutnya.
            </AlertDescription>
          </Alert>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Ringkasan</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-4">
            <div>
              <p className="text-xs text-muted-foreground">Periode</p>
              <p className="font-medium">
                {kontrak.MulaiPada} s.d. {kontrak.BerakhirPada}
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Nilai</p>
              <p className="font-mono font-medium">
                {kontrak.Nilai ? formatUang(kontrak.Nilai, kontrak.MataUang) : '—'}
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Tingkat layanan</p>
              <p className="font-medium">{kontrak.NamaTingkatLayanan ?? 'Tanpa SLA'}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Peringatan</p>
              <p className="font-medium">H-{kontrak.PeringatanHariSebelum}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Aset Tercakup</CardTitle>
            {aktif && <DialogTambahAset kontrak={kontrak} aset={aset} />}
          </CardHeader>
          <CardContent className="space-y-3">
            {daftarAset.length === 0 ? (
              <EmptyState
                judul="Belum ada aset tercakup."
                deskripsi="Lampirkan aset agar pekerjaan vendor dapat ditelusuri ke kontrak ini."
              />
            ) : (
              daftarAset.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-2 rounded-[9px] border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.NamaAset}</p>
                    <p className="font-mono text-xs text-muted-foreground">
                      {item.KodeAset} · {item.MulaiPada ?? kontrak.MulaiPada} s.d.{' '}
                      {item.BerakhirPada ?? kontrak.BerakhirPada}
                    </p>
                  </div>
                  {aktif && (
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Lepas ${item.NamaAset}`}
                      onClick={() => lepasAset(item.Id, item.NamaAset ?? 'aset ini')}
                    >
                      <Trash2 />
                    </Button>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Layanan</CardTitle>
            {aktif && <DialogTambahLayanan kontrak={kontrak} />}
          </CardHeader>
          <CardContent className="space-y-3">
            {daftarLayanan.length === 0 ? (
              <EmptyState
                judul="Belum ada layanan."
                deskripsi="Daftarkan layanan beserta kuotanya bila kontrak membatasi jumlah pekerjaan."
              />
            ) : (
              daftarLayanan.map((layanan) => (
                <div key={layanan.Id} className="space-y-2 rounded-[9px] border border-border p-3">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium">{layanan.Nama}</p>
                      <p className="text-xs text-muted-foreground">
                        Terpakai {layanan.Terpakai}
                        {layanan.Kuota ? ` dari ${layanan.Kuota}` : ' (tanpa kuota)'} {layanan.Satuan ?? ''}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      {aktif && <DialogPemakaian kontrak={kontrak} layanan={layanan} />}
                      {aktif && (
                        <Button
                          size="icon"
                          variant="ghost"
                          aria-label={`Hapus layanan ${layanan.Nama}`}
                          onClick={() => hapusLayanan(layanan)}
                        >
                          <Trash2 />
                        </Button>
                      )}
                    </div>
                  </div>
                  {layanan.Deskripsi && <p className="text-sm text-muted-foreground">{layanan.Deskripsi}</p>}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Dokumen dan Kolaborasi</CardTitle>
          </CardHeader>
          <CardContent>
            <PanelKolaborasi jenisEntitas="Kontrak" entitasId={kontrak.Id} />
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
