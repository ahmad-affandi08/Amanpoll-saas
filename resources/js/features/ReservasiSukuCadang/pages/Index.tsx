import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
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
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import type { ReservasiSukuCadang } from '@/features/Persediaan/types';
import type { Paginasi } from '@/types/global';
import { VARIAN_BADGE_STATUS_RESERVASI } from '@/features/Persediaan/status';
import { ruteReservasiSukuCadang } from '@/features/ReservasiSukuCadang/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

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
  reservasi: Paginasi<ReservasiSukuCadang>;
  gudang: Ringkas[];
  sukuCadang: SukuCadangRingkas[];
  filter: { status?: string };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogBuatReservasi({
  gudang,
  sukuCadang,
  wajib,
}: {
  gudang: Ringkas[];
  sukuCadang: SukuCadangRingkas[];
  wajib: AturanWajib;
}) {
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="GudangId">Gudang</Label>
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
              <Label nama="SukuCadangId">Suku Cadang</Label>
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
                <Label nama="Jumlah">Jumlah</Label>
                <Input
                  type="number"
                  min={0}
                  value={form.data.Jumlah}
                  onChange={(e) => form.setData('Jumlah', e.target.value)}
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="KadaluarsaPada">Kadaluarsa Pada (opsional)</Label>
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

/** Hanya penyaring yang benar-benar terisi yang ikut dibawa saat berpindah halaman. */
function filterAktif(filter: Props['filter']): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filter).filter((pasangan): pasangan is [string, string] => Boolean(pasangan[1])),
  );
}

export default function ReservasiSukuCadangIndex({ reservasi, gudang, sukuCadang, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const lepaskan = async (item: ReservasiSukuCadang) => {
    if (
      !(await konfirmasi({
        judul: 'Lepas reservasi ini?',
        deskripsi: 'Hold stok akan dikembalikan.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(ruteReservasiSukuCadang.lepaskan(item.Id), {}, { preserveScroll: true });
  };

  const konsumsi = async (item: ReservasiSukuCadang) => {
    if (
      !(await konfirmasi({
        judul: 'Pakai reservasi ini?',
        deskripsi: 'Stok fisik akan berkurang lewat mutasi Pengeluaran.',
        ragam: 'perhatian',
      }))
    )
      return;
    router.post(ruteReservasiSukuCadang.konsumsi(item.Id), {}, { preserveScroll: true });
  };

  return (
    <KerangkaAplikasi>
      <Head title="Reservasi Suku Cadang" />
      <KepalaHalaman
        judul="Reservasi Suku Cadang"
        deskripsi="Menahan stok tersedia untuk kebutuhan mendatang tanpa mengurangi stok fisik."
        aksi={
          <>
            <DialogBuatReservasi gudang={gudang} sukuCadang={sukuCadang} wajib={wajib.reservasi} />
          </>
        }
        className="mb-6"
      />

      {reservasi.data.length === 0 ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada reservasi."
          deskripsi="Buat reservasi untuk menahan stok bagi kebutuhan mendatang."
        />
      ) : (
        <div className="space-y-2">
          {reservasi.data.map((r) => (
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
          <KontrolPaginasi
            meta={reservasi.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
          />
        </div>
      )}
    </KerangkaAplikasi>
  );
}
