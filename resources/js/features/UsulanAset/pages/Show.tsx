import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Send, ShieldCheck, Star, Trash2 } from 'lucide-react';
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
import type { PrioritasUsulanAset, UsulanAset } from '@/features/UsulanAset/types';
import { formatUang } from '@/lib/uang';
import { ruteUsulanAset } from '@/features/UsulanAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Referensi {
  Id: string;
  Nama: string;
}
interface Keputusan {
  Keputusan: string;
  Catatan: string | null;
  NamaPenyetuju: string | null;
  DiputuskanPada: string | null;
}
interface Persetujuan {
  Id: string;
  Status: string;
  DimintaPada: string | null;
  SelesaiPada: string | null;
  Keputusan: Keputusan[];
}
interface CatatanAudit {
  Id: string;
  Aksi: string;
  PenggunaId: string | null;
  DibuatPada: string;
}
interface Props {
  usulan: UsulanAset;
  persetujuan: Persetujuan[];
  audit: CatatanAudit[];
  unitOrganisasi: Referensi[];
  kategoriAset: Referensi[];
  modelAset: Referensi[];
}

const TANPA = '__tanpa__';
const PRIORITAS: PrioritasUsulanAset[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];
const VARIAN_STATUS = {
  Draft: 'netral',
  Diajukan: 'info',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'sukses',
  Ditolak: 'bahaya',
} as const;

