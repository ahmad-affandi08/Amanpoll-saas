import { type FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ReceiptText, Search } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Paginasi } from '@/types/global';
import type { StatusTagihanPenyedia, TagihanPenyedia } from '@/features/TagihanPenyedia/types';
import { formatUang } from '@/lib/uang';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { PageHeader } from '@/components/shared/PageHeader';

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
    <AppLayout>
      <Head title="Tagihan Penyedia" />
      <div className="space-y-6">
        <PageHeader
          judul="Tagihan Penyedia"
          deskripsi="Tagihan hasil matching PO dan penerimaan, beserta sisa yang belum dibayar."
        />

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_12rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor tagihan"
              placeholder="Cari nomor tagihan"
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

        {tagihan.data.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/berkas-dokumen.webp"
            judul="Belum ada tagihan penyedia."
            deskripsi="Catat tagihan dari halaman pesanan pembelian yang sudah menerima barang."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Nomor</th>
                    <th className="px-4 py-3">Penyedia / PO</th>
                    <th className="px-4 py-3 text-right">Total</th>
                    <th className="px-4 py-3 text-right">Sisa</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {tagihan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
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
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <ReceiptText className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.NomorTagihan}</p>
                    <p className="truncate text-xs text-muted-foreground">{item.NamaPenyedia}</p>
                    <p className="mt-1 font-mono text-sm font-semibold">Sisa {formatUang(item.Sisa)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <Pagination
              meta={tagihan.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
