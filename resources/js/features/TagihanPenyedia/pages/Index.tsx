import { type FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ReceiptText, Search } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Paginasi } from '@/types/global';
import type { StatusTagihanPenyedia, TagihanPenyedia } from '@/features/TagihanPenyedia/types';
import { formatUang } from '@/lib/uang';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface Props {
  tagihan: Paginasi<TagihanPenyedia>;
  filter: { cari?: string; status?: StatusTagihanPenyedia };
}

const SEMUA = '__semua__';
const STATUS: StatusTagihanPenyedia[] = ['BelumDibayar', 'DibayarSebagian', 'Dibayar'];
const VARIAN_STATUS = { BelumDibayar: 'perhatian', DibayarSebagian: 'proses', Dibayar: 'sukses' } as const;

export default function TagihanPenyediaIndex({ tagihan, filter }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteTagihanPenyedia.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Tagihan Penyedia" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Tagihan Penyedia"
          deskripsi="Tagihan hasil matching PO dan penerimaan, beserta sisa yang belum dibayar."
          aksi={<TombolEkspor url={ruteTagihanPenyedia.ekspor} filter={filter as Record<string, string>} />}
        />

        <form onSubmit={terapkanFilter} className="flex flex-wrap items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
            />
            <Input
              aria-label="Cari nomor tagihan"
              placeholder="Cari nomor tagihan"
              className="pl-8"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full sm:w-44">
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
          <Button type="submit" variant="secondary">
            Terapkan
          </Button>
        </form>

        {tagihan.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/berkas-dokumen.webp"
            judul="Belum ada tagihan penyedia."
            deskripsi="Catat tagihan dari halaman pesanan pembelian yang sudah menerima barang."
          />
        ) : (
          <div className="overflow-hidden rounded-md border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-permukaan-50 text-left text-[12.5px] text-grafit-500">
                  <tr>
                    <th className="h-10 px-4 font-medium">Nomor</th>
                    <th className="h-10 px-4 font-medium">Penyedia / PO</th>
                    <th className="h-10 px-4 font-medium text-right">Total</th>
                    <th className="h-10 px-4 font-medium text-right">Sisa</th>
                    <th className="h-10 px-4 font-medium">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {tagihan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-permukaan-50">
                      <td className="px-4 py-3">
                        <Link
                          className="font-mono font-medium hover:text-primary"
                          href={ruteTagihanPenyedia.detail(item.Id)}
                        >
                          {item.NomorTagihan}
                        </Link>
                        <p className="text-xs text-muted-foreground">Jatuh tempo {item.JatuhTempo ?? '-'}</p>
                      </td>
                      <td className="px-4 py-3">
                        {item.NamaPenyedia}
                        <p className="font-mono text-xs text-muted-foreground">
                          {item.NomorPesananPembelian ?? '-'}
                        </p>
                      </td>
                      <td className="px-4 py-3 text-right font-mono">{formatUang(item.Total)}</td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(item.Sisa)}
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
              {tagihan.data.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteTagihanPenyedia.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4 transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                >
                  <ReceiptText className="size-4 shrink-0 text-grafit-500" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.NomorTagihan}</p>
                    <p className="truncate text-xs text-muted-foreground">{item.NamaPenyedia}</p>
                    <p className="mt-1 font-mono text-sm font-semibold">Sisa {formatUang(item.Sisa)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={tagihan.meta}
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