function DialogUbahUsulan({
  usulan,
  unitOrganisasi,
  kategoriAset,
  modelAset,
}: Pick<Props, 'usulan' | 'unitOrganisasi' | 'kategoriAset' | 'modelAset'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: usulan.UnitOrganisasiId,
    KategoriAsetId: usulan.KategoriAsetId ?? TANPA,
    ModelAsetId: usulan.ModelAsetId ?? TANPA,
    NamaKebutuhan: usulan.NamaKebutuhan,
    Jumlah: usulan.Jumlah,
    EstimasiHargaSatuan: usulan.EstimasiHargaSatuan ?? '',
    Alasan: usulan.Alasan,
    JenisKebutuhan: usulan.JenisKebutuhan ?? '',
    TahunKebutuhan: usulan.TahunKebutuhan?.toString() ?? '',
    Prioritas: usulan.Prioritas,
  });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KategoriAsetId: data.KategoriAsetId === TANPA ? null : data.KategoriAsetId,
      ModelAsetId: data.ModelAsetId === TANPA ? null : data.ModelAsetId,
      EstimasiHargaSatuan: data.EstimasiHargaSatuan || null,
      JenisKebutuhan: data.JenisKebutuhan || null,
      TahunKebutuhan: data.TahunKebutuhan || null,
    }));
    form.put(ruteUsulanAset.detail(usulan.Id), {
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
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Ubah Usulan</DialogTitle>
          <DialogDescription>
            Usulan hanya dapat diubah selama berstatus draft atau ditolak.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Unit</Label>
              <Select
                value={form.data.UnitOrganisasiId}
                onValueChange={(value) => form.setData('UnitOrganisasiId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {unitOrganisasi.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Prioritas</Label>
              <Select
                value={form.data.Prioritas}
                onValueChange={(value) => form.setData('Prioritas', value as PrioritasUsulanAset)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PRIORITAS.map((item) => (
                    <SelectItem key={item} value={item}>
                      {item}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Nama Kebutuhan</Label>
            <Input
              value={form.data.NamaKebutuhan}
              onChange={(event) => form.setData('NamaKebutuhan', event.target.value)}
            />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Kategori</Label>
              <Select
                value={form.data.KategoriAsetId}
                onValueChange={(value) => form.setData('KategoriAsetId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Belum ditentukan</SelectItem>
                  {kategoriAset.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Model</Label>
              <Select
                value={form.data.ModelAsetId}
                onValueChange={(value) => form.setData('ModelAsetId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Belum ditentukan</SelectItem>
                  {modelAset.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label>Jumlah</Label>
              <Input
                type="number"
                step="0.0001"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Harga / Unit</Label>
              <Input
                type="number"
                step="0.01"
                value={form.data.EstimasiHargaSatuan}
                onChange={(event) => form.setData('EstimasiHargaSatuan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Tahun</Label>
              <Input
                type="number"
                value={form.data.TahunKebutuhan}
                onChange={(event) => form.setData('TahunKebutuhan', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Jenis Kebutuhan</Label>
            <Input
              value={form.data.JenisKebutuhan}
              onChange={(event) => form.setData('JenisKebutuhan', event.target.value)}
            />
          </div>
          <div className="space-y-1.5">
            <Label>Alasan</Label>
            <Textarea
              rows={4}
              value={form.data.Alasan}
              onChange={(event) => form.setData('Alasan', event.target.value)}
            />
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

function DialogPenilaian({ usulan }: { usulan: UsulanAset }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kriteria: '', Bobot: '1', Nilai: '', Prioritas: usulan.Prioritas });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteUsulanAset.penilaian(usulan.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset('Kriteria', 'Nilai');
      },
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          <Star /> Tambah Penilaian
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Nilai Usulan</DialogTitle>
          <DialogDescription>
            Skor dihitung di server dari bobot × nilai dan prioritas usulan diperbarui.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Kriteria</Label>
            <Input
              value={form.data.Kriteria}
              onChange={(event) => form.setData('Kriteria', event.target.value)}
            />
            {form.errors.Kriteria && <p className="text-sm text-destructive">{form.errors.Kriteria}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Bobot</Label>
              <Input
                type="number"
                min="0.0001"
                max="100"
                step="0.0001"
                value={form.data.Bobot}
                onChange={(event) => form.setData('Bobot', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Nilai</Label>
              <Input
                type="number"
                min="0"
                max="100"
                step="0.0001"
                value={form.data.Nilai}
                onChange={(event) => form.setData('Nilai', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Prioritas Hasil</Label>
            <Select
              value={form.data.Prioritas}
              onValueChange={(value) => form.setData('Prioritas', value as PrioritasUsulanAset)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {PRIORITAS.map((item) => (
                  <SelectItem key={item} value={item}>
                    {item}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Penilaian
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function UsulanAsetShow({
  usulan,
  persetujuan,
  audit,
  unitOrganisasi,
  kategoriAset,
  modelAset,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const dapatUbah = usulan.Status === 'Draft' || usulan.Status === 'Ditolak';
  const dapatNilai = usulan.Status === 'Diajukan';
  async function submitUsulan(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Submit ${usulan.Nomor} untuk penilaian?`,
        deskripsi: 'Usulan terkunci dari perubahan selama proses penilaian.',
        ragam: 'perhatian',
      })
    )
      router.post(ruteUsulanAset.submit(usulan.Id));
  }
  async function ajukanPersetujuan(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Ajukan ${usulan.Nomor} ke alur persetujuan aktif?`,
        deskripsi: 'Penyetuju pada alur aktif akan menerima permintaan persetujuan.',
        ragam: 'perhatian',
      })
    )
      router.post(ruteUsulanAset.ajukanPersetujuan(usulan.Id));
  }
  async function hapus(): Promise<void> {
    if (
      await konfirmasi({
        judul: `Hapus usulan ${usulan.Nomor}?`,
        deskripsi: 'Seluruh penilaian pada usulan ini ikut terhapus.',
        ragam: 'bahaya',
      })
    )
      router.delete(ruteUsulanAset.detail(usulan.Id));
  }
  return (
    <AppLayout>
      <Head title={`${usulan.Nomor} — Usulan Aset`} />
      <div className="space-y-6">
        <Link
          href={ruteUsulanAset.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Usulan Aset
        </Link>
        <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p className="font-mono text-sm text-muted-foreground">{usulan.Nomor}</p>
            <div className="mt-1 flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-semibold tracking-tight">{usulan.NamaKebutuhan}</h1>
              <Badge variant={VARIAN_STATUS[usulan.Status]}>
                {usulan.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : usulan.Status}
              </Badge>
            </div>
            <p className="mt-1 text-sm text-muted-foreground">
              {usulan.NamaUnitOrganisasi} · diajukan oleh {usulan.NamaPengaju}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            {dapatUbah && (
              <DialogUbahUsulan
                usulan={usulan}
                unitOrganisasi={unitOrganisasi}
                kategoriAset={kategoriAset}
                modelAset={modelAset}
              />
            )}
            {usulan.Status === 'Draft' && (
              <Button size="sm" onClick={submitUsulan}>
                <Send /> Submit
              </Button>
            )}
            {dapatNilai && <DialogPenilaian usulan={usulan} />}
            {dapatNilai && (usulan.Penilaian?.length ?? 0) > 0 && (
              <Button size="sm" onClick={ajukanPersetujuan}>
                <ShieldCheck /> Ajukan Persetujuan
              </Button>
            )}
            {dapatUbah && (
              <Button size="sm" variant="outline" onClick={hapus}>
                <Trash2 /> Hapus
              </Button>
            )}
          </div>
        </header>
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          {[
            ['Jumlah', Number(usulan.Jumlah).toLocaleString('id-ID')],
            [
              'Estimasi / Unit',
              usulan.EstimasiHargaSatuan ? formatUang(usulan.EstimasiHargaSatuan) : 'Belum diisi',
            ],
            [
              'Total Estimasi',
              usulan.EstimasiHargaSatuan
                ? formatUang(Number(usulan.Jumlah) * Number(usulan.EstimasiHargaSatuan))
                : 'Belum diisi',
            ],
            ['Prioritas', usulan.Prioritas],
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
        <Card>
          <CardHeader>
            <CardTitle>Rincian Kebutuhan</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <p className="text-muted-foreground">Kategori / Model</p>
              <p className="font-medium">
                {usulan.NamaKategoriAset ?? 'Belum ditentukan'} / {usulan.NamaModelAset ?? 'Belum ditentukan'}
              </p>
            </div>
            <div>
              <p className="text-muted-foreground">Jenis / Tahun Kebutuhan</p>
              <p className="font-medium">
                {usulan.JenisKebutuhan ?? 'Belum ditentukan'} / {usulan.TahunKebutuhan ?? '—'}
              </p>
            </div>
            <div className="sm:col-span-2">
              <p className="text-muted-foreground">Alasan</p>
              <p className="mt-1 whitespace-pre-wrap">{usulan.Alasan}</p>
            </div>
          </CardContent>
        </Card>
        <section className="overflow-hidden rounded-[9px] border border-border bg-card">
          <div className="border-b border-border p-4">
            <h2 className="font-semibold">Penilaian</h2>
            <p className="text-xs text-muted-foreground">Riwayat kriteria dan skor berbobot.</p>
          </div>
          {(usulan.Penilaian?.length ?? 0) === 0 ? (
            <p className="p-8 text-center text-sm text-muted-foreground">Belum ada penilaian.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-[650px] w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Kriteria</th>
                    <th className="px-4 py-3 text-right">Bobot</th>
                    <th className="px-4 py-3 text-right">Nilai</th>
                    <th className="px-4 py-3 text-right">Skor</th>
                    <th className="px-4 py-3">Penilai</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {usulan.Penilaian?.map((item) => (
                    <tr key={item.Id}>
                      <td className="px-4 py-3 font-medium">{item.Kriteria}</td>
                      <td className="px-4 py-3 text-right font-mono">{item.Bobot}</td>
                      <td className="px-4 py-3 text-right font-mono">{item.Nilai}</td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">{item.Skor}</td>
                      <td className="px-4 py-3">{item.NamaPenilai ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Riwayat Persetujuan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {persetujuan.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum diajukan ke alur persetujuan.</p>
              ) : (
                persetujuan.map((item) => (
                  <div key={item.Id} className="rounded-md border border-border p-3 text-sm">
                    <div className="flex justify-between gap-3">
                      <Badge
                        variant={
                          item.Status === 'Disetujui'
                            ? 'sukses'
                            : item.Status === 'Ditolak'
                              ? 'bahaya'
                              : 'perhatian'
                        }
                      >
                        {item.Status}
                      </Badge>
                      <span className="text-xs text-muted-foreground">
                        {item.DimintaPada ? new Date(item.DimintaPada).toLocaleString('id-ID') : '—'}
                      </span>
                    </div>
                    {item.Keputusan.map((keputusan, index) => (
                      <p key={index} className="mt-2 text-muted-foreground">
                        {keputusan.NamaPenyetuju ?? 'Penyetuju'}: {keputusan.Keputusan}
                        {keputusan.Catatan ? ` — ${keputusan.Catatan}` : ''}
                      </p>
                    ))}
                  </div>
                ))
              )}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Jejak Audit</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {audit.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum ada catatan audit.</p>
              ) : (
                audit.map((item) => (
                  <div
                    key={item.Id}
                    className="flex justify-between gap-3 border-b border-border py-2 text-sm last:border-0"
                  >
                    <span>{item.Aksi}</span>
                    <span className="text-xs text-muted-foreground">
                      {new Date(item.DibuatPada).toLocaleString('id-ID')}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
