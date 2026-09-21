import { type FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarPlus, FileText, Plus, XCircle } from 'lucide-react';
import { EmptyState } from '@/components/shared/EmptyState';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { tanggal } from '@/features/Langganan/format';
import type { LanggananPlatformItem, PilihanRingkas, StatusLangganan } from '@/features/Langganan/types';
import { PageHeader } from '@/components/shared/PageHeader';

interface PilihanSiklus {
  Nilai: string;
  Label: string;
}

interface Props {
  langganan: LanggananPlatformItem[];
  organisasi: PilihanRingkas[];
  paket: PilihanRingkas[];
  siklus: PilihanSiklus[];
}

const VARIAN_STATUS: Record<StatusLangganan, 'default' | 'secondary' | 'destructive'> = {
  UjiCoba: 'secondary',
  Aktif: 'default',
  Tenggang: 'secondary',
  Kedaluwarsa: 'destructive',
  Dibatalkan: 'destructive',
};

export default function PlatformLanggananIndex({ langganan, organisasi, paket, siklus }: Props) {
  const [dialogTerbuka, setDialogTerbuka] = useState(false);
  const konfirmasi = useKonfirmasi();

  const batalkan = async (item: LanggananPlatformItem) => {
    const setuju = await konfirmasi({
      judul: `Batalkan langganan ${item.NamaOrganisasi ?? item.OrganisasiId}?`,
      deskripsi:
        'Pembatalan berlaku di akhir periode berjalan; akses tulis baru berhenti setelah tanggal itu terlewat.',
    });

    if (setuju) {
      router.post(`/admin-platform/langganan/${item.Id}/batalkan`, {}, { preserveScroll: true });
    }
  };

  return (
    <KerangkaPlatform>
      <Head title="Langganan Tenant" />

      <div className="space-y-6">
        <PageHeader
          tanpaBreadcrumb
          judul="Langganan Tenant"
          deskripsi="Status efektif dihitung dari tanggal, jadi kolom ini selalu mencerminkan hak akses hari ini."
          aksi={
            <>
              <Button onClick={() => setDialogTerbuka(true)}>
                <Plus aria-hidden="true" className="size-4" />
                Mulai / ganti paket
              </Button>
            </>
          }
        />

        <Card>
          <CardHeader>
            <CardTitle>Daftar langganan</CardTitle>
          </CardHeader>
          <CardContent>
            {langganan.length === 0 ? (
              <EmptyState
                judul="Belum ada organisasi yang berlangganan."
                deskripsi="Mulai langganan untuk organisasi pertama."
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Organisasi</TableHead>
                    <TableHead>Paket</TableHead>
                    <TableHead>Siklus</TableHead>
                    <TableHead>Berlaku sampai</TableHead>
                    <TableHead>Status efektif</TableHead>
                    <TableHead className="w-0" />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {langganan.map((item) => (
                    <TableRow key={item.Id}>
                      <TableCell>
                        <div className="space-y-0.5">
                          <p className="font-medium text-foreground">{item.NamaOrganisasi ?? '—'}</p>
                          <p className="text-xs text-muted-foreground">
                            {item.KodeOrganisasi ?? item.OrganisasiId}
                          </p>
                        </div>
                      </TableCell>
                      <TableCell>{item.NamaPaket ?? '—'}</TableCell>
                      <TableCell className="text-muted-foreground">{item.Siklus}</TableCell>
                      <TableCell className="text-muted-foreground">{tanggal(item.BerakhirPada)}</TableCell>
                      <TableCell>
                        <div className="space-y-1">
                          <Badge variant={VARIAN_STATUS[item.StatusEfektif]}>{item.LabelStatusEfektif}</Badge>
                          {item.StatusTersimpan !== item.StatusEfektif && (
                            <p className="text-xs text-muted-foreground">
                              Kolom tersimpan: {item.StatusTersimpan}
                            </p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex justify-end gap-1">
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() =>
                              router.post(
                                `/admin-platform/langganan/${item.Id}/perpanjang`,
                                {},
                                { preserveScroll: true },
                              )
                            }
                          >
                            <CalendarPlus aria-hidden="true" className="size-4" />
                            <span className="sr-only">Perpanjang</span>
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() =>
                              router.post(
                                `/admin-platform/langganan/${item.Id}/tagihan`,
                                {},
                                { preserveScroll: true },
                              )
                            }
                          >
                            <FileText aria-hidden="true" className="size-4" />
                            <span className="sr-only">Terbitkan tagihan</span>
                          </Button>
                          <Button size="sm" variant="ghost" onClick={() => batalkan(item)}>
                            <XCircle aria-hidden="true" className="size-4" />
                            <span className="sr-only">Batalkan</span>
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
        <DialogLangganan
          organisasi={organisasi}
          paket={paket}
          siklus={siklus}
          onTutup={() => setDialogTerbuka(false)}
        />
      )}
    </KerangkaPlatform>
  );
}

function DialogLangganan({
  organisasi,
  paket,
  siklus,
  onTutup,
}: {
  organisasi: PilihanRingkas[];
  paket: PilihanRingkas[];
  siklus: PilihanSiklus[];
  onTutup: () => void;
}) {
  const form = useForm({
    OrganisasiId: '',
    PaketLanggananId: '',
    Siklus: siklus[0]?.Nilai ?? 'Bulanan',
    MulaiPada: '',
    DenganUjiCoba: false,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post('/admin-platform/langganan', { preserveScroll: true, onSuccess: onTutup });
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Mulai atau ganti paket</DialogTitle>
          <DialogDescription>
            Organisasi yang sudah berlangganan akan berganti paket pada baris yang sama; data tenant tidak
            tersentuh.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={kirim} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Organisasi</Label>
            <Select
              value={form.data.OrganisasiId}
              onValueChange={(nilai) => form.setData('OrganisasiId', nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Pilih organisasi" />
              </SelectTrigger>
              <SelectContent>
                {organisasi.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.OrganisasiId && (
              <p className="text-sm text-destructive">{form.errors.OrganisasiId}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label>Paket</Label>
            <Select
              value={form.data.PaketLanggananId}
              onValueChange={(nilai) => form.setData('PaketLanggananId', nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Pilih paket" />
              </SelectTrigger>
              <SelectContent>
                {paket.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.PaketLanggananId && (
              <p className="text-sm text-destructive">{form.errors.PaketLanggananId}</p>
            )}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Siklus</Label>
              <Select value={form.data.Siklus} onValueChange={(nilai) => form.setData('Siklus', nilai)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {siklus.map((item) => (
                    <SelectItem key={item.Nilai} value={item.Nilai}>
                      {item.Label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="mulai-pada">Mulai pada</Label>
              <Input
                id="mulai-pada"
                type="date"
                value={form.data.MulaiPada}
                onChange={(e) => form.setData('MulaiPada', e.target.value)}
              />
            </div>
          </div>

          <label className="flex items-center gap-3 text-sm">
            <Switch
              checked={form.data.DenganUjiCoba}
              onCheckedChange={(nilai) => form.setData('DenganUjiCoba', nilai)}
            />
            Awali dengan masa uji coba
          </label>

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
