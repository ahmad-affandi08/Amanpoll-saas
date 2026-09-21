import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
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
import { EmptyState } from '@/components/shared/EmptyState';
import type { PermintaanMutasiAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_MUTASI } from '@/features/SiklusAset/status';
import type { Aset } from '@/features/Aset/types';
import { ruteMutasiAset } from '@/features/MutasiAset/api';

interface Props {
  permintaan: PermintaanMutasiAset;
  aset: Aset[];
}

function DialogTambahAset({ permintaan, aset }: { permintaan: PermintaanMutasiAset; aset: Aset[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', Catatan: '' });

  const asetTersedia = aset.filter((a) => !permintaan.DetailMutasiAset.some((d) => d.AsetId === a.Id));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteMutasiAset.detail2(permintaan.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
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
          <DialogTitle>Tambah Aset ke Mutasi</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Aset</Label>
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
            {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing || !form.data.AsetId}>
              Tambah
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function MutasiAsetShow({ permintaan, aset }: Props) {
  const hapusDetail = (detailId: string) => {
    if (!confirm('Hapus aset ini dari daftar mutasi?')) return;
    router.delete(ruteMutasiAset.detailDetail(detailId), { preserveScroll: true });
  };

  const submit = () => router.post(ruteMutasiAset.submit(permintaan.Id), {}, { preserveScroll: true });
  const batalkan = () => {
    if (!confirm('Batalkan permintaan mutasi ini?')) return;
    router.post(ruteMutasiAset.batalkan(permintaan.Id), {}, { preserveScroll: true });
  };
  const eksekusi = () => {
    if (!confirm('Eksekusi mutasi ini? Lokasi/unit aset akan diperbarui.')) return;
    router.post(ruteMutasiAset.eksekusi(permintaan.Id), {}, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title={permintaan.Nomor} />
      <div className="space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="font-mono text-sm text-muted-foreground">{permintaan.Nomor}</p>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">
              {permintaan.JenisMutasi}
            </h1>
            <p className="text-sm text-muted-foreground">
              {permintaan.NamaLokasiAsal ?? '—'} →{' '}
              {permintaan.NamaLokasiTujuan ?? permintaan.NamaUnitTujuan ?? '—'}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant={VARIAN_BADGE_STATUS_MUTASI[permintaan.Status]}>{permintaan.Status}</Badge>
            {permintaan.Status === 'Draft' && (
              <Button size="sm" onClick={submit}>
                Submit
              </Button>
            )}
            {(permintaan.Status === 'Draft' || permintaan.Status === 'Menunggu') && (
              <Button size="sm" variant="outline" onClick={batalkan}>
                Batalkan
              </Button>
            )}
            {permintaan.Status === 'Disetujui' && (
              <Button size="sm" onClick={eksekusi}>
                Eksekusi
              </Button>
            )}
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diminta Oleh</p>
            <p className="text-sm font-medium text-foreground">{permintaan.NamaDimintaOleh ?? '—'}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diminta Pada</p>
            <p className="text-sm font-medium text-foreground">
              {new Date(permintaan.DimintaPada).toLocaleString('id-ID')}
            </p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Selesai Pada</p>
            <p className="text-sm font-medium text-foreground">
              {permintaan.SelesaiPada ? new Date(permintaan.SelesaiPada).toLocaleString('id-ID') : '—'}
            </p>
          </div>
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Daftar Aset</h2>
            {permintaan.Status === 'Draft' && <DialogTambahAset permintaan={permintaan} aset={aset} />}
          </div>
          {permintaan.DetailMutasiAset.length === 0 && (
            <EmptyState
              judul="Belum ada aset ditambahkan."
              deskripsi="Tambahkan aset yang akan dimutasi sebelum submit."
            />
          )}
          <div className="space-y-2">
            {permintaan.DetailMutasiAset.map((d) => (
              <div
                key={d.Id}
                className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
              >
                <div>
                  <span className="font-medium text-foreground">{d.NamaAset ?? '—'}</span>
                  <span className="ml-2 font-mono text-xs text-muted-foreground">{d.KodeAset}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Badge
                    variant={
                      d.Status === 'Selesai' ? 'sukses' : d.Status === 'Dibatalkan' ? 'netral' : 'perhatian'
                    }
                  >
                    {d.Status}
                  </Badge>
                  {permintaan.Status === 'Draft' && (
                    <Button variant="ghost" size="sm" onClick={() => hapusDetail(d.Id)}>
                      Hapus
                    </Button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
