import { type FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { labelBatas, rupiah } from '@/features/Langganan/format';
import type { DefinisiFitur, PaketItem } from '@/features/Langganan/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { rutePlatform } from '@/features/Platform/api';
import { InputUang } from '@/components/shared/InputUang';

interface Props {
  paket: PaketItem[];
  katalogFitur: DefinisiFitur[];
}

interface BarisFiturForm {
  Kode: string;
  Diizinkan: boolean;
  BatasNilai: string;
}

export default function PlatformPaketIndex({ paket, katalogFitur }: Props) {
  const [sedangDisunting, setSedangDisunting] = useState<PaketItem | null>(null);
  const [dialogTerbuka, setDialogTerbuka] = useState(false);
  const konfirmasi = useKonfirmasi();

  const buka = (item: PaketItem | null) => {
    setSedangDisunting(item);
    setDialogTerbuka(true);
  };

  const hapus = async (item: PaketItem) => {
    const setuju = await konfirmasi({
      judul: `Hapus paket "${item.Nama}"?`,
      deskripsi: 'Paket yang masih dipakai langganan berjalan tidak dapat dihapus.',
    });

    if (setuju) {
      router.delete(rutePlatform.paketDetail(item.Id), { preserveScroll: true });
    }
  };

  return (
    <KerangkaPlatform>
      <Head title="Paket Langganan" />

      <div className="space-y-6">
        <KepalaHalaman
          tanpaBreadcrumb
          judul="Paket Langganan"
          deskripsi="Harga, masa berlaku, dan modul yang termasuk pada tiap paket."
          aksi={
            <>
              <Button onClick={() => buka(null)}>
                <Plus aria-hidden="true" className="size-4" />
                Paket baru
              </Button>
            </>
          }
        />

        <Card>
          <CardHeader>
            <CardTitle>Katalog paket</CardTitle>
          </CardHeader>
          <CardContent>
            {paket.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada paket."
                deskripsi="Buat paket pertama untuk mulai menjual langganan."
                aksi={
                  <Button onClick={() => buka(null)}>
                    <Plus aria-hidden="true" className="size-4" />
                    Paket baru
                  </Button>
                }
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Paket</TableHead>
                    <TableHead className="text-right">Bulanan</TableHead>
                    <TableHead className="text-right">Tahunan</TableHead>
                    <TableHead className="text-right">Pelanggan</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="w-0" />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paket.map((item) => (
                    <TableRow key={item.Id}>
                      <TableCell>
                        <div className="space-y-0.5">
                          <p className="font-medium text-foreground">{item.Nama}</p>
                          <p className="text-xs text-muted-foreground">{item.Kode}</p>
                        </div>
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {rupiah(item.HargaBulanan, item.MataUang)}
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {rupiah(item.HargaTahunan, item.MataUang)}
                      </TableCell>
                      <TableCell className="text-right tabular-nums">{item.JumlahLangganan}</TableCell>
                      <TableCell>
                        <Badge variant={item.Aktif ? 'default' : 'secondary'}>
                          {item.Aktif ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex justify-end gap-1">
                          <Button size="sm" variant="ghost" onClick={() => buka(item)}>
                            <Pencil aria-hidden="true" className="size-4" />
                            <span className="sr-only">Ubah {item.Nama}</span>
                          </Button>
                          <Button size="sm" variant="ghost" onClick={() => hapus(item)}>
                            <Trash2 aria-hidden="true" className="size-4" />
                            <span className="sr-only">Hapus {item.Nama}</span>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>

      {dialogTerbuka && (
        <DialogPaket
          katalogFitur={katalogFitur}
          paket={sedangDisunting}
          onTutup={() => setDialogTerbuka(false)}
        />
      )}
    </KerangkaPlatform>
  );
}

function DialogPaket({
  katalogFitur,
  paket,
  onTutup,
}: {
  katalogFitur: DefinisiFitur[];
  paket: PaketItem | null;
  onTutup: () => void;
}) {
  const form = useForm({
    Kode: paket?.Kode ?? '',
    Nama: paket?.Nama ?? '',
    Deskripsi: paket?.Deskripsi ?? '',
    HargaBulanan: String(paket?.HargaBulanan ?? 0),
    HargaTahunan: String(paket?.HargaTahunan ?? 0),
    MataUang: paket?.MataUang ?? 'IDR',
    Aktif: paket?.Aktif ?? true,
    Fitur: katalogFitur.map<BarisFiturForm>((definisi) => {
      const tersimpan = paket?.Fitur.find((f) => f.Kode === definisi.Kode);

      return {
        Kode: definisi.Kode,
        Diizinkan: tersimpan?.Diizinkan ?? definisi.DiizinkanBawaan,
        BatasNilai:
          tersimpan?.BatasNilai === null || tersimpan?.BatasNilai === undefined
            ? ''
            : String(tersimpan.BatasNilai),
      };
    }),
  });

  const ubahFitur = (kode: string, perubahan: Partial<BarisFiturForm>) => {
    form.setData(
      'Fitur',
      form.data.Fitur.map((baris) => (baris.Kode === kode ? { ...baris, ...perubahan } : baris)),
    );
  };

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: onTutup };

    if (paket) {
      form.put(rutePlatform.paketDetail(paket.Id), opsi);
    } else {
      form.post(rutePlatform.paket, opsi);
    }
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="max-h-[85vh] max-w-2xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{paket ? `Ubah ${paket.Nama}` : 'Paket baru'}</DialogTitle>
          <DialogDescription>
            Modul yang tidak dicentang tidak dapat dibuka pelanggan, termasuk lewat API.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={kirim} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="kode">Kode</Label>
              <Input
                id="kode"
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="nama">Nama</Label>
              <Input
                id="nama"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="harga-bulanan">Harga bulanan</Label>
              <InputUang
                id="harga-bulanan"
                value={form.data.HargaBulanan}
                onChange={(nilai) => form.setData('HargaBulanan', nilai)}
                mataUang={form.data.MataUang || 'IDR'}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="harga-tahunan">Harga tahunan</Label>
              <InputUang
                id="harga-tahunan"
                value={form.data.HargaTahunan}
                onChange={(nilai) => form.setData('HargaTahunan', nilai)}
                mataUang={form.data.MataUang || 'IDR'}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="deskripsi">Deskripsi</Label>
            <Textarea
              id="deskripsi"
              rows={2}
              value={form.data.Deskripsi}
              onChange={(e) => form.setData('Deskripsi', e.target.value)}
            />
          </div>

          <label className="flex items-center gap-3 text-sm">
            <Switch checked={form.data.Aktif} onCheckedChange={(nilai) => form.setData('Aktif', nilai)} />
            Paket dapat dipilih untuk langganan baru
          </label>

          <fieldset className="space-y-3">
            <legend className="text-sm font-medium text-foreground">Entitlement</legend>
            {katalogFitur.map((definisi) => {
              const baris = form.data.Fitur.find((f) => f.Kode === definisi.Kode);
              if (!baris) return null;

              return (
                <div key={definisi.Kode} className="rounded-[8px] border border-border p-3">
                  <label className="flex items-start gap-3">
                    <Switch
                      checked={baris.Diizinkan}
                      onCheckedChange={(nilai) => ubahFitur(definisi.Kode, { Diizinkan: nilai })}
                    />
                    <span className="min-w-0 space-y-0.5">
                      <span className="block text-sm font-medium text-foreground">{definisi.Nama}</span>
                      <span className="block text-xs text-muted-foreground">{definisi.Deskripsi}</span>
                    </span>
                  </label>

                  {definisi.TipeBatas === 'Angka' && (
                    <div className="mt-3 space-y-1.5 ps-11">
                      <Label htmlFor={`batas-${definisi.Kode}`}>
                        Batas jumlah ({definisi.SatuanBatas ?? 'entri'})
                      </Label>
                      <Input
                        id={`batas-${definisi.Kode}`}
                        inputMode="numeric"
                        placeholder="Kosongkan untuk tanpa batas"
                        value={baris.BatasNilai}
                        onChange={(e) => ubahFitur(definisi.Kode, { BatasNilai: e.target.value })}
                      />
                      <p className="text-xs text-muted-foreground">
                        Saat ini:{' '}
                        {labelBatas(
                          baris.BatasNilai === '' ? null : Number(baris.BatasNilai),
                          definisi.SatuanBatas,
                        )}
                        . Nilai 0 berarti tidak boleh sama sekali.
                      </p>
                    </div>
                  )}
                </div>
              );
            })}
          </fieldset>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onTutup}>
              Batal
            </Button>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
