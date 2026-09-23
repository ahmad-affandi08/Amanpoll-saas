import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { PackageCheck, Search } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
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
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Paginasi } from '@/types/global';
import type { PenawaranPenyedia } from '@/features/PermintaanPenawaran/types';
import type { PesananPembelian, StatusPesananPembelian } from '@/features/PesananPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { DatePicker } from '@/components/ui/date-picker';

interface Props {
  pesanan: Paginasi<PesananPembelian>;
  penawaranTerpilih: PenawaranPenyedia[];
  filter: { cari?: string; status?: StatusPesananPembelian };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusPesananPembelian[] = [
  'Draft',
  'MenungguPersetujuan',
  'Disetujui',
  'Ditolak',
  'Dikirim',
  'DiterimaSebagian',
  'DiterimaPenuh',
];
const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'info',
  Ditolak: 'bahaya',
  Dikirim: 'proses',
  DiterimaSebagian: 'perhatian',
  DiterimaPenuh: 'sukses',
} as const;

function DialogBuatPesanan({ penawaran, wajib }: { penawaran: PenawaranPenyedia; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    TanggalPesanan: new Date().toISOString().slice(0, 10),
    TanggalKirimRencana: '',
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      TanggalKirimRencana: data.TanggalKirimRencana || null,
      Catatan: data.Catatan || null,
    }));
    form.post(rutePesananPembelian.buatDariPenawaran(penawaran.Id), { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          Buat PO
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Pesanan Pembelian</DialogTitle>
          <DialogDescription>
            Item dan nilai disalin dari penawaran {penawaran.NamaPenyedia} sebesar{' '}
            {formatUang(penawaran.Total)}.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TanggalPesanan" htmlFor="TanggalPesanan">
                  Tanggal Pesanan
                </Label>
                <DatePicker
                  value={form.data.TanggalPesanan}
                  onChange={(nilai) => form.setData('TanggalPesanan', nilai)}
                  id="TanggalPesanan"
                />
                {form.errors.TanggalPesanan && (
                  <p className="text-sm text-destructive">{form.errors.TanggalPesanan}</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label nama="TanggalKirimRencana" htmlFor="TanggalKirimRencana">
                  Rencana Kirim
                </Label>
                <DatePicker
                  value={form.data.TanggalKirimRencana}
                  onChange={(nilai) => form.setData('TanggalKirimRencana', nilai)}
                  id="TanggalKirimRencana"
                />
                {form.errors.TanggalKirimRencana && (
                  <p className="text-sm text-destructive">{form.errors.TanggalKirimRencana}</p>
                )}
              </div>
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
                Buat Pesanan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function PesananPembelianIndex({ pesanan, penawaranTerpilih, filter, wajib }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      rutePesananPembelian.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Pesanan Pembelian" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Pesanan Pembelian"
          deskripsi="PO dibuat dari penawaran terpilih; komitmen anggaran dicatat saat PO dikirim."
          aksi={<TombolEkspor url="/perencanaan-pengadaan/pesanan-pembelian/ekspor" filter={filter as Record<string, string>} />}
        />

        {penawaranTerpilih.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Penawaran Terpilih Menunggu PO</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {penawaranTerpilih.map((item) => (
                <div
                  key={item.Id}
                  className="flex flex-col gap-3 rounded-[9px] border border-border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{item.NamaPenyedia}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.NomorPenawaran ?? 'Tanpa nomor'} · {formatUang(item.Total)}
                    </p>
                  </div>
                  <DialogBuatPesanan penawaran={item} wajib={wajib.pesanan} />
                </div>
              ))}
            </CardContent>
          </Card>
        )}

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_14rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor PO"
              placeholder="Cari nomor PO"
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

        {pesanan.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/persediaan.webp"
            judul="Belum ada pesanan pembelian."
            deskripsi="Pilih penawaran pada RFQ untuk menerbitkan PO."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Nomor</th>
                    <th className="px-4 py-3">Penyedia</th>
                    <th className="px-4 py-3">Penerimaan</th>
                    <th className="px-4 py-3 text-right">Total</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {pesanan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          className="font-mono font-medium hover:text-primary"
                          href={rutePesananPembelian.detail(item.Id)}
                        >
                          {item.Nomor}
                        </Link>
                        <p className="text-xs text-muted-foreground">{item.TanggalPesanan ?? '-'}</p>
                      </td>
                      <td className="px-4 py-3">{item.NamaPenyedia}</td>
                      <td className="px-4 py-3">{item.JumlahPenerimaan ?? 0} dokumen</td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(item.Total)}
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
              {pesanan.data.map((item) => (
                <Link
                  key={item.Id}
                  href={rutePesananPembelian.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <PackageCheck className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.Nomor}</p>
                    <p className="truncate text-xs text-muted-foreground">{item.NamaPenyedia}</p>
                    <p className="mt-1 font-mono text-sm font-semibold">{formatUang(item.Total)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={pesanan.meta}
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
