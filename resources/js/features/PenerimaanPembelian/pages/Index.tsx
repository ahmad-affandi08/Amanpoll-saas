import { type FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PackageCheck, Search } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginasi } from '@/types/global';
import type { PenerimaanPembelian } from '@/features/PenerimaanPembelian/types';
import { rutePenerimaanPembelian } from '@/features/PenerimaanPembelian/api';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  penerimaan: Paginasi<PenerimaanPembelian>;
  filter: { cari?: string };
}

function tanggalLokal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleDateString('id-ID') : '-';
}

export default function PenerimaanPembelianIndex({ penerimaan, filter }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(rutePenerimaanPembelian.index, { cari }, { preserveState: true, replace: true });
  }

  return (
    <AppLayout>
      <Head title="Penerimaan Pembelian" />
      <div className="space-y-6">
        <PageHeader
          judul="Penerimaan Pembelian"
          deskripsi="Riwayat penerimaan barang; stok dan registrasi aset dibuat otomatis saat dokumen dicatat."
        />

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor penerimaan atau surat jalan"
              placeholder="Cari nomor penerimaan atau surat jalan"
              className="pl-9"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {penerimaan.data.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/gudang.webp"
            judul="Belum ada penerimaan."
            deskripsi="Catat penerimaan dari halaman pesanan pembelian yang sudah dikirim."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Nomor</th>
                    <th className="px-4 py-3">Pesanan / Penyedia</th>
                    <th className="px-4 py-3">Gudang</th>
                    <th className="px-4 py-3">Tanggal</th>
                    <th className="px-4 py-3">Item</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {penerimaan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <span className="font-mono font-medium">{item.Nomor}</span>
                        <p className="text-xs text-muted-foreground">
                          {item.NomorSuratJalan ?? 'Tanpa surat jalan'}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        <Link
                          className="font-mono text-xs hover:text-primary"
                          href={rutePesananPembelian.detail(item.PesananPembelianId)}
                        >
                          {item.NomorPesananPembelian ?? '-'}
                        </Link>
                        <p className="text-xs text-muted-foreground">{item.NamaPenyedia ?? '-'}</p>
                      </td>
                      <td className="px-4 py-3">{item.NamaGudang ?? 'Tanpa gudang'}</td>
                      <td className="px-4 py-3">{tanggalLokal(item.TanggalTerima)}</td>
                      <td className="px-4 py-3">
                        <Badge variant="netral">{item.JumlahItem ?? 0} baris</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {penerimaan.data.map((item) => (
                <Link
                  key={item.Id}
                  href={rutePesananPembelian.detail(item.PesananPembelianId)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <PackageCheck className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.Nomor}</p>
                    <p className="truncate text-xs text-muted-foreground">
                      {item.NamaPenyedia ?? '-'} · {tanggalLokal(item.TanggalTerima)}
                    </p>
                  </div>
                  <Badge variant="netral">{item.JumlahItem ?? 0} baris</Badge>
                </Link>
              ))}
            </div>
            <Pagination meta={penerimaan.meta} onNavigasi={(halaman) => navigasiHalaman(halaman, { cari })} />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
