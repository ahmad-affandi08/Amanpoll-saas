import { type FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Plus, Search } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Paginasi } from '@/types/global';
import type { SertifikasiAset, StatusSertifikasi } from '@/features/Sertifikasi/types';
import { ruteSertifikasi } from '@/features/Sertifikasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
}
interface Props {
  sertifikasi: Paginasi<SertifikasiAset>;
  aset: AsetRingkas[];
  filter: { cari?: string; status?: StatusSertifikasi };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusSertifikasi[] = ['Aktif', 'Kedaluwarsa', 'Dicabut'];
const VARIAN_STATUS = { Aktif: 'sukses', Kedaluwarsa: 'perhatian', Dicabut: 'bahaya' } as const;

function DialogTerbitkan({ aset, wajib }: { aset: AsetRingkas[]; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    AsetId: '',
    JenisSertifikasi: '',
    NomorSertifikat: '',
    Penerbit: '',
    TerbitPada: '',
    BerlakuSampai: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      NomorSertifikat: data.NomorSertifikat || null,
      Penerbit: data.Penerbit || null,
      TerbitPada: data.TerbitPada || null,
      BerlakuSampai: data.BerlakuSampai || null,
    }));
    form.post(ruteSertifikasi.simpan, {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Catat Sertifikat
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Sertifikat Aset</DialogTitle>
          <DialogDescription>
            Sertifikat dengan masa berlaku akan diingatkan menjelang kedaluwarsa dan ditutup otomatis setelah
            lewat.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId">Aset</Label>
              <Select value={form.data.AsetId} onValueChange={(value) => form.setData('AsetId', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Pilih aset" />
                </SelectTrigger>
                <SelectContent>
                  {aset.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.KodeAset} — {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.AsetId && <p className="text-sm text-destructive">{form.errors.AsetId}</p>}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="JenisSertifikasi" htmlFor="JenisSertifikasi">
                  Jenis sertifikat
                </Label>
                <Input
                  id="JenisSertifikasi"
                  value={form.data.JenisSertifikasi}
                  onChange={(event) => form.setData('JenisSertifikasi', event.target.value)}
                />
                {form.errors.JenisSertifikasi && (
                  <p className="text-sm text-destructive">{form.errors.JenisSertifikasi}</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label nama="NomorSertifikat" htmlFor="NomorSertifikat">
                  Nomor sertifikat
                </Label>
                <Input
                  id="NomorSertifikat"
                  value={form.data.NomorSertifikat}
                  onChange={(event) => form.setData('NomorSertifikat', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Penerbit" htmlFor="PenerbitSertifikat">
                Penerbit
              </Label>
              <Input
                id="PenerbitSertifikat"
                value={form.data.Penerbit}
                onChange={(event) => form.setData('Penerbit', event.target.value)}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TerbitPada" htmlFor="TerbitPada">
                  Terbit pada
                </Label>
                <Input
                  id="TerbitPada"
                  type="date"
                  value={form.data.TerbitPada}
                  onChange={(event) => form.setData('TerbitPada', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="BerlakuSampai" htmlFor="BerlakuSampaiSertifikat">
                  Berlaku sampai
                </Label>
                <Input
                  id="BerlakuSampaiSertifikat"
                  type="date"
                  value={form.data.BerlakuSampai}
                  onChange={(event) => form.setData('BerlakuSampai', event.target.value)}
                />
                {form.errors.BerlakuSampai && (
                  <p className="text-sm text-destructive">{form.errors.BerlakuSampai}</p>
                )}
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Sertifikat
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogCabut({ sertifikat, wajib }: { sertifikat: SertifikasiAset; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteSertifikasi.cabut(sertifikat.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          Cabut
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            Cabut sertifikat {sertifikat.NomorSertifikat ?? sertifikat.JenisSertifikasi}?
          </DialogTitle>
          <DialogDescription>
            Sertifikat yang dicabut tidak dapat diubah lagi dan berhenti diingatkan.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Alasan" htmlFor="AlasanCabut">
                Alasan pencabutan
              </Label>
              <Textarea
                id="AlasanCabut"
                rows={3}
                value={form.data.Alasan}
                onChange={(event) => form.setData('Alasan', event.target.value)}
              />
              {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" variant="destructive" disabled={form.processing}>
                Cabut Sertifikat
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function SertifikasiIndex({ sertifikasi, aset, filter, wajib }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteSertifikasi.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Sertifikasi Aset" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Sertifikasi Aset"
          deskripsi="Sertifikat aset beserta penerbit, masa berlaku, dan statusnya."
          aksi={
            <>
              <DialogTerbitkan aset={aset} wajib={wajib.terbitkan} />
            </>
          }
        />

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_13rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor atau jenis sertifikat"
              placeholder="Cari nomor atau jenis sertifikat"
              className="pl-9"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua status</SelectItem>
              {STATUS.map((item) => (
                <SelectItem key={item} value={item}>
                  {item}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {sertifikasi.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/persetujuan-kepatuhan.webp"
            judul="Belum ada sertifikat aset."
            deskripsi="Catat sertifikat agar masa berlakunya ikut diingatkan."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Sertifikat</th>
                    <th className="px-4 py-3">Aset</th>
                    <th className="px-4 py-3">Penerbit</th>
                    <th className="px-4 py-3">Masa berlaku</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {sertifikasi.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        {item.JenisSertifikasi}
                        <p className="font-mono text-xs text-muted-foreground">
                          {item.NomorSertifikat ?? 'Tanpa nomor'}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        {item.NamaAset}
                        <p className="font-mono text-xs text-muted-foreground">{item.KodeAset}</p>
                      </td>
                      <td className="px-4 py-3">{item.Penerbit ?? '—'}</td>
                      <td className="px-4 py-3 text-xs">
                        {item.BerlakuSampai ?? 'Tanpa batas'}
                        {item.SisaHari !== null && item.SisaHari >= 0 && item.Status === 'Aktif' && (
                          <p className="text-muted-foreground">{item.SisaHari} hari lagi</p>
                        )}
                        {item.SisaHari !== null && item.SisaHari < 0 && (
                          <p className="text-bahaya-600">Lewat {Math.abs(item.SisaHari)} hari</p>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                      <td className="px-4 py-3 text-right">
                        {item.Status !== 'Dicabut' && <DialogCabut sertifikat={item} wajib={wajib.cabut} />}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {sertifikasi.data.map((item) => (
                <div key={item.Id} className="space-y-2 p-4">
                  <div className="flex items-start gap-3">
                    <BadgeCheck className="size-5 shrink-0 text-primary" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-medium">{item.JenisSertifikasi}</p>
                      <p className="truncate font-mono text-xs text-muted-foreground">
                        {item.KodeAset} · {item.NomorSertifikat ?? 'Tanpa nomor'}
                      </p>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Berlaku sampai {item.BerlakuSampai ?? 'tanpa batas'}
                      </p>
                    </div>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </div>
                  {item.Status !== 'Dicabut' && <DialogCabut sertifikat={item} wajib={wajib.cabut} />}
                </div>
              ))}
            </div>
            <KontrolPaginasi
              meta={sertifikasi.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
