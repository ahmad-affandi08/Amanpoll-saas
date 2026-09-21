import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/shared/EmptyState';
import type { Keluhan, PrioritasKeluhan, StatusKeluhan } from '@/features/Keluhan/types';
import { VARIAN_PRIORITAS_KELUHAN, VARIAN_STATUS_KELUHAN } from '@/features/Keluhan/status';
import { ruteKeluhan } from '@/features/Keluhan/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface KategoriRingkas {
  Id: string;
  Nama: string;
  PrioritasBawaan: PrioritasKeluhan;
  AsetWajib: boolean;
}
interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
  LokasiId: string | null;
}
interface Ringkas {
  Id: string;
  Nama: string;
}
interface Props {
  keluhan: Keluhan[];
  kategori: KategoriRingkas[];
  aset: AsetRingkas[];
  lokasi: Ringkas[];
  filter: { status?: string; prioritas?: string };
  dapatMengelola: boolean;
}
const TANPA = '__tanpa__';

function DialogBuatKeluhan({
  kategori,
  aset,
  lokasi,
  dapatMengelola,
}: Pick<Props, 'kategori' | 'aset' | 'lokasi' | 'dapatMengelola'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KategoriKeluhanId: '',
    AsetId: TANPA,
    LokasiId: '',
    Judul: '',
    Deskripsi: '',
    Prioritas: TANPA,
    Lampiran: [] as File[],
  });
  const kategoriDipilih = kategori.find((item) => item.Id === form.data.KategoriKeluhanId);

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      AsetId: data.AsetId === TANPA ? null : data.AsetId,
      Prioritas: data.Prioritas === TANPA ? null : data.Prioritas,
    }));
    form.post(ruteKeluhan.index, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Keluhan</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Buat Keluhan</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Kategori</Label>
            <Select
              value={form.data.KategoriKeluhanId}
              onValueChange={(value) => {
                form.setData('KategoriKeluhanId', value);
              }}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih kategori" />
              </SelectTrigger>
              <SelectContent>
                {kategori.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.KategoriKeluhanId && (
              <p className="text-sm text-destructive">{form.errors.KategoriKeluhanId}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Aset {kategoriDipilih?.AsetWajib ? '(wajib)' : '(opsional)'}</Label>
            <Select
              value={form.data.AsetId}
              onValueChange={(value) => {
                const dipilih = aset.find((item) => item.Id === value);
                form.setData((data) => ({
                  ...data,
                  AsetId: value,
                  LokasiId: dipilih?.LokasiId ?? data.LokasiId,
                }));
              }}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tanpa aset</SelectItem>
                {aset.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.KodeAset} · {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Lokasi</Label>
              <Select value={form.data.LokasiId} onValueChange={(value) => form.setData('LokasiId', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih lokasi" />
                </SelectTrigger>
                <SelectContent>
                  {lokasi.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.LokasiId && <p className="text-sm text-destructive">{form.errors.LokasiId}</p>}
            </div>
            {dapatMengelola && (
              <div className="space-y-1.5">
                <Label>Prioritas</Label>
                <Select
                  value={form.data.Prioritas}
                  onValueChange={(value) => form.setData('Prioritas', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={TANPA}>Gunakan bawaan kategori</SelectItem>
                    {(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => (
                      <SelectItem key={p} value={p}>
                        {p}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Judul</Label>
            <Input value={form.data.Judul} onChange={(e) => form.setData('Judul', e.target.value)} />
            {form.errors.Judul && <p className="text-sm text-destructive">{form.errors.Judul}</p>}
          </div>
          <div className="space-y-1.5">
            <Label>Deskripsi</Label>
            <Textarea
              rows={5}
              value={form.data.Deskripsi}
              onChange={(e) => form.setData('Deskripsi', e.target.value)}
            />
            {form.errors.Deskripsi && <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>}
          </div>
          <div className="space-y-1.5">
            <Label>Lampiran bukti (maksimal 5)</Label>
            <Input
              type="file"
              multiple
              accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
              onChange={(event) => form.setData('Lampiran', Array.from(event.target.files ?? []))}
            />
            {form.errors.Lampiran && <p className="text-sm text-destructive">{form.errors.Lampiran}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Kirim Keluhan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}
function labelSla(item: Keluhan): string {
  if (!item.BatasPenyelesaianPada) return 'Tanpa SLA';
  const lewat = !item.DiresolusikanPada && new Date(item.BatasPenyelesaianPada).getTime() < Date.now();
  return `${lewat ? 'Terlewati' : 'Batas'} ${formatTanggal(item.BatasPenyelesaianPada)}`;
}

export default function KeluhanIndex({ keluhan, kategori, aset, lokasi, filter, dapatMengelola }: Props) {
  const filterData = (kunci: 'status' | 'prioritas', nilai: string) =>
    router.get(
      ruteKeluhan.index,
      { ...filter, [kunci]: nilai === TANPA ? undefined : nilai },
      { preserveState: true, replace: true },
    );
  return (
    <AppLayout>
      <Head title="Keluhan" />
      <PageHeader
        judul="Keluhan"
        deskripsi={
          dapatMengelola
            ? 'Triage dan pantau keluhan beserta kepatuhan SLA.'
            : 'Laporkan masalah dan pantau status keluhan Anda.'
        }
        aksi={
          <>
            <DialogBuatKeluhan
              kategori={kategori}
              aset={aset}
              lokasi={lokasi}
              dapatMengelola={dapatMengelola}
            />
          </>
        }
        className="mb-6"
      />
      <div className="mb-4 grid gap-2 sm:grid-cols-2 lg:max-w-xl">
        <Select value={filter.status ?? TANPA} onValueChange={(value) => filterData('status', value)}>
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Semua status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA}>Semua status</SelectItem>
            {(
              [
                'Baru',
                'Ditinjau',
                'Diterima',
                'Diproses',
                'Selesai',
                'Ditutup',
                'Ditolak',
                'Dibatalkan',
              ] as StatusKeluhan[]
            ).map((s) => (
              <SelectItem key={s} value={s}>
                {s}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={filter.prioritas ?? TANPA} onValueChange={(value) => filterData('prioritas', value)}>
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Semua prioritas" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA}>Semua prioritas</SelectItem>
            {(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => (
              <SelectItem key={p} value={p}>
                {p}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      {keluhan.length === 0 ? (
        <EmptyState
          ilustrasi="/assets/3d/keluhan.webp"
          judul="Belum ada keluhan."
          deskripsi="Buat keluhan pertama agar masalah dapat segera ditindaklanjuti."
        />
      ) : (
        <div className="space-y-3">
          {keluhan.map((item) => (
            <Link
              key={item.Id}
              href={ruteKeluhan.detail(item.Id)}
              className="block rounded-[9px] border border-border bg-card p-4 transition-colors hover:border-teknisi-600/40"
            >
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-mono text-xs text-muted-foreground">{item.Nomor}</span>
                    <Badge variant={VARIAN_PRIORITAS_KELUHAN[item.Prioritas]}>{item.Prioritas}</Badge>
                    <Badge variant={VARIAN_STATUS_KELUHAN[item.Status]}>{item.Status}</Badge>
                  </div>
                  <h2 className="mt-2 truncate font-medium text-foreground">{item.Judul}</h2>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {item.NamaKategori} · {item.NamaLokasi} {item.NamaAset ? `· ${item.NamaAset}` : ''}
                  </p>
                </div>
                <div className="shrink-0 text-left text-xs text-muted-foreground sm:text-right">
                  <div>{formatTanggal(item.DilaporkanPada)}</div>
                  <div
                    className={
                      item.BatasPenyelesaianPada &&
                      !item.DiresolusikanPada &&
                      new Date(item.BatasPenyelesaianPada).getTime() < Date.now()
                        ? 'mt-1 text-destructive'
                        : 'mt-1'
                    }
                  >
                    {labelSla(item)}
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
