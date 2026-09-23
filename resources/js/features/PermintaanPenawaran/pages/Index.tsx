import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileText, Plus, Search } from 'lucide-react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { Paginasi } from '@/types/global';
import type { PermintaanPenawaran, StatusPermintaanPenawaran } from '@/features/PermintaanPenawaran/types';
import { formatUang } from '@/lib/uang';
import { rutePermintaanPenawaran } from '@/features/PermintaanPenawaran/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { dariMasukanWaktu } from '@/lib/waktu';

interface PermintaanRingkas {
  Id: string;
  Nomor: string;
  TotalEstimasi: string;
}
interface PenyediaRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface Props {
  rfq: Paginasi<PermintaanPenawaran>;
  permintaanDisetujui: PermintaanRingkas[];
  penyedia: PenyediaRingkas[];
  filter: { cari?: string; status?: StatusPermintaanPenawaran };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusPermintaanPenawaran[] = ['Draft', 'Dibuka', 'Ditutup'];
const VARIAN_STATUS = { Draft: 'netral', Dibuka: 'proses', Ditutup: 'sukses' } as const;

function DialogBuatRfq({
  permintaanDisetujui,
  penyedia,
  wajib,
}: Pick<Props, 'permintaanDisetujui' | 'penyedia'> & { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    PermintaanPembelianId: '',
    BatasPenawaran: '',
    Catatan: '',
    PenyediaIds: [] as string[],
  });

  function pilihPenyedia(id: string, dipilih: boolean): void {
    form.setData(
      'PenyediaIds',
      dipilih ? [...form.data.PenyediaIds, id] : form.data.PenyediaIds.filter((item) => item !== id),
    );
  }

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      BatasPenawaran: dariMasukanWaktu(data.BatasPenawaran),
      Catatan: data.Catatan || null,
    }));
    form.post(rutePermintaanPenawaran.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat RFQ
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Permintaan Penawaran</DialogTitle>
          <DialogDescription>
            Pilih permintaan pembelian yang telah disetujui, tetapkan batas waktu, lalu undang penyedia.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="PermintaanPembelianId">Permintaan Pembelian</Label>
              <Combobox
                nilai={form.data.PermintaanPembelianId}
                onPilih={(value) => form.setData('PermintaanPembelianId', value)}
                opsi={opsiDari(
                  permintaanDisetujui,
                  (item) => `${item.Nomor} — ${formatUang(item.TotalEstimasi)}`,
                )}
                placeholder="Pilih permintaan disetujui"
              />
              {form.errors.PermintaanPembelianId && (
                <p className="text-sm text-destructive">{form.errors.PermintaanPembelianId}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="BatasPenawaran" htmlFor="BatasPenawaran">
                Batas Penawaran
              </Label>
              <Input
                id="BatasPenawaran"
                type="datetime-local"
                value={form.data.BatasPenawaran}
                onChange={(event) => form.setData('BatasPenawaran', event.target.value)}
              />
              {form.errors.BatasPenawaran && (
                <p className="text-sm text-destructive">{form.errors.BatasPenawaran}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Penyedia Diundang</Label>
              {penyedia.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum ada penyedia aktif yang dapat diundang.</p>
              ) : (
                <div className="max-h-56 space-y-1 overflow-y-auto rounded-[9px] border border-border p-2">
                  {penyedia.map((item) => (
                    <label
                      key={item.Id}
                      className="flex min-h-11 items-center gap-3 rounded-[5px] p-2 hover:bg-muted"
                    >
                      <Checkbox
                        checked={form.data.PenyediaIds.includes(item.Id)}
                        onCheckedChange={(nilai) => pilihPenyedia(item.Id, nilai === true)}
                      />
                      <span className="text-sm">
                        {item.Kode} — {item.Nama}
                      </span>
                    </label>
                  ))}
                </div>
              )}
              {form.errors.PenyediaIds && (
                <p className="text-sm text-destructive">{form.errors.PenyediaIds}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="Catatan" htmlFor="Catatan">
                Catatan
              </Label>
              <Input
                id="Catatan"
                value={form.data.Catatan}
                onChange={(event) => form.setData('Catatan', event.target.value)}
              />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Draft
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function PermintaanPenawaranIndex({
  rfq,
  permintaanDisetujui,
  penyedia,
  filter,
  wajib,
}: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      rutePermintaanPenawaran.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Permintaan Penawaran" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Permintaan Penawaran"
          deskripsi="Undang penyedia, catat penawaran masuk, dan pilih hasil evaluasi."
          aksi={
            <>
              <TombolEkspor url={rutePermintaanPenawaran.ekspor} filter={filter as Record<string, string>} />
              <DialogBuatRfq
                permintaanDisetujui={permintaanDisetujui}
                penyedia={penyedia}
                wajib={wajib.permintaan}
              />
            </>
          }
        />

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_12rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor RFQ"
              placeholder="Cari nomor RFQ"
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

        {rfq.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/penyedia-kontrak.webp"
            judul="Belum ada permintaan penawaran."
            deskripsi="Buat RFQ dari permintaan pembelian yang sudah disetujui."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Nomor</th>
                    <th className="px-4 py-3">Sumber</th>
                    <th className="px-4 py-3">Penyedia / Penawaran</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {rfq.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          className="font-mono font-medium hover:text-primary"
                          href={rutePermintaanPenawaran.detail(item.Id)}
                        >
                          {item.Nomor}
                        </Link>
                      </td>
                      <td className="px-4 py-3 font-mono text-xs">
                        {item.PermintaanPembelian?.Nomor ?? 'Tanpa permintaan'}
                      </td>
                      <td className="px-4 py-3">
                        {item.JumlahPenyediaDiundang ?? 0} diundang · {item.JumlahPenawaran ?? 0} penawaran
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {rfq.data.map((item) => (
                <Link
                  key={item.Id}
                  href={rutePermintaanPenawaran.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <FileText className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.Nomor}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.PermintaanPembelian?.Nomor ?? 'Tanpa permintaan'} ·{' '}
                      {item.JumlahPenyediaDiundang ?? 0} penyedia
                    </p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={rfq.meta}
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
