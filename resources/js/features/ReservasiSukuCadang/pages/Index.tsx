import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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
import type { ReservasiSukuCadang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_RESERVASI } from '@/features/Persediaan/status';
import { ruteReservasiSukuCadang } from '@/features/ReservasiSukuCadang/api';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface SukuCadangRingkas {
  Id: string;
  Nama: string;
  Kode: string;
}

interface Props {
  reservasi: ReservasiSukuCadang[];
  gudang: Ringkas[];
  sukuCadang: SukuCadangRingkas[];
  filter: { status?: string };
}

function DialogBuatReservasi({ gudang, sukuCadang }: { gudang: Ringkas[]; sukuCadang: SukuCadangRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ GudangId: '', SukuCadangId: '', Jumlah: '', KadaluarsaPada: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteReservasiSukuCadang.index,
      { ...form.data, KadaluarsaPada: form.data.KadaluarsaPada || null },
      {
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Reservasi</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reservasi Suku Cadang</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Gudang</Label>
            <Select value={form.data.GudangId} onValueChange={(v) => form.setData('GudangId', v)}>
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
          <div className="space-y-1.5">
            <Label>Suku Cadang</Label>
            <Select value={form.data.SukuCadangId} onValueChange={(v) => form.setData('SukuCadangId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih suku cadang" />
              </SelectTrigger>
              <SelectContent>
                {sukuCadang.map((s) => (
                  <SelectItem key={s.Id} value={s.Id}>
                    {s.Nama} ({s.Kode})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Jumlah</Label>
              <Input
                type="number"
                min={0}
                value={form.data.Jumlah}
                onChange={(e) => form.setData('Jumlah', e.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Kadaluarsa Pada (opsional)</Label>
              <Input
                type="datetime-local"
                value={form.data.KadaluarsaPada}
                onChange={(e) => form.setData('KadaluarsaPada', e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              type="submit"
              disabled={form.processing || !form.data.GudangId || !form.data.SukuCadangId}
            >
              Reservasi
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function ReservasiSukuCadangIndex({ reservasi, gudang, sukuCadang }: Props) {
  const lepaskan = (item: ReservasiSukuCadang) => {
    if (!confirm('Lepas reservasi ini? Hold stok akan dikembalikan.')) return;
    router.post(ruteReservasiSukuCadang.lepaskan(item.Id), {}, { preserveScroll: true });
  };

  const konsumsi = (item: ReservasiSukuCadang) => {
    if (!confirm('Pakai reservasi ini? Stok fisik akan berkurang lewat mutasi Pengeluaran.')) return;
    router.post(ruteReservasiSukuCadang.konsumsi(item.Id), {}, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Reservasi Suku Cadang" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Reservasi Suku Cadang</h1>
          <p className="text-sm text-muted-foreground">
            Menahan stok tersedia untuk kebutuhan mendatang tanpa mengurangi stok fisik.
          </p>
        </div>
        <DialogBuatReservasi gudang={gudang} sukuCadang={sukuCadang} />
      </div>

      {reservasi.length === 0 ? (
        <EmptyState
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada reservasi."
          deskripsi="Buat reservasi untuk menahan stok bagi kebutuhan mendatang."
        />
      ) : (
        <div className="space-y-2">
          {reservasi.map((r) => (
            <div
              key={r.Id}
              className="flex items-center justify-between rounded-[9px] border border-border bg-card p-4"
            >
              <div>
                <div className="font-medium text-foreground">
                  {r.NamaSukuCadang}{' '}
                  <span className="font-mono text-xs text-muted-foreground">{r.KodeSukuCadang}</span>
                </div>
                <div className="text-sm text-muted-foreground">
                  {r.NamaGudang} &middot; {r.Jumlah} unit
                </div>
                {r.KadaluarsaPada && (
                  <div className="text-xs text-muted-foreground">
                    Kadaluarsa: {new Date(r.KadaluarsaPada).toLocaleString('id-ID')}
                  </div>
                )}
              </div>
              <div className="flex items-center gap-2">
                <Badge variant={VARIAN_BADGE_STATUS_RESERVASI[r.Status]}>{r.Status}</Badge>
                {r.Status === 'Aktif' && (
                  <>
                    <Button size="sm" onClick={() => konsumsi(r)}>
                      Pakai
                    </Button>
                    <Button size="sm" variant="outline" onClick={() => lepaskan(r)}>
                      Lepaskan
                    </Button>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </AppLayout>
  );
}
