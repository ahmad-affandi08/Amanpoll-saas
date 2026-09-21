import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
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
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/shared/EmptyState';
import type { JenisMutasiStok, MutasiStok } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_MUTASI_STOK } from '@/features/Persediaan/status';
import { ruteMutasiStok } from '@/features/MutasiStok/api';

interface Ringkas {
  Id: string;
  Nama: string;
}

interface Props {
  mutasiStok: MutasiStok[];
  gudang: Ringkas[];
  filter: { status?: string; jenis?: string };
}

const TANPA = '__tanpa__';

const LABEL_JENIS: Record<JenisMutasiStok, string> = {
  Penerimaan: 'Penerimaan',
  Pengeluaran: 'Pengeluaran',
  Transfer: 'Transfer',
  Adjustment: 'Penyesuaian',
  Return: 'Retur',
};

function butuhGudangAsal(jenis: JenisMutasiStok): boolean {
  return jenis === 'Pengeluaran' || jenis === 'Adjustment' || jenis === 'Transfer';
}

function butuhGudangTujuan(jenis: JenisMutasiStok): boolean {
  return jenis === 'Penerimaan' || jenis === 'Return' || jenis === 'Transfer';
}

function DialogBuatMutasi({ gudang }: { gudang: Ringkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Jenis: 'Penerimaan' as JenisMutasiStok,
    GudangAsalId: TANPA,
    GudangTujuanId: TANPA,
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteMutasiStok.index,
      {
        Jenis: form.data.Jenis,
        GudangAsalId: form.data.GudangAsalId === TANPA ? null : form.data.GudangAsalId,
        GudangTujuanId: form.data.GudangTujuanId === TANPA ? null : form.data.GudangTujuanId,
        Catatan: form.data.Catatan || null,
      },
      { onSuccess: () => setBuka(false) },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Mutasi Stok</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Buat Mutasi Stok</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Jenis</Label>
            <Select
              value={form.data.Jenis}
              onValueChange={(v) => form.setData('Jenis', v as JenisMutasiStok)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {(Object.keys(LABEL_JENIS) as JenisMutasiStok[]).map((j) => (
                  <SelectItem key={j} value={j}>
                    {LABEL_JENIS[j]}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {butuhGudangAsal(form.data.Jenis) && (
            <div className="space-y-1.5">
              <Label>Gudang Asal</Label>
              <Select value={form.data.GudangAsalId} onValueChange={(v) => form.setData('GudangAsalId', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih gudang" />
                </SelectTrigger>
                <SelectContent>
                  {gudang.map((g) => (
                    <SelectItem key={g.Id} value={g.Id}>
                      {g.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
          {butuhGudangTujuan(form.data.Jenis) && (
            <div className="space-y-1.5">
              <Label>Gudang Tujuan</Label>
              <Select
                value={form.data.GudangTujuanId}
                onValueChange={(v) => form.setData('GudangTujuanId', v)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih gudang" />
                </SelectTrigger>
                <SelectContent>
                  {gudang.map((g) => (
                    <SelectItem key={g.Id} value={g.Id}>
                      {g.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
          <div className="space-y-1.5">
            <Label>Catatan {form.data.Jenis === 'Adjustment' && '(alasan penyesuaian, wajib)'}</Label>
            <Textarea
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
              rows={3}
            />
            {form.errors.Catatan && <p className="text-sm text-destructive">{form.errors.Catatan}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Buat Draft
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function MutasiStokIndex({ mutasiStok, gudang }: Props) {
  return (
    <AppLayout>
      <Head title="Mutasi Stok" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Mutasi Stok</h1>
          <p className="text-sm text-muted-foreground">
            Penerimaan, pengeluaran, transfer, penyesuaian, dan retur -- draf, posting, sampai audit.
          </p>
        </div>
        <DialogBuatMutasi gudang={gudang} />
      </div>

      {mutasiStok.length === 0 ? (
        <EmptyState
          ilustrasi="/assets/3d/persediaan.webp"
          judul="Belum ada mutasi stok."
          deskripsi="Buat mutasi pertama untuk mulai mencatat pergerakan stok."
        />
      ) : (
        <div className="space-y-2">
          {mutasiStok.map((m) => (
            <Link
              key={m.Id}
              href={ruteMutasiStok.detail(m.Id)}
              className="flex items-center justify-between rounded-[9px] border border-border bg-card p-4 hover:border-teknisi-600/40"
            >
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-sm text-muted-foreground">{m.Nomor}</span>
                  <Badge variant="netral">{LABEL_JENIS[m.Jenis]}</Badge>
                </div>
                <div className="mt-1 text-sm text-foreground">
                  {m.NamaGudangAsal && <span>{m.NamaGudangAsal}</span>}
                  {m.NamaGudangAsal && m.NamaGudangTujuan && <span className="mx-1">&rarr;</span>}
                  {m.NamaGudangTujuan && <span>{m.NamaGudangTujuan}</span>}
                </div>
                <div className="text-xs text-muted-foreground">
                  {new Date(m.Tanggal).toLocaleString('id-ID')} &middot; {m.NamaDibuatOleh}
                </div>
              </div>
              <Badge variant={VARIAN_BADGE_STATUS_MUTASI_STOK[m.Status]}>{m.Status}</Badge>
            </Link>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
