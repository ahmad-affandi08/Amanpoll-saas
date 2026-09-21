import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, PlugZap, Plus, RefreshCw, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
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
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type {
  IntegrasiEksternal,
  PemetaanDataEksternal,
  SinkronisasiEksternal,
  StatusIntegrasi,
} from '@/features/Integrasi/types';
import { ruteIntegrasi } from '@/features/Integrasi/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';

interface Props {
  integrasi: IntegrasiEksternal;
  pemetaan: PemetaanDataEksternal[];
  sinkronisasi: SinkronisasiEksternal[];
}

const STATUS: StatusIntegrasi[] = ['Aktif', 'Nonaktif', 'Bermasalah'];
const VARIAN_STATUS = { Aktif: 'sukses', Nonaktif: 'netral', Bermasalah: 'bahaya' } as const;
const VARIAN_SINKRON = {
  Diproses: 'info',
  Berhasil: 'sukses',
  Sebagian: 'perhatian',
  Gagal: 'bahaya',
} as const;

function DialogPemetaan({ integrasi }: { integrasi: IntegrasiEksternal }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ JenisEntitas: 'Aset', EntitasId: '', KodeEksternal: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteIntegrasi.pemetaan(integrasi.Id), {
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
          <Plus /> Tambah Pemetaan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Pemetaan Data</DialogTitle>
          <DialogDescription>
            Menghubungkan identitas internal Amanpoll dengan kode pada sistem eksternal.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="JenisEntitas">Jenis entitas</Label>
            <Input
              id="JenisEntitas"
              placeholder="mis. Aset, Penyedia"
              value={form.data.JenisEntitas}
              onChange={(event) => form.setData('JenisEntitas', event.target.value)}
            />
            {form.errors.JenisEntitas && (
              <p className="text-sm text-destructive">{form.errors.JenisEntitas}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="EntitasId">ID internal</Label>
            <Input
              id="EntitasId"
              value={form.data.EntitasId}
              onChange={(event) => form.setData('EntitasId', event.target.value)}
            />
            {form.errors.EntitasId && <p className="text-sm text-destructive">{form.errors.EntitasId}</p>}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="KodeEksternal">Kode eksternal</Label>
            <Input
              id="KodeEksternal"
              value={form.data.KodeEksternal}
              onChange={(event) => form.setData('KodeEksternal', event.target.value)}
            />
            {form.errors.KodeEksternal && (
              <p className="text-sm text-destructive">{form.errors.KodeEksternal}</p>
            )}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Pemetaan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogSinkronisasi({ integrasi }: { integrasi: IntegrasiEksternal }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ JenisProses: 'Aset', Arah: 'Tarik' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteIntegrasi.sinkronkan(integrasi.Id), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        setBuka(false);
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          <RefreshCw /> Sinkronkan
        </Button>
      </DialogTrigger>
      <DialogContent>
        <form onSubmit={submit}>
          <DialogHeader>
            <DialogTitle>Jalankan Sinkronisasi</DialogTitle>
            <DialogDescription>
              Sinkronisasi dikerjakan di antrean. Hasilnya muncul di riwayat begitu selesai.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="JenisProses">Jenis Proses</Label>
              <Input
                id="JenisProses"
                value={form.data.JenisProses}
                onChange={(event) => form.setData('JenisProses', event.target.value)}
                required
              />
              {form.errors.JenisProses && (
                <p className="text-sm text-destructive">{form.errors.JenisProses}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="Arah">Arah</Label>
              <Select value={form.data.Arah} onValueChange={(nilai) => form.setData('Arah', nilai)}>
                <SelectTrigger id="Arah">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Tarik">Tarik dari sistem eksternal</SelectItem>
                  <SelectItem value="Dorong">Dorong ke sistem eksternal</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Masukkan ke Antrean
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function IntegrasiShow({ integrasi, pemetaan, sinkronisasi }: Props) {
  const konfirmasi = useKonfirmasi();
  const [memproses, setMemproses] = useState(false);
  const berkonflik = pemetaan.filter((item) => item.Konflik);

  function ujiKoneksi(): void {
    router.post(
      ruteIntegrasi.ujiKoneksi(integrasi.Id),
      {},
      { preserveScroll: true, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) },
    );
  }

  function ubahStatus(status: string): void {
    router.post(ruteIntegrasi.status(integrasi.Id), { Status: status }, { preserveScroll: true });
  }

  async function selesaikanKonflik(item: PemetaanDataEksternal, pertahankan: boolean): Promise<void> {
    const lanjut = await konfirmasi({
      judul: pertahankan
        ? `Pertahankan pemetaan "${item.KodeEksternal}"?`
        : `Buang pemetaan "${item.KodeEksternal}"?`,
      deskripsi: pertahankan
        ? 'Pemetaan lain yang memakai kode eksternal ini akan dilepas.'
        : 'Pemetaan ini dihapus dan pemetaan lawannya tetap dipakai.',
      ragam: 'perhatian',
    });
    if (lanjut) {
      router.post(
        ruteIntegrasi.pemetaanKonflik(integrasi.Id, item.Id),
        { Pertahankan: pertahankan },
        { preserveScroll: true },
      );
    }
  }

  async function lepasPemetaan(item: PemetaanDataEksternal): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Lepas pemetaan "${item.KodeEksternal}"?`,
      deskripsi: 'Sinkronisasi berikutnya akan memperlakukan entitas ini sebagai data baru.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteIntegrasi.pemetaanDetail(integrasi.Id, item.Id), { preserveScroll: true });
  }

  return (
    <AppLayout>
      <Head title={`${integrasi.Kode} — Integrasi`} />
      <BreadcrumbHalaman />
      <div className="space-y-6">
        <Link
          href={ruteIntegrasi.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Integrasi
        </Link>

        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{integrasi.Nama}</h1>
              <Badge variant={VARIAN_STATUS[integrasi.Status]}>{integrasi.Status}</Badge>
            </div>
            <p className="font-mono text-sm text-muted-foreground">
              {integrasi.Kode} · {integrasi.Jenis} · {integrasi.MetodeAutentikasi ?? 'Tanpa autentikasi'}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Select value={integrasi.Status} onValueChange={ubahStatus}>
              <SelectTrigger className="w-40">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {STATUS.map((item) => (
                  <SelectItem key={item} value={item}>
                    {item}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button size="sm" className="min-h-11 sm:min-h-9" disabled={memproses} onClick={ujiKoneksi}>
              <PlugZap /> Uji Koneksi
            </Button>
          </div>
        </header>

        {berkonflik.length > 0 && (
          <Alert variant="bahaya">
            <AlertTitle>{berkonflik.length} pemetaan berkonflik</AlertTitle>
            <AlertDescription>
              Satu kode eksternal dipakai lebih dari satu entitas internal. Pilih pemetaan mana yang benar
              sebelum sinkronisasi berikutnya dijalankan.
            </AlertDescription>
          </Alert>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Konfigurasi</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">URL dasar</p>
              <p className="truncate font-mono text-sm">{integrasi.UrlDasar ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Kunci kredensial tersimpan</p>
              <p className="text-sm">
                {integrasi.KunciKonfigurasi.length > 0 ? integrasi.KunciKonfigurasi.join(', ') : 'Tidak ada'}
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Terakhir sinkron</p>
              <p className="text-sm">{integrasi.TerakhirSinkronPada ?? 'Belum pernah'}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Pemetaan Data</CardTitle>
            <DialogPemetaan integrasi={integrasi} />
          </CardHeader>
          <CardContent className="space-y-3">
            {pemetaan.length === 0 ? (
              <EmptyState
                judul="Belum ada pemetaan."
                deskripsi="Petakan entitas Amanpoll ke kode pada sistem eksternal."
              />
            ) : (
              pemetaan.map((item) => (
                <div key={item.Id} className="space-y-2 rounded-[9px] border border-border p-3">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium">
                        {item.JenisEntitas} → {item.KodeEksternal}
                      </p>
                      <p className="font-mono text-xs text-muted-foreground">{item.EntitasId}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      {item.Konflik && <Badge variant="bahaya">Konflik</Badge>}
                      <Button
                        size="icon"
                        variant="ghost"
                        aria-label={`Lepas pemetaan ${item.KodeEksternal}`}
                        onClick={() => lepasPemetaan(item)}
                      >
                        <Trash2 />
                      </Button>
                    </div>
                  </div>
                  {item.Konflik && (
                    <div className="flex flex-wrap items-center gap-2 rounded-[9px] bg-bahaya-600/10 p-2">
                      <p className="min-w-0 flex-1 text-xs text-bahaya-600">{item.AlasanKonflik}</p>
                      <Button size="sm" variant="outline" onClick={() => selesaikanKonflik(item, true)}>
                        Pertahankan ini
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => selesaikanKonflik(item, false)}>
                        Buang ini
                      </Button>
                    </div>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between gap-3">
            <CardTitle>Riwayat Sinkronisasi</CardTitle>
            <DialogSinkronisasi integrasi={integrasi} />
          </CardHeader>
          <CardContent className="space-y-3">
            {sinkronisasi.length === 0 ? (
              <EmptyState
                judul="Belum ada sinkronisasi."
                deskripsi="Riwayat tarik dan dorong data akan muncul di sini beserta jumlah keberhasilannya."
              />
            ) : (
              sinkronisasi.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-2 rounded-[9px] border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">
                      {item.JenisProses} · {item.Arah}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {item.JumlahBerhasil} berhasil · {item.JumlahGagal} gagal dari {item.JumlahData} data
                    </p>
                    {item.PesanKesalahan && (
                      <p className="mt-1 text-xs text-bahaya-600">{item.PesanKesalahan}</p>
                    )}
                  </div>
                  <Badge variant={VARIAN_SINKRON[item.Status]}>{item.Status}</Badge>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
