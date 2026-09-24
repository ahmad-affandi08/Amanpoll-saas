import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { dariMasukanWaktu } from '@/lib/waktu';

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
      { ...form.data, KadaluarsaPada: dariMasukanWaktu(form.data.KadaluarsaPada) },
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
              <Combobox
                nilai={form.data.GudangId}
                onPilih={(v) => form.setData('GudangId', v)}
                opsi={opsiDari(gudang, (g) => g.Nama)}
                placeholder="Pilih gudang"
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="SukuCadangId">Suku Cadang</Label>
              <Combobox
                nilai={form.data.SukuCadangId}
                onPilih={(v) => form.setData('SukuCadangId', v)}
                opsi={opsiDari(sukuCadang, (s) => `${s.Nama} (${s.Kode})`)}
                placeholder="Pilih suku cadang"
              />
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
            <TombolEkspor url={ruteReservasiSukuCadang.ekspor} filter={filter as Record<string, string>} />
            <DialogBuatReservasi gudang={gudang} sukuCadang={sukuCadang} wajib={wajib.reservasi} />
          </>
        }
        className="mb-5"
      />

      {reservasi.data.length === 0 ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/suku-cadang.webp"
          judul="Belum ada reservasi."
          deskripsi="Buat reservasi untuk menahan stok bagi kebutuhan mendatang."
        />
      ) : (
        <div className="overflow-hidden rounded-md border border-border bg-card">
          <div className="divide-y divide-border">
            {reservasi.data.map((r) => (
              <div key={r.Id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
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
          <KontrolPaginasi
            meta={reservasi.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
          />
        </div>
      )}
    </KerangkaAplikasi>
  );
}
