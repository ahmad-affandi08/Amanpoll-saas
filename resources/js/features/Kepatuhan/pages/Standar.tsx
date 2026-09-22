import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
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
import { Textarea } from '@/components/ui/textarea';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type { PersyaratanKepatuhan, StandarKepatuhan } from '@/features/Kepatuhan/types';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';
import { BidangKode } from '@/components/shared/BidangKode';

interface Props {
  standar: StandarKepatuhan;
}

function DialogTambahPersyaratan({ standar }: Props) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    Deskripsi: '',
    BuktiYangDiperlukan: '',
    IntervalHari: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Deskripsi: data.Deskripsi || null,
      BuktiYangDiperlukan: data.BuktiYangDiperlukan || null,
      IntervalHari: data.IntervalHari || null,
    }));
    form.post(ruteKepatuhan.persyaratan(standar.Id), {
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
          <Plus /> Tambah Persyaratan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Persyaratan {standar.Kode}</DialogTitle>
          <DialogDescription>
            Interval hari menentukan masa berlaku hasil pemeriksaan; kosongkan bila persyaratan tidak perlu
            diperiksa ulang secara berkala.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
            />
            <div className="space-y-1.5">
              <Label nama="NamaPersyaratan" htmlFor="NamaPersyaratan">
                Nama
              </Label>
              <Input
                id="NamaPersyaratan"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label nama="DeskripsiPersyaratan" htmlFor="DeskripsiPersyaratan">
              Deskripsi
            </Label>
            <Textarea
              id="DeskripsiPersyaratan"
              rows={2}
              value={form.data.Deskripsi}
              onChange={(event) => form.setData('Deskripsi', event.target.value)}
            />
          </div>
          <div className="space-y-1.5">
            <Label nama="BuktiYangDiperlukan" htmlFor="BuktiYangDiperlukan">
              Bukti yang diperlukan
            </Label>
            <Textarea
              id="BuktiYangDiperlukan"
              rows={2}
              placeholder="mis. sertifikat uji, foto pemasangan, berita acara"
              value={form.data.BuktiYangDiperlukan}
              onChange={(event) => form.setData('BuktiYangDiperlukan', event.target.value)}
            />
          </div>
          <div className="space-y-1.5">
            <Label nama="IntervalHari" htmlFor="IntervalHari">
              Interval pemeriksaan (hari)
            </Label>
            <Input
              id="IntervalHari"
              type="number"
              min="1"
              max="3650"
              value={form.data.IntervalHari}
              onChange={(event) => form.setData('IntervalHari', event.target.value)}
            />
            {form.errors.IntervalHari && (
              <p className="text-sm text-destructive">{form.errors.IntervalHari}</p>
            )}
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

export default function KepatuhanStandar({ standar }: Props) {
  const konfirmasi = useKonfirmasi();
  const persyaratan = standar.Persyaratan ?? [];

  async function hapusPersyaratan(item: PersyaratanKepatuhan): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus persyaratan "${item.Kode}"?`,
      deskripsi: 'Persyaratan yang sudah ditugaskan ke aset tidak dapat dihapus.',
      ragam: 'bahaya',
    });
    if (lanjut) {
      router.delete(ruteKepatuhan.persyaratanDetail(standar.Id, item.Id), { preserveScroll: true });
    }
  }

  async function hapusStandar(): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Hapus standar "${standar.Nama}"?`,
      deskripsi: 'Standar yang masih memiliki persyaratan tidak dapat dihapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKepatuhan.standarDetail(standar.Id));
  }

  return (
    <KerangkaAplikasi>
      <Head title={`${standar.Kode} — Standar Kepatuhan`} />
      <BreadcrumbHalaman />
      <div className="space-y-6">
        <Link
          href={ruteKepatuhan.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Kepatuhan
        </Link>

        <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{standar.Nama}</h1>
              <Badge variant={standar.Aktif ? 'sukses' : 'netral'}>
                {standar.Aktif ? 'Aktif' : 'Nonaktif'}
              </Badge>
            </div>
            <p className="font-mono text-sm text-muted-foreground">
              {standar.Kode}
              {standar.VersiStandar ? ` · versi ${standar.VersiStandar}` : ''}
              {standar.Penerbit ? ` · ${standar.Penerbit}` : ''}
            </p>
          </div>
          <Button size="sm" variant="destructive" className="min-h-11 sm:min-h-9" onClick={hapusStandar}>
            <Trash2 /> Hapus Standar
          </Button>
        </header>

        {standar.Deskripsi && (
          <Card>
            <CardHeader>
              <CardTitle>Deskripsi</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">{standar.Deskripsi}</p>
              {standar.JenisIndustri && (
                <p className="mt-2 text-xs text-muted-foreground">Lingkup: {standar.JenisIndustri}</p>
              )}
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Persyaratan</CardTitle>
            {standar.Aktif && <DialogTambahPersyaratan standar={standar} />}
          </CardHeader>
          <CardContent className="space-y-3">
            {persyaratan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada persyaratan."
                deskripsi="Rinci persyaratan standar ini agar dapat ditugaskan ke aset."
              />
            ) : (
              persyaratan.map((item) => (
                <div key={item.Id} className="space-y-2 rounded-[9px] border border-border p-3">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium">{item.Nama}</p>
                      <p className="font-mono text-xs text-muted-foreground">
                        {item.Kode}
                        {item.IntervalHari ? ` · tiap ${item.IntervalHari} hari` : ' · tanpa interval'} ·{' '}
                        {item.JumlahAsetDitugaskan ?? 0} aset
                      </p>
                    </div>
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Hapus persyaratan ${item.Kode}`}
                      onClick={() => hapusPersyaratan(item)}
                    >
                      <Trash2 />
                    </Button>
                  </div>
                  {item.Deskripsi && <p className="text-sm text-muted-foreground">{item.Deskripsi}</p>}
                  {item.BuktiYangDiperlukan && (
                    <p className="text-xs text-muted-foreground">Bukti: {item.BuktiYangDiperlukan}</p>
                  )}
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}
