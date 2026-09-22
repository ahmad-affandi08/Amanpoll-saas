import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Keluhan, PrioritasKeluhan, StatusKeluhan } from '@/features/Keluhan/types';
import { VARIAN_PRIORITAS_KELUHAN, VARIAN_STATUS_KELUHAN } from '@/features/Keluhan/status';
import { ruteKeluhan } from '@/features/Keluhan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  keluhan: Keluhan;
  dapatMengelola: boolean;
  transisiDiizinkan: StatusKeluhan[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}
function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—';
}

function DialogStatus({
  keluhan,
  transisi,
  wajib,
}: {
  keluhan: Keluhan;
  transisi: StatusKeluhan[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Status: transisi[0] ?? keluhan.Status, Catatan: '', Versi: keluhan.Versi });
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(ruteKeluhan.status(keluhan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button disabled={transisi.length === 0}>Ubah Status</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Status Keluhan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Status">Status berikutnya</Label>
              <Select
                value={form.data.Status}
                onValueChange={(v) => form.setData('Status', v as StatusKeluhan)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {transisi.map((s) => (
                    <SelectItem key={s} value={s}>
                      {s}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label nama="Status">
                Catatan {(form.data.Status === 'Ditolak' || form.data.Status === 'Dibatalkan') && '(wajib)'}
              </Label>
              <Textarea
                rows={4}
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
              />
              {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Status
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogPrioritas({ keluhan, wajib }: { keluhan: Keluhan; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Prioritas: keluhan.Prioritas, Alasan: '', Versi: keluhan.Versi });
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(ruteKeluhan.prioritas(keluhan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Ubah Prioritas</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ubah Prioritas</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Prioritas">Prioritas</Label>
              <Select
                value={form.data.Prioritas}
                onValueChange={(v) => form.setData('Prioritas', v as PrioritasKeluhan)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {(['Rendah', 'Normal', 'Tinggi', 'Kritis'] as PrioritasKeluhan[]).map((p) => (
                    <SelectItem key={p} value={p}>
                      {p}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan perubahan</Label>
              <Textarea value={form.data.Alasan} onChange={(e) => form.setData('Alasan', e.target.value)} />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Terapkan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function KeluhanShow({ keluhan, dapatMengelola, transisiDiizinkan, wajib }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title={keluhan.Nomor} />
      <div className="mb-5">
        <Link href={ruteKeluhan.index} className="text-sm text-muted-foreground hover:text-foreground">
          ← Kembali ke Keluhan
        </Link>
      </div>
      <KepalaHalaman
        className="mb-6"
        judul={keluhan.Judul}
        labelBreadcrumb={keluhan.Nomor}
        lencana={
          <>
            <span className="font-mono text-sm text-muted-foreground">{keluhan.Nomor}</span>
            <Badge variant={VARIAN_PRIORITAS_KELUHAN[keluhan.Prioritas]}>{keluhan.Prioritas}</Badge>
            <Badge variant={VARIAN_STATUS_KELUHAN[keluhan.Status]}>{keluhan.Status}</Badge>
          </>
        }
        deskripsi={
          <>
            Dilaporkan {formatTanggal(keluhan.DilaporkanPada)} oleh {keluhan.NamaPelapor}
          </>
        }
        aksi={
          <>
            {dapatMengelola && <DialogPrioritas keluhan={keluhan} wajib={wajib.prioritas} />}
            <DialogStatus
              keluhan={keluhan}
              transisi={
                dapatMengelola ? transisiDiizinkan : transisiDiizinkan.filter((s) => s === 'Dibatalkan')
              }
              wajib={wajib.status}
            />
          </>
        }
      />
      <div className="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Detail Keluhan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="whitespace-pre-wrap text-sm leading-6">{keluhan.Deskripsi}</p>
              <dl className="grid gap-4 border-t border-border pt-4 text-sm sm:grid-cols-2">
                <div>
                  <dt className="text-muted-foreground">Kategori</dt>
                  <dd className="font-medium">{keluhan.NamaKategori}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Lokasi</dt>
                  <dd className="font-medium">{keluhan.NamaLokasi}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Aset</dt>
                  <dd className="font-medium">
                    {keluhan.NamaAset ? `${keluhan.KodeAset} · ${keluhan.NamaAset}` : 'Tidak terkait aset'}
                  </dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Tingkat layanan</dt>
                  <dd className="font-medium">{keluhan.NamaTingkatLayanan ?? 'Tanpa SLA'}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
          {dapatMengelola && <PanelKolaborasi jenisEntitas="Keluhan" entitasId={keluhan.Id} />}
        </div>
        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>SLA</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="space-y-3 text-sm">
                <div>
                  <dt className="text-muted-foreground">Batas respons</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.BatasResponsPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Direspons</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.DiresponsPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Batas penyelesaian</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.BatasPenyelesaianPada)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Diselesaikan</dt>
                  <dd className="font-medium">{formatTanggal(keluhan.DiresolusikanPada)}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Riwayat Status</CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="space-y-4">
                {keluhan.RiwayatStatus.map((riwayat) => (
                  <li key={riwayat.Id} className="relative border-l-2 border-border pl-4">
                    <div className="font-medium text-sm">{riwayat.StatusSesudah}</div>
                    <div className="text-xs text-muted-foreground">
                      {formatTanggal(riwayat.DiubahPada)} · {riwayat.NamaPengubah ?? 'Sistem'}
                    </div>
                    {riwayat.Catatan && (
                      <p className="mt-1 text-sm text-muted-foreground">{riwayat.Catatan}</p>
                    )}
                  </li>
                ))}
              </ol>
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
