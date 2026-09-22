import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Pencil, Plus, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import type { DetailRencanaPengadaan, RencanaPengadaan } from '@/features/RencanaPengadaan/types';
import { formatUang } from '@/lib/uang';
import { ruteRencanaPengadaan } from '@/features/RencanaPengadaan/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface PosRingkas {
  Id: string;
  Label: string;
}
interface UsulanRingkas {
  Id: string;
  Nomor: string;
  NamaKebutuhan: string;
}
interface SukuCadangRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface Props {
  rencana: RencanaPengadaan;
  posAnggaran: PosRingkas[];
  usulanDisetujui: UsulanRingkas[];
  sukuCadang: SukuCadangRingkas[];
}
const TANPA = '__tanpa__';
const MANUAL = '__manual__';
const VARIAN_STATUS = { Draft: 'netral', Direncanakan: 'sukses', Dibatalkan: 'bahaya' } as const;
const BULAN = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
];

function DialogUbahRencana({ rencana, posAnggaran }: Pick<Props, 'rencana' | 'posAnggaran'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Nama: rencana.Nama,
    Tahun: rencana.Tahun.toString(),
    PosAnggaranId: rencana.PosAnggaranId ?? TANPA,
  });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      PosAnggaranId: data.PosAnggaranId === TANPA ? null : data.PosAnggaranId,
    }));
    form.put(ruteRencanaPengadaan.detail(rencana.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          <Pencil /> Ubah
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Rencana</DialogTitle>
          <DialogDescription>Pilih pos dari anggaran aktif sebelum finalisasi.</DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(event) => form.setData('Nama', event.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label>Tahun</Label>
            <Input
              type="number"
              value={form.data.Tahun}
              onChange={(event) => form.setData('Tahun', event.target.value)}
            />
          </div>
          <div className="space-y-1.5">
            <Label>Pos Anggaran</Label>
            <Select
              value={form.data.PosAnggaranId}
              onValueChange={(value) => form.setData('PosAnggaranId', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Belum dipilih</SelectItem>
                {posAnggaran.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
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

function DialogTambahDetail({
  rencana,
  usulanDisetujui,
  sukuCadang,
}: Pick<Props, 'rencana' | 'usulanDisetujui' | 'sukuCadang'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UsulanAsetId: MANUAL,
    SukuCadangId: TANPA,
    Deskripsi: '',
    Jumlah: '1',
    Satuan: 'unit',
    HargaEstimasi: '',
    BulanRencana: TANPA,
  });
  const manual = form.data.UsulanAsetId === MANUAL;
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      UsulanAsetId: data.UsulanAsetId === MANUAL ? null : data.UsulanAsetId,
      SukuCadangId: data.SukuCadangId === TANPA ? null : data.SukuCadangId,
      Deskripsi: data.Deskripsi || null,
      Jumlah: manual ? data.Jumlah : null,
      Satuan: manual ? data.Satuan : null,
      HargaEstimasi: data.HargaEstimasi || null,
      BulanRencana: data.BulanRencana === TANPA ? null : data.BulanRencana,
    }));
    form.post(ruteRencanaPengadaan.detail2(rencana.Id), {
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
        <Button size="sm">
          <Plus /> Tambah Detail
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Detail Rencana</DialogTitle>
          <DialogDescription>
            Pilih usulan disetujui atau masukkan kebutuhan manual. Total dihitung dari jumlah × harga.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Sumber Usulan</Label>
            <Select
              value={form.data.UsulanAsetId}
              onValueChange={(value) => form.setData('UsulanAsetId', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={MANUAL}>Detail manual</SelectItem>
                {usulanDisetujui.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Nomor} — {item.NamaKebutuhan}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Suku Cadang (opsional)</Label>
            <Select
              value={form.data.SukuCadangId}
              onValueChange={(value) => form.setData('SukuCadangId', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tidak terkait suku cadang</SelectItem>
                {sukuCadang.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Kode} — {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {manual && (
            <>
              <div className="space-y-1.5">
                <Label>Deskripsi</Label>
                <Input
                  value={form.data.Deskripsi}
                  onChange={(event) => form.setData('Deskripsi', event.target.value)}
                />
                {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label>Jumlah</Label>
                  <Input
                    type="number"
                    min="0.0001"
                    step="0.0001"
                    value={form.data.Jumlah}
                    onChange={(event) => form.setData('Jumlah', event.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label>Satuan</Label>
                  <Input
                    value={form.data.Satuan}
                    onChange={(event) => form.setData('Satuan', event.target.value)}
                  />
                </div>
              </div>
            </>
          )}
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Harga Estimasi</Label>
              <Input
                type="number"
                min="0"
                step="0.01"
                value={form.data.HargaEstimasi}
                onChange={(event) => form.setData('HargaEstimasi', event.target.value)}
                placeholder={manual ? '' : 'Gunakan harga usulan'}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Bulan Rencana</Label>
              <Select
                value={form.data.BulanRencana}
                onValueChange={(value) => form.setData('BulanRencana', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Belum ditentukan</SelectItem>
                  {BULAN.map((item, index) => (
                    <SelectItem key={item} value={(index + 1).toString()}>
                      {item}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Tambah Detail
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function RencanaPengadaanShow({ rencana, posAnggaran, usulanDisetujui, sukuCadang }: Props) {
  const konfirmasi = useKonfirmasi();
  const detail = rencana.Detail ?? [];
  const draft = rencana.Status === 'Draft';
  async function hapusDetail(item: DetailRencanaPengadaan): Promise<void> {
    if (
      await konfirmasi({
        judul: `Hapus detail ${item.Deskripsi}?`,
        deskripsi: `Total estimasi akan dihitung ulang.`,
        ragam: 'bahaya',
      })
    )
      router.delete(ruteRencanaPengadaan.detailDetail(rencana.Id, item.Id), {
        preserveScroll: true,
      });
  }
  async function finalisasi(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Finalisasi ${rencana.Nomor}?`,
        deskripsi: `Rencana tidak dapat diubah lagi.`,
        ragam: 'perhatian',
      })
    )
      router.post(ruteRencanaPengadaan.finalisasi(rencana.Id));
  }
  async function hapus(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Hapus draft rencana ${rencana.Nomor} beserta seluruh detailnya?`,
        deskripsi: 'Usulan yang terhubung kembali tersedia untuk rencana lain.',
        ragam: 'bahaya',
      })
    )
      router.delete(ruteRencanaPengadaan.detail(rencana.Id));
  }
  return (
    <KerangkaAplikasi>
      <Head title={`${rencana.Nomor} — Rencana Pengadaan`} />
      <div className="space-y-6">
        <Link
          href={ruteRencanaPengadaan.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Rencana Pengadaan
        </Link>
        <KepalaHalaman
          judul={rencana.Nama}
          labelBreadcrumb={rencana.Nomor}
          lencana={<Badge variant={VARIAN_STATUS[rencana.Status]}>{rencana.Status}</Badge>}
          deskripsi={
            <>
              <span className="font-mono">
                {rencana.Nomor} · {rencana.Tahun}
              </span>{' '}
              · {rencana.NamaPosAnggaran ?? 'Pos anggaran belum dipilih'} · dibuat oleh{' '}
              {rencana.NamaPembuat ?? '—'}
            </>
          }
          aksi={
            draft ? (
              <>
                <DialogUbahRencana rencana={rencana} posAnggaran={posAnggaran} />
                <Button size="sm" onClick={finalisasi}>
                  <CheckCircle2 /> Finalisasi
                </Button>
                <Button size="sm" variant="outline" onClick={hapus}>
                  <Trash2 /> Hapus
                </Button>
              </>
            ) : undefined
          }
        />
        <div className="grid gap-3 sm:grid-cols-3">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-xs uppercase text-muted-foreground">Total Estimasi</CardTitle>
            </CardHeader>
            <CardContent className="font-mono text-xl font-semibold">
              {formatUang(rencana.TotalEstimasi)}
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-xs uppercase text-muted-foreground">Jumlah Detail</CardTitle>
            </CardHeader>
            <CardContent className="font-mono text-xl font-semibold">{detail.length}</CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-xs uppercase text-muted-foreground">Pos Anggaran</CardTitle>
            </CardHeader>
            <CardContent className="truncate text-base font-semibold">
              {rencana.NamaPosAnggaran ?? 'Belum dipilih'}
            </CardContent>
          </Card>
        </div>
        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="flex items-center justify-between gap-3 border-b border-border p-4">
            <div>
              <h2 className="font-semibold">Detail Pengadaan</h2>
              <p className="text-xs text-muted-foreground">Estimasi dihitung secara server-side.</p>
            </div>
            {draft && (
              <DialogTambahDetail
                rencana={rencana}
                usulanDisetujui={usulanDisetujui}
                sukuCadang={sukuCadang}
              />
            )}
          </div>
          {detail.length === 0 ? (
            <p className="p-8 text-center text-sm text-muted-foreground">
              Belum ada detail. Tambahkan minimal satu detail sebelum finalisasi.
            </p>
          ) : (
            <>
              <div className="hidden overflow-x-auto md:block">
                <table className="min-w-[800px] w-full text-sm">
                  <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                      <th className="px-4 py-3">Kebutuhan</th>
                      <th className="px-4 py-3 text-right">Jumlah</th>
                      <th className="px-4 py-3 text-right">Harga</th>
                      <th className="px-4 py-3 text-right">Subtotal</th>
                      <th className="px-4 py-3">Rencana</th>
                      {draft && <th className="px-4 py-3" />}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {detail.map((item) => (
                      <tr key={item.Id}>
                        <td className="px-4 py-3">
                          <p className="font-medium">{item.Deskripsi}</p>
                          <p className="font-mono text-xs text-muted-foreground">
                            {item.NomorUsulan ?? item.NamaSukuCadang ?? 'Detail manual'}
                          </p>
                        </td>
                        <td className="px-4 py-3 text-right font-mono">
                          {Number(item.Jumlah).toLocaleString('id-ID')} {item.Satuan}
                        </td>
                        <td className="px-4 py-3 text-right font-mono">
                          {item.HargaEstimasi ? formatUang(item.HargaEstimasi) : '—'}
                        </td>
                        <td className="px-4 py-3 text-right font-mono font-semibold">
                          {formatUang(Number(item.Jumlah) * Number(item.HargaEstimasi ?? 0))}
                        </td>
                        <td className="px-4 py-3">
                          {item.BulanRencana ? BULAN[item.BulanRencana - 1] : '—'}
                        </td>
                        {draft && (
                          <td className="px-4 py-3 text-right">
                            <Button
                              size="sm"
                              variant="ghost"
                              aria-label={`Hapus ${item.Deskripsi}`}
                              onClick={() => hapusDetail(item)}
                            >
                              <Trash2 />
                            </Button>
                          </td>
                        )}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="divide-y divide-border md:hidden">
                {detail.map((item) => (
                  <div key={item.Id} className="space-y-2 p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="font-medium">{item.Deskripsi}</p>
                        <p className="font-mono text-xs text-muted-foreground">
                          {item.NomorUsulan ?? 'Detail manual'}
                        </p>
                      </div>
                      {draft && (
                        <Button size="sm" variant="ghost" onClick={() => hapusDetail(item)}>
                          <Trash2 />
                        </Button>
                      )}
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">
                        {Number(item.Jumlah).toLocaleString('id-ID')} {item.Satuan}
                      </span>
                      <span className="font-mono font-semibold">
                        {formatUang(Number(item.Jumlah) * Number(item.HargaEstimasi ?? 0))}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </section>
      </div>
    </KerangkaAplikasi>
  );
}
