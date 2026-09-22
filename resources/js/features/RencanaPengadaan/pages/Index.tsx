import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ListChecks, Plus, Search } from 'lucide-react';
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
import type { RencanaPengadaan, StatusRencanaPengadaan } from '@/features/RencanaPengadaan/types';
import { formatUang } from '@/lib/uang';
import { ruteRencanaPengadaan } from '@/features/RencanaPengadaan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface PosRingkas {
  Id: string;
  Label: string;
}
interface UsulanRingkas {
  Id: string;
  Nomor: string;
  NamaKebutuhan: string;
  Jumlah: string;
  EstimasiHargaSatuan: string | null;
}
interface Props {
  rencana: Paginasi<RencanaPengadaan>;
  posAnggaran: PosRingkas[];
  usulanDisetujui: UsulanRingkas[];
  filter: { cari?: string; tahun?: number; status?: StatusRencanaPengadaan };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusRencanaPengadaan[] = ['Draft', 'Direncanakan', 'Dibatalkan'];
const VARIAN_STATUS = { Draft: 'netral', Direncanakan: 'sukses', Dibatalkan: 'bahaya' } as const;

function DialogBuatRencana({
  posAnggaran,
  usulanDisetujui,
  wajib,
}: Pick<Props, 'posAnggaran' | 'usulanDisetujui'> & { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Nama: '',
    Tahun: new Date().getFullYear().toString(),
    PosAnggaranId: TANPA_PILIHAN,
    UsulanAsetIds: [] as string[],
  });
  function pilihUsulan(id: string, dipilih: boolean): void {
    form.setData(
      'UsulanAsetIds',
      dipilih ? [...form.data.UsulanAsetIds, id] : form.data.UsulanAsetIds.filter((item) => item !== id),
    );
  }
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      PosAnggaranId: data.PosAnggaranId === TANPA_PILIHAN ? null : data.PosAnggaranId,
    }));
    form.post(ruteRencanaPengadaan.index, { onSuccess: () => setBuka(false) });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Rencana
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Buat Rencana Pengadaan</DialogTitle>
          <DialogDescription>
            Usulan yang dipilih langsung menjadi detail. Estimasi total dihitung di server.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-[1fr_9rem]">
              <div className="space-y-1.5">
                <Label nama="Nama">Nama Rencana</Label>
                <Input
                  value={form.data.Nama}
                  onChange={(event) => form.setData('Nama', event.target.value)}
                />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="Tahun">Tahun</Label>
                <Input
                  type="number"
                  min="2000"
                  max="2100"
                  value={form.data.Tahun}
                  onChange={(event) => form.setData('Tahun', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="PosAnggaranId">Pos Anggaran</Label>
              <Combobox
                nilai={form.data.PosAnggaranId}
                onPilih={(value) => form.setData('PosAnggaranId', value)}
                opsi={[opsiKosong('Pilih nanti'), ...opsiDari(posAnggaran, (item) => item.Label)]}
              />
            </div>
            <div className="space-y-2">
              <Label>Usulan Disetujui</Label>
              {usulanDisetujui.length === 0 ? (
                <p className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                  Belum ada usulan disetujui yang tersedia.
                </p>
              ) : (
                <div className="max-h-60 space-y-2 overflow-y-auto rounded-md border border-border p-3">
                  {usulanDisetujui.map((item) => (
                    <label
                      key={item.Id}
                      className="flex min-h-11 cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-muted/40"
                    >
                      <Checkbox
                        checked={form.data.UsulanAsetIds.includes(item.Id)}
                        onCheckedChange={(value) => pilihUsulan(item.Id, value === true)}
                      />
                      <span className="flex-1 text-sm">
                        <span className="block font-medium">{item.NamaKebutuhan}</span>
                        <span className="font-mono text-xs text-muted-foreground">
                          {item.Nomor} ·{' '}
                          {item.EstimasiHargaSatuan
                            ? formatUang(Number(item.Jumlah) * Number(item.EstimasiHargaSatuan))
                            : 'tanpa estimasi'}
                        </span>
                      </span>
                    </label>
                  ))}
                </div>
              )}
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

export default function RencanaPengadaanIndex({
  rencana,
  posAnggaran,
  usulanDisetujui,
  filter,
  wajib,
}: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [tahun, setTahun] = useState(filter.tahun?.toString() ?? '');
  const [status, setStatus] = useState(filter.status ?? SEMUA);
  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteRencanaPengadaan.index,
      { cari: cari || undefined, tahun: tahun || undefined, status: status === SEMUA ? undefined : status },
      { preserveState: true },
    );
  }
  return (
    <KerangkaAplikasi>
      <Head title="Rencana Pengadaan" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Rencana Pengadaan"
          deskripsi="Konsolidasikan usulan disetujui ke rencana dan pos anggaran."
          aksi={
            <>
              <DialogBuatRencana
                posAnggaran={posAnggaran}
                usulanDisetujui={usulanDisetujui}
                wajib={wajib.rencana}
              />
            </>
          }
        />
        <form
          onSubmit={terapkanFilter}
          className="grid gap-2 rounded-[9px] border border-border bg-card p-3 sm:grid-cols-[1fr_8rem_12rem_auto]"
        >
          <div className="relative">
            <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
            <Input
              className="pl-9"
              aria-label="Cari rencana"
              placeholder="Cari nomor atau nama..."
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Input
            aria-label="Tahun"
            type="number"
            placeholder="Tahun"
            value={tahun}
            onChange={(event) => setTahun(event.target.value)}
          />
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
        {rencana.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/dashboard-analitik.webp"
            judul="Belum ada rencana pengadaan."
            deskripsi="Buat rencana dari usulan yang telah disetujui."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Rencana</th>
                    <th className="px-4 py-3">Tahun / Pos</th>
                    <th className="px-4 py-3 text-right">Total Estimasi</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {rencana.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          className="font-medium hover:text-primary"
                          href={ruteRencanaPengadaan.detail(item.Id)}
                        >
                          {item.Nama}
                        </Link>
                        <p className="font-mono text-xs text-muted-foreground">{item.Nomor}</p>
                      </td>
                      <td className="px-4 py-3">
                        {item.Tahun}
                        <p className="text-xs text-muted-foreground">
                          {item.NamaPosAnggaran ?? 'Pos belum dipilih'}
                        </p>
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(item.TotalEstimasi)}
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
              {rencana.data.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteRencanaPengadaan.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <ListChecks className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.Nama}</p>
                    <p className="font-mono text-xs text-muted-foreground">
                      {item.Nomor} · {item.Tahun}
                    </p>
                    <p className="mt-1 font-mono text-sm font-semibold">{formatUang(item.TotalEstimasi)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={rencana.meta}
              onNavigasi={(page) =>
                navigasiHalaman(page, { cari, tahun, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
