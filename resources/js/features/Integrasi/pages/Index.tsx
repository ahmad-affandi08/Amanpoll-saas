import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plug, Plus, Webhook } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
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
import { Switch } from '@/components/ui/switch';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type {
  AntrianPeristiwa,
  IntegrasiEksternal,
  MetodeAutentikasi,
  PanggilanBalikWeb,
} from '@/features/Integrasi/types';
import { ruteIntegrasi } from '@/features/Integrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  integrasi: IntegrasiEksternal[];
  webhook: PanggilanBalikWeb[];
  antrianPeristiwa: AntrianPeristiwa;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const METODE: MetodeAutentikasi[] = ['Bearer', 'ApiKey', 'Basic', 'TanpaAutentikasi'];
const VARIAN_STATUS = { Aktif: 'sukses', Nonaktif: 'netral', Bermasalah: 'bahaya' } as const;

function DialogBuatIntegrasi({ wajib }: { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    Jenis: '',
    UrlDasar: '',
    MetodeAutentikasi: 'TanpaAutentikasi',
    Token: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      Kode: data.Kode,
      Nama: data.Nama,
      Jenis: data.Jenis,
      UrlDasar: data.UrlDasar || null,
      MetodeAutentikasi: data.MetodeAutentikasi,
      Konfigurasi: data.Token ? { Token: data.Token } : null,
    }));
    form.post(ruteIntegrasi.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Tambah Integrasi
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Integrasi Eksternal</DialogTitle>
          <DialogDescription>
            Kredensial disimpan terenkripsi dan tidak pernah ditampilkan kembali setelah disimpan.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
              <div className="space-y-1.5">
                <Label nama="KodeIntegrasi" htmlFor="KodeIntegrasi">
                  Kode
                </Label>
                <Input
                  id="KodeIntegrasi"
                  value={form.data.Kode}
                  onChange={(event) => form.setData('Kode', event.target.value)}
                />
                {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="NamaIntegrasi" htmlFor="NamaIntegrasi">
                  Nama
                </Label>
                <Input
                  id="NamaIntegrasi"
                  value={form.data.Nama}
                  onChange={(event) => form.setData('Nama', event.target.value)}
                />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="JenisIntegrasi" htmlFor="JenisIntegrasi">
                  Jenis sistem
                </Label>
                <Input
                  id="JenisIntegrasi"
                  placeholder="mis. ERP, HRIS, IoT"
                  value={form.data.Jenis}
                  onChange={(event) => form.setData('Jenis', event.target.value)}
                />
                {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="MetodeAutentikasi">Metode autentikasi</Label>
                <Select
                  value={form.data.MetodeAutentikasi}
                  onValueChange={(value) => form.setData('MetodeAutentikasi', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {METODE.map((item) => (
                      <SelectItem key={item} value={item}>
                        {item}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="UrlDasar" htmlFor="UrlDasar">
                URL dasar
              </Label>
              <Input
                id="UrlDasar"
                placeholder="https://sistem-tujuan.test/api"
                value={form.data.UrlDasar}
                onChange={(event) => form.setData('UrlDasar', event.target.value)}
              />
              {form.errors.UrlDasar && <p className="text-sm text-destructive">{form.errors.UrlDasar}</p>}
            </div>
            {form.data.MetodeAutentikasi !== 'TanpaAutentikasi' && (
              <div className="space-y-1.5">
                <Label nama="TokenIntegrasi" htmlFor="TokenIntegrasi">
                  Token / kunci
                </Label>
                <Input
                  id="TokenIntegrasi"
                  type="password"
                  autoComplete="off"
                  value={form.data.Token}
                  onChange={(event) => form.setData('Token', event.target.value)}
                />
              </div>
            )}
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Integrasi
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogBuatWebhook() {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nama: '', Url: '', Rahasia: '', Peristiwa: 'Keluhan.*', Aktif: true });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Peristiwa: data.Peristiwa.split(',')
        .map((pola) => pola.trim())
        .filter((pola) => pola !== ''),
    }));
    form.post(ruteIntegrasi.panggilanBalik, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" className="min-h-11 sm:min-h-9">
          <Webhook /> Tambah Panggilan Balik
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Panggilan Balik Web</DialogTitle>
          <DialogDescription>
            Setiap pengiriman ditandatangani HMAC SHA-256 memakai rahasia ini pada header
            X-Amanpoll-Signature.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label nama="NamaWebhook" htmlFor="NamaWebhook">
              Nama
            </Label>
            <Input
              id="NamaWebhook"
              value={form.data.Nama}
              onChange={(event) => form.setData('Nama', event.target.value)}
            />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <div className="space-y-1.5">
            <Label nama="UrlWebhook" htmlFor="UrlWebhook">
              URL tujuan
            </Label>
            <Input
              id="UrlWebhook"
              placeholder="https://sistem-anda.test/hook"
              value={form.data.Url}
              onChange={(event) => form.setData('Url', event.target.value)}
            />
            {form.errors.Url && <p className="text-sm text-destructive">{form.errors.Url}</p>}
          </div>
          <div className="space-y-1.5">
            <Label nama="RahasiaWebhook" htmlFor="RahasiaWebhook">
              Rahasia penandatanganan
            </Label>
            <Input
              id="RahasiaWebhook"
              type="password"
              autoComplete="off"
              value={form.data.Rahasia}
              onChange={(event) => form.setData('Rahasia', event.target.value)}
            />
            {form.errors.Rahasia && <p className="text-sm text-destructive">{form.errors.Rahasia}</p>}
          </div>
          <div className="space-y-1.5">
            <Label nama="PeristiwaWebhook" htmlFor="PeristiwaWebhook">
              Peristiwa (pisahkan dengan koma)
            </Label>
            <Input
              id="PeristiwaWebhook"
              placeholder="Keluhan.*, PerintahKerja.Selesai"
              value={form.data.Peristiwa}
              onChange={(event) => form.setData('Peristiwa', event.target.value)}
            />
            {form.errors.Peristiwa && <p className="text-sm text-destructive">{form.errors.Peristiwa}</p>}
          </div>
          <div className="flex items-center justify-between rounded-md border border-border p-3">
            <div>
              <p className="text-sm font-medium">Aktif</p>
              <p className="text-xs text-muted-foreground">Endpoint nonaktif tidak menerima pengiriman.</p>
            </div>
            <Switch checked={form.data.Aktif} onCheckedChange={(nilai) => form.setData('Aktif', nilai)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Panggilan Balik
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function IntegrasiIndex({ integrasi, webhook, antrianPeristiwa, wajib }: Props) {
  const konfirmasi = useKonfirmasi();

  async function hapusWebhook(item: PanggilanBalikWeb): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus panggilan balik "${item.Nama}"?`,
      deskripsi: 'Riwayat pengirimannya ikut terhapus dan peristiwa berikutnya tidak lagi dikirim ke sana.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteIntegrasi.panggilanBalikDetail(item.Id), { preserveScroll: true });
  }

  return (
    <KerangkaAplikasi>
      <Head title="Integrasi" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Integrasi"
          deskripsi="Sistem eksternal, panggilan balik web, dan antrean peristiwa keluar."
          aksi={
            <>
              <div className="flex flex-wrap gap-2">
                <DialogBuatWebhook />
                <DialogBuatIntegrasi wajib={wajib.integrasi} />
              </div>
            </>
          }
        />

        {(antrianPeristiwa.gagal > 0 || antrianPeristiwa.pengirimanGagal > 0) && (
          <Alert variant="perhatian">
            <AlertTitle>Ada peristiwa yang belum berhasil dikirim</AlertTitle>
            <AlertDescription>
              {antrianPeristiwa.gagal} peristiwa berhenti setelah batas percobaan dan{' '}
              {antrianPeristiwa.pengirimanGagal} pengiriman menunggu percobaan ulang. Periksa riwayat
              pengiriman pada panggilan balik terkait.
            </AlertDescription>
          </Alert>
        )}

        <DeretStatistik kolom={3}>
          <KartuStatistik menyatu label="Peristiwa menunggu" nilai={antrianPeristiwa.menunggu} />
          <KartuStatistik menyatu label="Peristiwa gagal" nilai={antrianPeristiwa.gagal} />
          <KartuStatistik menyatu label="Pengiriman bermasalah" nilai={antrianPeristiwa.pengirimanGagal} />
        </DeretStatistik>

        <Card>
          <CardHeader>
            <CardTitle>Sistem Eksternal</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {integrasi.length === 0 ? (
              <KeadaanKosong
                ilustrasi="/assets/3d/integrasi.webp"
                judul="Belum ada integrasi."
                deskripsi="Daftarkan sistem eksternal untuk menarik atau mendorong data."
              />
            ) : (
              integrasi.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteIntegrasi.detail(item.Id)}
                  className="flex flex-col gap-2 rounded-md border border-border p-3 transition hover:border-primary/40 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.Nama}</p>
                    <p className="font-mono text-xs text-muted-foreground">
                      {item.Kode} · {item.Jenis} · {item.JumlahPemetaan} pemetaan
                    </p>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="text-xs text-muted-foreground">
                      {item.MetodeAutentikasi ?? 'Tanpa autentikasi'}
                    </span>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </div>
                </Link>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Panggilan Balik Web</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {webhook.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada panggilan balik."
                deskripsi="Daftarkan endpoint untuk menerima peristiwa Amanpoll secara otomatis."
              />
            ) : (
              webhook.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-2 rounded-md border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.Nama}</p>
                    <p className="truncate font-mono text-xs text-muted-foreground">{item.Url}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {item.Peristiwa.join(', ')} · {item.JumlahPengiriman ?? 0} pengiriman
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant={item.Aktif ? 'sukses' : 'netral'}>
                      {item.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                    <Button variant="outline" size="sm" className="min-h-11 sm:min-h-9" asChild>
                      <Link href={ruteIntegrasi.panggilanBalikPengiriman(item.Id)}>Riwayat</Link>
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="min-h-11 sm:min-h-9"
                      onClick={() => hapusWebhook(item)}
                    >
                      Hapus
                    </Button>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <p className="flex items-center gap-2 text-xs text-muted-foreground">
          <Plug className="size-4" />
          Endpoint tulis API memerlukan header Idempotency-Key agar percobaan ulang tidak menggandakan data.
        </p>
      </div>
    </KerangkaAplikasi>
  );
}
