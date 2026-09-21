import { type FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, ReceiptText, Send, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
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
import type {
  Anggaran,
  JenisTransaksiAnggaran,
  PosAnggaran,
  TransaksiAnggaran,
} from '@/features/Anggaran/types';
import { formatUang } from '@/lib/uang';

interface Props {
  anggaran: Anggaran;
  transaksi: TransaksiAnggaran[];
  dapatMenyesuaikan: boolean;
}

const TANPA = '__tanpa__';
const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Aktif: 'sukses',
  Ditolak: 'bahaya',
  Ditutup: 'netral',
} as const;

function DialogPos({
  anggaran,
  pos,
  semuaPos,
}: {
  anggaran: Anggaran;
  pos?: PosAnggaran;
  semuaPos: PosAnggaran[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    IndukId: pos?.IndukId ?? TANPA,
    Kode: pos?.Kode ?? '',
    Nama: pos?.Nama ?? '',
    Jumlah: pos?.Jumlah ?? '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, IndukId: data.IndukId === TANPA ? null : data.IndukId }));
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    if (pos) form.put(`/perencanaan-pengadaan/pos-anggaran/${pos.Id}`, opsi);
    else form.post(`/perencanaan-pengadaan/anggaran/${anggaran.Id}/pos`, opsi);
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        {pos ? (
          <Button variant="ghost" size="sm" aria-label={`Ubah ${pos.Nama}`}>
            <Pencil />
          </Button>
        ) : (
          <Button size="sm">
            <Plus /> Tambah Pos
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{pos ? 'Ubah Pos Anggaran' : 'Tambah Pos Anggaran'}</DialogTitle>
          <DialogDescription>
            Nilai anak tidak boleh melebihi pos induk; pos utama tidak boleh melampaui total anggaran.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Pos Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(value) => form.setData('IndukId', value)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Pos utama</SelectItem>
                {semuaPos
                  .filter((item) => item.Id !== pos?.Id)
                  .map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Kode} — {item.Nama}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="kode-pos">Kode</Label>
              <Input
                id="kode-pos"
                value={form.data.Kode}
                onChange={(event) => form.setData('Kode', event.target.value)}
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="nilai-pos">Nilai</Label>
              <Input
                id="nilai-pos"
                type="number"
                min="0.01"
                step="0.01"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="nama-pos">Nama</Label>
            <Input
              id="nama-pos"
              value={form.data.Nama}
              onChange={(event) => form.setData('Nama', event.target.value)}
            />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              {pos ? 'Simpan Perubahan' : 'Tambah Pos'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogTransaksi({ pos, dapatMenyesuaikan }: { pos: PosAnggaran; dapatMenyesuaikan: boolean }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Jenis: 'Komitmen' as JenisTransaksiAnggaran,
    Jumlah: '',
    Tanggal: new Date().toISOString().slice(0, 10),
    ReferensiJenis: '',
    ReferensiId: '',
    Keterangan: '',
  });
  const jenis: JenisTransaksiAnggaran[] = [
    'Komitmen',
    'Realisasi',
    'PelepasanKomitmen',
    ...(dapatMenyesuaikan ? ['Penyesuaian' as const] : []),
  ];

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      ReferensiJenis: data.ReferensiJenis || null,
      ReferensiId: data.ReferensiId || null,
      Keterangan: data.Keterangan || null,
    }));
    form.post(`/perencanaan-pengadaan/pos-anggaran/${pos.Id}/transaksi`, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset('Jumlah', 'ReferensiJenis', 'ReferensiId', 'Keterangan');
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <ReceiptText /> Catat
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Catat Transaksi — {pos.Nama}</DialogTitle>
          <DialogDescription>
            Saldo dihitung ulang dari ledger setelah transaksi tersimpan. Realisasi otomatis melepas komitmen
            yang tersedia.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Jenis</Label>
            <Select
              value={form.data.Jenis}
              onValueChange={(value) => form.setData('Jenis', value as JenisTransaksiAnggaran)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {jenis.map((item) => (
                  <SelectItem key={item} value={item}>
                    {item === 'PelepasanKomitmen'
                      ? 'Pelepasan Komitmen'
                      : item === 'Penyesuaian'
                        ? 'Penyesuaian (izin khusus)'
                        : item}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor={`jumlah-${pos.Id}`}>Jumlah</Label>
              <Input
                id={`jumlah-${pos.Id}`}
                type="number"
                step="0.01"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor={`tanggal-${pos.Id}`}>Tanggal</Label>
              <Input
                id={`tanggal-${pos.Id}`}
                type="date"
                value={form.data.Tanggal}
                onChange={(event) => form.setData('Tanggal', event.target.value)}
              />
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Jenis Referensi</Label>
              <Input
                value={form.data.ReferensiJenis}
                onChange={(event) => form.setData('ReferensiJenis', event.target.value)}
                placeholder="Contoh: RencanaPengadaan"
              />
            </div>
            <div className="space-y-1.5">
              <Label>ID Referensi</Label>
              <Input
                value={form.data.ReferensiId}
                onChange={(event) => form.setData('ReferensiId', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor={`keterangan-${pos.Id}`}>
              Keterangan {form.data.Jenis === 'Penyesuaian' && '(wajib)'}
            </Label>
            <Textarea
              id={`keterangan-${pos.Id}`}
              rows={3}
              value={form.data.Keterangan}
              onChange={(event) => form.setData('Keterangan', event.target.value)}
            />
            {form.errors.Keterangan && <p className="text-sm text-destructive">{form.errors.Keterangan}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Catat Transaksi
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogUbahAnggaran({ anggaran }: { anggaran: Anggaran }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: anggaran.Kode,
    Nama: anggaran.Nama,
    Tahun: anggaran.Tahun.toString(),
    MataUang: anggaran.MataUang,
    Jumlah: anggaran.Jumlah,
  });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.put(`/perencanaan-pengadaan/anggaran/${anggaran.Id}`, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Pencil /> Ubah
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Anggaran</DialogTitle>
          <DialogDescription>Total tidak dapat diturunkan melewati alokasi pos utama.</DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Kode</Label>
              <Input value={form.data.Kode} onChange={(event) => form.setData('Kode', event.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label>Tahun</Label>
              <Input
                type="number"
                value={form.data.Tahun}
                onChange={(event) => form.setData('Tahun', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(event) => form.setData('Nama', event.target.value)} />
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Total</Label>
              <Input
                type="number"
                step="0.01"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Mata Uang</Label>
              <Input
                maxLength={3}
                value={form.data.MataUang}
                onChange={(event) => form.setData('MataUang', event.target.value.toUpperCase())}
              />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Perubahan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function AnggaranShow({ anggaran, transaksi, dapatMenyesuaikan }: Props) {
  const posisi = anggaran.PosAnggaran ?? [];
  const ringkasan = useMemo(
    () =>
      posisi.reduce(
        (hasil, pos) => ({
          terpakai: hasil.terpakai + Number(pos.Terpakai),
          ditahan: hasil.ditahan + Number(pos.Ditahan),
          sisa: hasil.sisa + Number(pos.Sisa),
        }),
        { terpakai: 0, ditahan: 0, sisa: 0 },
      ),
    [posisi],
  );
  const dapatUbah = anggaran.Status === 'Draft' || anggaran.Status === 'Ditolak';

  function ajukan(): void {
    if (confirm(`Ajukan anggaran ${anggaran.Kode}? Struktur pos tidak dapat diubah setelah diajukan.`))
      router.post(`/perencanaan-pengadaan/anggaran/${anggaran.Id}/ajukan`);
  }
  function hapusAnggaran(): void {
    if (confirm(`Hapus draft anggaran ${anggaran.Kode}? Tindakan ini hanya berhasil bila belum ada pos.`))
      router.delete(`/perencanaan-pengadaan/anggaran/${anggaran.Id}`);
  }
  function hapusPos(pos: PosAnggaran): void {
    if (
      confirm(
        `Hapus pos ${pos.Kode} — ${pos.Nama}? Pos yang memiliki anak atau transaksi tidak dapat dihapus.`,
      )
    )
      router.delete(`/perencanaan-pengadaan/pos-anggaran/${pos.Id}`, { preserveScroll: true });
  }

  return (
    <AppLayout>
      <Head title={`${anggaran.Kode} — Anggaran`} />
      <div className="space-y-6">
        <Link
          href="/perencanaan-pengadaan/anggaran"
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Anggaran
        </Link>
        <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p className="font-mono text-sm text-muted-foreground">
              {anggaran.Kode} · {anggaran.Tahun}
            </p>
            <div className="mt-1 flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{anggaran.Nama}</h1>
              <Badge variant={VARIAN_STATUS[anggaran.Status]}>
                {anggaran.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : anggaran.Status}
              </Badge>
            </div>
            <p className="mt-1 text-sm text-muted-foreground">
              {anggaran.NamaUnitOrganisasi ?? 'Scope seluruh organisasi'}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            {dapatUbah && <DialogUbahAnggaran anggaran={anggaran} />}
            {anggaran.Status === 'Draft' && (
              <Button size="sm" onClick={ajukan}>
                <Send /> Ajukan
              </Button>
            )}
            {dapatUbah && (
              <Button size="sm" variant="outline" onClick={hapusAnggaran}>
                <Trash2 /> Hapus
              </Button>
            )}
          </div>
        </header>

        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {[
            ['Total Anggaran', formatUang(anggaran.Jumlah, anggaran.MataUang)],
            ['Realisasi', formatUang(ringkasan.terpakai, anggaran.MataUang)],
            ['Komitmen', formatUang(ringkasan.ditahan, anggaran.MataUang)],
            ['Sisa Pos', formatUang(ringkasan.sisa, anggaran.MataUang)],
          ].map(([label, value]) => (
            <Card key={label}>
              <CardHeader className="pb-2">
                <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  {label}
                </CardTitle>
              </CardHeader>
              <CardContent className="font-mono text-lg font-semibold">{value}</CardContent>
            </Card>
          ))}
        </div>

        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="flex items-center justify-between border-b border-border p-4">
            <div>
              <h2 className="font-semibold">Pos Anggaran</h2>
              <p className="text-xs text-muted-foreground">
                Saldo proyeksi selalu direkonsiliasi dari transaksi ledger.
              </p>
            </div>
            {dapatUbah && <DialogPos anggaran={anggaran} semuaPos={posisi} />}
          </div>
          {posisi.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted-foreground">Belum ada pos anggaran.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[860px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Pos</th>
                    <th className="px-4 py-3 text-right">Nilai</th>
                    <th className="px-4 py-3 text-right">Realisasi</th>
                    <th className="px-4 py-3 text-right">Komitmen</th>
                    <th className="px-4 py-3 text-right">Sisa</th>
                    <th className="px-4 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {posisi.map((pos) => (
                    <tr key={pos.Id}>
                      <td className="px-4 py-3">
                        <p className="font-medium">{pos.Nama}</p>
                        <p className="font-mono text-xs text-muted-foreground">
                          {pos.Kode}
                          {pos.NamaInduk ? ` · di bawah ${pos.NamaInduk}` : ''}
                        </p>
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Jumlah, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Terpakai, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(pos.Ditahan, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(pos.Sisa, anggaran.MataUang)}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex justify-end gap-1">
                          {anggaran.Status === 'Aktif' && (
                            <DialogTransaksi pos={pos} dapatMenyesuaikan={dapatMenyesuaikan} />
                          )}
                          {dapatUbah && <DialogPos anggaran={anggaran} pos={pos} semuaPos={posisi} />}
                          {dapatUbah && (
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label={`Hapus ${pos.Nama}`}
                              onClick={() => hapusPos(pos)}
                            >
                              <Trash2 />
                            </Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="border-b border-border p-4">
            <h2 className="font-semibold">Ledger Transaksi</h2>
            <p className="text-xs text-muted-foreground">
              100 transaksi terbaru; baris tidak dapat diedit atau dihapus.
            </p>
          </div>
          {transaksi.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted-foreground">Belum ada transaksi.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[760px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Tanggal</th>
                    <th className="px-4 py-3">Pos</th>
                    <th className="px-4 py-3">Jenis</th>
                    <th className="px-4 py-3 text-right">Jumlah</th>
                    <th className="px-4 py-3">Keterangan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {transaksi.map((item) => (
                    <tr key={item.Id}>
                      <td className="px-4 py-3">
                        {new Date(`${item.Tanggal}T00:00:00`).toLocaleDateString('id-ID')}
                      </td>
                      <td className="px-4 py-3">{item.NamaPosAnggaran}</td>
                      <td className="px-4 py-3">
                        <Badge
                          variant={
                            item.Jenis === 'Realisasi'
                              ? 'sukses'
                              : item.Jenis === 'Komitmen'
                                ? 'perhatian'
                                : 'netral'
                          }
                        >
                          {item.Jenis === 'PelepasanKomitmen' ? 'Pelepasan' : item.Jenis}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatUang(item.Jumlah, anggaran.MataUang)}
                      </td>
                      <td className="max-w-xs px-4 py-3 text-muted-foreground">{item.Keterangan ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      </div>
    </AppLayout>
  );
}
