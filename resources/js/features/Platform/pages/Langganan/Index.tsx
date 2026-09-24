import { type FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarPlus, FileText, Plus, XCircle } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { tanggal } from '@/features/Langganan/format';
import type { LanggananPlatformItem, PilihanRingkas, StatusLangganan } from '@/features/Langganan/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { rutePlatform } from '@/features/Platform/api';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { DatePicker } from '@/components/ui/date-picker';

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

/* DESIGN.md 18: uji coba berjalan, aktif berhasil, tenggang menunggu bayar, kedaluwarsa gagal, batal netral. */
const VARIAN_STATUS: Record<StatusLangganan, 'proses' | 'sukses' | 'perhatian' | 'bahaya' | 'netral'> = {
  UjiCoba: 'proses',
  Aktif: 'sukses',
  Tenggang: 'perhatian',
  Kedaluwarsa: 'bahaya',
  Dibatalkan: 'netral',
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
      router.post(rutePlatform.langgananBatalkan(item.Id), {}, { preserveScroll: true });
    }
  };

  return (
    <KerangkaPlatform>
      <Head title="Langganan Tenant" />

      <div className="space-y-5">
        <KepalaHalaman
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
              <KeadaanKosong
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
                                rutePlatform.langgananPerpanjang(item.Id),
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
                                rutePlatform.langgananTagihan(item.Id),
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
    form.post(rutePlatform.langganan, { preserveScroll: true, onSuccess: onTutup });
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
            <Combobox
              nilai={form.data.OrganisasiId}
              onPilih={(nilai) => form.setData('OrganisasiId', nilai)}
              opsi={opsiDari(organisasi, (item) => item.Nama)}
              placeholder="Pilih organisasi"
            />
            {form.errors.OrganisasiId && (
              <p className="text-sm text-destructive">{form.errors.OrganisasiId}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label>Paket</Label>
            <Combobox
              nilai={form.data.PaketLanggananId}
              onPilih={(nilai) => form.setData('PaketLanggananId', nilai)}
              opsi={opsiDari(paket, (item) => item.Nama)}
              placeholder="Pilih paket"
            />
            {form.errors.PaketLanggananId && (
              <p className="text-sm text-destructive">{form.errors.PaketLanggananId}</p>
            )}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Siklus</Label>
              <Combobox
                nilai={form.data.Siklus}
                onPilih={(nilai) => form.setData('Siklus', nilai)}
                opsi={siklus.map((item) => ({ nilai: item.Nilai, label: item.Label }))}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="mulai-pada">Mulai pada</Label>
              <DatePicker
                value={form.data.MulaiPada}
                onChange={(nilai) => form.setData('MulaiPada', nilai)}
                id="mulai-pada"
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
