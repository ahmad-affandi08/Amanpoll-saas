import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ClipboardCheck, Plus, Search, ShieldCheck, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
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
import { Textarea } from '@/components/ui/textarea';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type { Paginasi } from '@/types/global';
import type {
  KepatuhanAset,
  RingkasanKepatuhan,
  StandarKepatuhan,
  StatusKepatuhan,
} from '@/features/Kepatuhan/types';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
interface Props {
  kewajiban: Paginasi<KepatuhanAset>;
  standar: StandarKepatuhan[];
  aset: AsetRingkas[];
  ringkasan: RingkasanKepatuhan;
  filter: { cari?: string; status?: StatusKepatuhan };
}

const SEMUA = '__semua__';
const STATUS: StatusKepatuhan[] = ['BelumDiperiksa', 'Patuh', 'TidakPatuh', 'Kedaluwarsa'];
const VARIAN_STATUS = {
  BelumDiperiksa: 'netral',
  Patuh: 'sukses',
  TidakPatuh: 'bahaya',
  Kedaluwarsa: 'perhatian',
} as const;

function DialogBuatStandar() {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    Penerbit: '',
    VersiStandar: '',
    JenisIndustri: '',
    Deskripsi: '',
    Aktif: true,
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Penerbit: data.Penerbit || null,
      VersiStandar: data.VersiStandar || null,
      JenisIndustri: data.JenisIndustri || null,
      Deskripsi: data.Deskripsi || null,
    }));
    form.post(ruteKepatuhan.standar, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Standar
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Standar Kepatuhan</DialogTitle>
          <DialogDescription>
            Amanpoll tidak membawa katalog standar bawaan. Daftarkan standar yang benar-benar berlaku bagi
            organisasi Anda beserta versinya.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
            <div className="space-y-1.5">
              <Label htmlFor="KodeStandar">Kode</Label>
              <Input
                id="KodeStandar"
                value={form.data.Kode}
                onChange={(event) => form.setData('Kode', event.target.value)}
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="NamaStandar">Nama</Label>
              <Input
                id="NamaStandar"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label htmlFor="Penerbit">Penerbit</Label>
              <Input
                id="Penerbit"
                value={form.data.Penerbit}
                onChange={(event) => form.setData('Penerbit', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="VersiStandar">Versi</Label>
              <Input
                id="VersiStandar"
                value={form.data.VersiStandar}
                onChange={(event) => form.setData('VersiStandar', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="JenisIndustri">Lingkup / Industri</Label>
              <Input
                id="JenisIndustri"
                value={form.data.JenisIndustri}
                onChange={(event) => form.setData('JenisIndustri', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="DeskripsiStandar">Deskripsi</Label>
            <Textarea
              id="DeskripsiStandar"
              rows={2}
              value={form.data.Deskripsi}
              onChange={(event) => form.setData('Deskripsi', event.target.value)}
            />
          </div>
          <div className="flex items-center justify-between rounded-[9px] border border-border p-3">
            <div>
              <p className="text-sm font-medium">Standar aktif</p>
              <p className="text-xs text-muted-foreground">
                Standar nonaktif tidak dapat ditugaskan ke aset baru.
              </p>
            </div>
            <Switch checked={form.data.Aktif} onCheckedChange={(nilai) => form.setData('Aktif', nilai)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Standar
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogTugaskan({ standar, aset }: Pick<Props, 'standar' | 'aset'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', StandarKepatuhanId: '' });
  const standarAktif = standar.filter((item) => item.Aktif && (item.JumlahPersyaratan ?? 0) > 0);

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKepatuhan.tugaskan, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" className="min-h-11 sm:min-h-9">
          <ShieldCheck /> Tugaskan ke Aset
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Tugaskan Standar</DialogTitle>
          <DialogDescription>
            Seluruh persyaratan pada standar akan menjadi kewajiban kepatuhan aset yang dipilih.
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
          <div className="space-y-1.5">
            <Label>Standar</Label>
            {standarAktif.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                Belum ada standar aktif yang memiliki persyaratan.
              </p>
            ) : (
              <Select
                value={form.data.StandarKepatuhanId}
                onValueChange={(value) => form.setData('StandarKepatuhanId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih standar" />
                </SelectTrigger>
                <SelectContent>
                  {standarAktif.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Kode} — {item.Nama} ({item.JumlahPersyaratan} persyaratan)
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            {form.errors.StandarKepatuhanId && (
              <p className="text-sm text-destructive">{form.errors.StandarKepatuhanId}</p>
            )}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing || standarAktif.length === 0}>
              Tugaskan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogPemeriksaan({ kewajiban }: { kewajiban: KepatuhanAset }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: 'Patuh',
    TanggalPemeriksaan: new Date().toISOString().slice(0, 10),
    BerlakuSampai: '',
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      BerlakuSampai: data.BerlakuSampai || null,
      Catatan: data.Catatan || null,
    }));
    form.post(ruteKepatuhan.pemeriksaan(kewajiban.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          Periksa
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Pemeriksaan {kewajiban.KodePersyaratan}</DialogTitle>
          <DialogDescription>
            {kewajiban.BuktiYangDiperlukan
              ? `Bukti yang diperlukan: ${kewajiban.BuktiYangDiperlukan}`
              : 'Masa berlaku dihitung otomatis dari interval persyaratan bila dikosongkan.'}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Hasil</Label>
            <Select value={form.data.Status} onValueChange={(value) => form.setData('Status', value)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Patuh">Patuh</SelectItem>
                <SelectItem value="TidakPatuh">Tidak patuh</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="TanggalPemeriksaan">Tanggal periksa</Label>
              <Input
                id="TanggalPemeriksaan"
                type="date"
                value={form.data.TanggalPemeriksaan}
                onChange={(event) => form.setData('TanggalPemeriksaan', event.target.value)}
              />
              {form.errors.TanggalPemeriksaan && (
                <p className="text-sm text-destructive">{form.errors.TanggalPemeriksaan}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="BerlakuSampai">Berlaku sampai</Label>
              <Input
                id="BerlakuSampai"
                type="date"
                value={form.data.BerlakuSampai}
                onChange={(event) => form.setData('BerlakuSampai', event.target.value)}
              />
              {form.errors.BerlakuSampai && (
                <p className="text-sm text-destructive">{form.errors.BerlakuSampai}</p>
              )}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="CatatanPemeriksaan">Catatan</Label>
            <Textarea
              id="CatatanPemeriksaan"
              rows={2}
              value={form.data.Catatan}
              onChange={(event) => form.setData('Catatan', event.target.value)}
            />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Hasil
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KepatuhanIndex({ kewajiban, standar, aset, ringkasan, filter }: Props) {
  const konfirmasi = useKonfirmasi();
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteKepatuhan.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  async function lepaskan(item: KepatuhanAset): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Lepas persyaratan "${item.KodePersyaratan}" dari aset ${item.KodeAset}?`,
      deskripsi: 'Riwayat pemeriksaan pada kewajiban ini ikut terhapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKepatuhan.kewajibanDetail(item.Id), { preserveScroll: true });
  }

  return (
    <AppLayout>
      <Head title="Kepatuhan" />
      <div className="space-y-6">
        <PageHeader
          judul="Kepatuhan"
          deskripsi="Standar yang berlaku bagi organisasi, persyaratannya, dan status kepatuhan tiap aset."
          aksi={
            <>
              <div className="flex flex-wrap gap-2">
                <DialogTugaskan standar={standar} aset={aset} />
                <DialogBuatStandar />
              </div>
            </>
          }
        />

        <div className="grid gap-3 sm:grid-cols-4">
          {[
            { label: 'Kepatuhan', nilai: `${ringkasan.persentaseKepatuhan}%`, kelas: 'text-foreground' },
            { label: 'Belum diperiksa', nilai: ringkasan.belumDiperiksa, kelas: 'text-muted-foreground' },
            { label: 'Tidak patuh', nilai: ringkasan.tidakPatuh, kelas: 'text-bahaya-600' },
            { label: 'Kedaluwarsa', nilai: ringkasan.kedaluwarsa, kelas: 'text-safety-600' },
          ].map((kartu) => (
            <div key={kartu.label} className="rounded-[9px] border border-border bg-card p-4">
              <p className="text-xs text-muted-foreground">{kartu.label}</p>
              <p className={`mt-1 text-2xl font-semibold ${kartu.kelas}`}>{kartu.nilai}</p>
            </div>
          ))}
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Standar Terdaftar</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {standar.length === 0 ? (
              <EmptyState
                judul="Belum ada standar kepatuhan."
                deskripsi="Daftarkan standar yang berlaku bagi organisasi Anda, lalu rinci persyaratannya."
              />
            ) : (
              <div className="grid gap-2 sm:grid-cols-2">
                {standar.map((item) => (
                  <Link
                    key={item.Id}
                    href={ruteKepatuhan.standarDetail(item.Id)}
                    className="flex items-center justify-between rounded-[9px] border border-border p-3 transition hover:border-primary/40"
                  >
                    <div className="min-w-0">
                      <p className="truncate font-medium">{item.Nama}</p>
                      <p className="font-mono text-xs text-muted-foreground">
                        {item.Kode}
                        {item.VersiStandar ? ` · v${item.VersiStandar}` : ''} · {item.JumlahPersyaratan ?? 0}{' '}
                        persyaratan
                      </p>
                    </div>
                    <Badge variant={item.Aktif ? 'sukses' : 'netral'}>
                      {item.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_13rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari kode atau nama aset"
              placeholder="Cari kode atau nama aset"
              className="pl-9"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua status</SelectItem>
              {STATUS.map((item) => (
                <SelectItem key={item} value={item}>
                  {item}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {kewajiban.data.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/persetujuan-kepatuhan.webp"
            judul="Belum ada kewajiban kepatuhan."
            deskripsi="Tugaskan standar ke aset agar status kepatuhannya dapat dipantau."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Aset</th>
                    <th className="px-4 py-3">Persyaratan</th>
                    <th className="px-4 py-3">Diperiksa</th>
                    <th className="px-4 py-3">Berlaku sampai</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {kewajiban.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        {item.NamaAset}
                        <p className="font-mono text-xs text-muted-foreground">{item.KodeAset}</p>
                      </td>
                      <td className="px-4 py-3">
                        {item.NamaPersyaratan}
                        <p className="font-mono text-xs text-muted-foreground">{item.KodePersyaratan}</p>
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {item.TanggalPemeriksaan ?? '—'}
                        {item.NamaPemeriksa && (
                          <p className="text-muted-foreground">oleh {item.NamaPemeriksa}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {item.BerlakuSampai ?? 'Tanpa batas'}
                        {item.SisaHari !== null && item.SisaHari < 0 && (
                          <p className="text-bahaya-600">Lewat {Math.abs(item.SisaHari)} hari</p>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                          <DialogPemeriksaan kewajiban={item} />
                          <Button
                            size="icon"
                            variant="ghost"
                            aria-label={`Lepas ${item.KodePersyaratan}`}
                            onClick={() => lepaskan(item)}
                          >
                            <Trash2 />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {kewajiban.data.map((item) => (
                <div key={item.Id} className="space-y-2 p-4">
                  <div className="flex items-start gap-3">
                    <ClipboardCheck className="size-5 shrink-0 text-primary" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-medium">{item.NamaPersyaratan}</p>
                      <p className="truncate font-mono text-xs text-muted-foreground">
                        {item.KodeAset} · {item.KodePersyaratan}
                      </p>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Berlaku sampai {item.BerlakuSampai ?? 'tanpa batas'}
                      </p>
                    </div>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </div>
                  <div className="flex gap-2">
                    <DialogPemeriksaan kewajiban={item} />
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Lepas ${item.KodePersyaratan}`}
                      onClick={() => lepaskan(item)}
                    >
                      <Trash2 />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
            <Pagination
              meta={kewajiban.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
