import { type FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PackageCheck, Search } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginasi } from '@/types/global';
import type { PenerimaanPembelian } from '@/features/PenerimaanPembelian/types';
import { rutePenerimaanPembelian } from '@/features/PenerimaanPembelian/api';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface PesananMenunggu {
  Id: string;
  Nomor: string;
  Status: 'Dikirim' | 'DiterimaSebagian';
  NamaPenyedia: string;
  TanggalKirimRencana: string | null;
}

interface Props {
  penerimaan: Paginasi<PenerimaanPembelian>;
  /** PO yang sudah dikirim ke penyedia dan barangnya belum lengkap diterima. */
  menungguPenerimaan: PesananMenunggu[];
  filter: { cari?: string };
}

function tanggalLokal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleDateString('id-ID') : '-';
}

export default function PenerimaanPembelianIndex({ penerimaan, menungguPenerimaan, filter }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(rutePenerimaanPembelian.index, { cari }, { preserveState: true, replace: true });
  }

  return (
    <KerangkaAplikasi>
      <Head title="Penerimaan Pembelian" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Penerimaan Pembelian"
          deskripsi="Riwayat penerimaan barang; stok dan registrasi aset dibuat otomatis saat dokumen dicatat."
          aksi={
            <TombolEkspor url={rutePenerimaanPembelian.ekspor} filter={filter as Record<string, string>} />
          }
        />

        {menungguPenerimaan.length > 0 && (
          <section aria-labelledby="judul-menunggu" className="space-y-2">
            <h2 id="judul-menunggu" className="text-sm font-semibold text-foreground">
              Menunggu Penerimaan ({menungguPenerimaan.length})
            </h2>
            <div className="divide-y divide-border overflow-hidden rounded-md border border-border bg-card">
              {menungguPenerimaan.map((po) => (
                <Link
                  key={po.Id}
                  href={rutePesananPembelian.detail(po.Id)}
                  className="flex min-h-14 items-center gap-3 px-4 py-2.5 transition-colors hover:bg-permukaan-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                >
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono text-sm font-medium">{po.Nomor}</p>
                    <p className="truncate text-xs text-muted-foreground">
                      {po.NamaPenyedia || '-'} · rencana tiba {tanggalLokal(po.TanggalKirimRencana)}
                    </p>
                  </div>
                  <Badge variant={po.Status === 'Dikirim' ? 'proses' : 'perhatian'}>
                    {po.Status === 'Dikirim' ? 'Belum diterima' : 'Diterima sebagian'}
                  </Badge>
                  <span className="hidden text-xs font-medium text-primary sm:inline">Catat penerimaan</span>
                </Link>
              ))}
            </div>
          </section>
        )}

        <form onSubmit={terapkanFilter} className="flex flex-wrap items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
            />
            <Input
              aria-label="Cari nomor penerimaan atau surat jalan"
              placeholder="Cari nomor penerimaan atau surat jalan"
              className="pl-8"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Button type="submit" variant="secondary">
            Terapkan
          </Button>
        </form>

        {penerimaan.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/gudang.webp"
            judul="Belum ada penerimaan."
            deskripsi="Catat penerimaan dari halaman pesanan pembelian yang sudah dikirim."
          />
        ) : (
          <div className="overflow-hidden rounded-md border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-permukaan-50 text-left text-[12.5px] text-grafit-500">
                  <tr>
                    <th className="h-10 px-4 font-medium">Nomor</th>
                    <th className="h-10 px-4 font-medium">Pesanan / Penyedia</th>
                    <th className="h-10 px-4 font-medium">Gudang</th>
                    <th className="h-10 px-4 font-medium">Tanggal</th>
                    <th className="h-10 px-4 font-medium">Item</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {penerimaan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-permukaan-50">
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
                  className="flex min-h-24 items-center gap-3 p-4 transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                >
                  <PackageCheck className="size-4 shrink-0 text-grafit-500" />
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
            <KontrolPaginasi
              meta={penerimaan.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, { cari })}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
