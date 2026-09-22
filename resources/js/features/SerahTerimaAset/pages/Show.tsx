import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { SerahTerimaAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_SERAH_TERIMA } from '@/features/SiklusAset/status';
import type { Aset } from '@/features/Aset/types';
import { ruteSerahTerimaAset } from '@/features/SerahTerimaAset/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  serahTerima: SerahTerimaAset;
  aset: Aset[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const KONDISI = ['Baik', 'PerluPerhatian', 'Rusak'];

function DialogTambahAset({
  serahTerima,
  aset,
  wajib,
}: {
  serahTerima: SerahTerimaAset;
  aset: Aset[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', KondisiSaatDiserahkan: 'Baik' });

  const asetTersedia = aset.filter((a) => !serahTerima.DetailSerahTerimaAset.some((d) => d.AsetId === a.Id));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteSerahTerimaAset.detail2(serahTerima.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          Tambah Aset
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Aset</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset</Label>
              <Select value={form.data.AsetId} onValueChange={(v) => form.setData('AsetId', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih aset" />
                </SelectTrigger>
                <SelectContent>
                  {asetTersedia.map((a) => (
                    <SelectItem key={a.Id} value={a.Id}>
                      {a.Nama} ({a.KodeAset})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label nama="KondisiSaatDiserahkan">Kondisi Saat Diserahkan</Label>
              <Select
                value={form.data.KondisiSaatDiserahkan}
                onValueChange={(v) => form.setData('KondisiSaatDiserahkan', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {KONDISI.map((k) => (
                    <SelectItem key={k} value={k}>
                      {k}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing || !form.data.AsetId}>
                Tambah
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogTerima({ serahTerima, wajib }: { serahTerima: SerahTerimaAset; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const [kondisi, setKondisi] = useState<Record<string, string>>(() =>
    Object.fromEntries(
      serahTerima.DetailSerahTerimaAset.map((d) => [d.AsetId, d.KondisiSaatDiserahkan ?? 'Baik']),
    ),
  );
  const [memproses, setMemproses] = useState(false);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    setMemproses(true);
    router.post(
      ruteSerahTerimaAset.terima(serahTerima.Id),
      {
        Detail: Object.entries(kondisi).map(([AsetId, KondisiSaatDiterima]) => ({
          AsetId,
          KondisiSaatDiterima,
        })),
      },
      {
        preserveScroll: true,
        onSuccess: () => setBuka(false),
        onFinish: () => setMemproses(false),
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm">Terima</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Konfirmasi Penerimaan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            {serahTerima.DetailSerahTerimaAset.map((d) => (
              <div key={d.Id} className="space-y-1.5">
                <Label>
                  {d.NamaAset} ({d.KodeAset})
                </Label>
                <Select
                  value={kondisi[d.AsetId]}
                  onValueChange={(v) => setKondisi((k) => ({ ...k, [d.AsetId]: v }))}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {KONDISI.map((k) => (
                      <SelectItem key={k} value={k}>
                        {k}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            ))}
            <DialogFooter>
              <Button type="submit" disabled={memproses}>
                Konfirmasi Diterima
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function SerahTerimaAsetShow({ serahTerima, aset, wajib }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title={serahTerima.Nomor} />
      <div className="space-y-6">
        <KepalaHalaman
          judul={serahTerima.Jenis}
          labelBreadcrumb={serahTerima.Nomor}
          lencana={
            <Badge variant={VARIAN_BADGE_STATUS_SERAH_TERIMA[serahTerima.Status]}>{serahTerima.Status}</Badge>
          }
          deskripsi={
            <>
              <span className="font-mono">{serahTerima.Nomor}</span> ·{' '}
              {serahTerima.NamaPihakMenyerahkan ?? '—'} → {serahTerima.NamaPihakMenerima ?? '—'}
            </>
          }
          aksi={
            serahTerima.Status === 'Diserahkan' && serahTerima.DetailSerahTerimaAset.length > 0 ? (
              <DialogTerima serahTerima={serahTerima} wajib={wajib.terima} />
            ) : undefined
          }
        />

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diserahkan Pada</p>
            <p className="text-sm font-medium text-foreground">
              {serahTerima.DiserahkanPada
                ? new Date(serahTerima.DiserahkanPada).toLocaleString('id-ID')
                : '—'}
            </p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diterima Pada</p>
            <p className="text-sm font-medium text-foreground">
              {serahTerima.DiterimaPada ? new Date(serahTerima.DiterimaPada).toLocaleString('id-ID') : '—'}
            </p>
          </div>
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Daftar Aset</h2>
            {serahTerima.Status === 'Diserahkan' && (
              <DialogTambahAset serahTerima={serahTerima} aset={aset} wajib={wajib.detail} />
            )}
          </div>
          {serahTerima.DetailSerahTerimaAset.length === 0 && (
            <KeadaanKosong
              judul="Belum ada aset ditambahkan."
              deskripsi="Tambahkan aset yang diserahterimakan."
            />
          )}
          <div className="space-y-2">
            {serahTerima.DetailSerahTerimaAset.map((d) => (
              <div key={d.Id} className="rounded-md border border-border px-3 py-2 text-sm">
                <div className="flex items-center justify-between">
                  <span className="font-medium text-foreground">{d.NamaAset ?? '—'}</span>
                  <span className="font-mono text-xs text-muted-foreground">{d.KodeAset}</span>
                </div>
                <div className="mt-1 text-xs text-muted-foreground">
                  Diserahkan: {d.KondisiSaatDiserahkan ?? '—'}{' '}
                  {d.KondisiSaatDiterima && `· Diterima: ${d.KondisiSaatDiterima}`}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
