import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { Badge } from '@/components/ui/badge';
import type { Paginasi } from '@/types/global';
import type { PanggilanBalikWeb, PengirimanPanggilanBalikWeb } from '@/features/Integrasi/types';
import { ruteIntegrasi } from '@/features/Integrasi/api';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';

interface Props {
  webhook: PanggilanBalikWeb;
  pengiriman: Paginasi<PengirimanPanggilanBalikWeb>;
}

const VARIAN_STATUS = {
  Antri: 'info',
  Berhasil: 'sukses',
  Gagal: 'perhatian',
  GagalPermanen: 'bahaya',
} as const;

function waktuLokal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID') : '—';
}

export default function IntegrasiPengiriman({ webhook, pengiriman }: Props) {
  return (
    <AppLayout>
      <Head title={`Pengiriman ${webhook.Nama}`} />
      <BreadcrumbHalaman />
      <div className="space-y-6">
        <Link
          href={ruteIntegrasi.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali ke Integrasi
        </Link>

        <header>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-2xl font-semibold tracking-tight">{webhook.Nama}</h1>
            <Badge variant={webhook.Aktif ? 'sukses' : 'netral'}>
              {webhook.Aktif ? 'Aktif' : 'Nonaktif'}
            </Badge>
          </div>
          <p className="truncate font-mono text-sm text-muted-foreground">{webhook.Url}</p>
          <p className="mt-1 text-xs text-muted-foreground">Berlangganan: {webhook.Peristiwa.join(', ')}</p>
        </header>

        {pengiriman.data.length === 0 ? (
          <EmptyState
            judul="Belum ada pengiriman."
            deskripsi="Pengiriman muncul setelah peristiwa yang dilanggani terjadi."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Peristiwa</th>
                    <th className="px-4 py-3">Dibuat</th>
                    <th className="px-4 py-3">Percobaan</th>
                    <th className="px-4 py-3">HTTP</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {pengiriman.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        {item.Peristiwa}
                        {item.Respons && (
                          <p className="truncate text-xs text-muted-foreground">{item.Respons}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {waktuLokal(item.DibuatPada)}
                        {item.DikirimPada && (
                          <p className="text-muted-foreground">terkirim {waktuLokal(item.DikirimPada)}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {item.Percobaan}
                        {item.JadwalCobaLagiPada && (
                          <p className="text-muted-foreground">ulang {waktuLokal(item.JadwalCobaLagiPada)}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 font-mono text-xs">{item.StatusHttp ?? '—'}</td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {pengiriman.data.map((item) => (
                <div key={item.Id} className="space-y-1 p-4">
                  <div className="flex items-start justify-between gap-3">
                    <p className="min-w-0 flex-1 truncate font-medium">{item.Peristiwa}</p>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {waktuLokal(item.DibuatPada)} · percobaan {item.Percobaan} · HTTP {item.StatusHttp ?? '—'}
                  </p>
                </div>
              ))}
            </div>
            <Pagination meta={pengiriman.meta} onNavigasi={(halaman) => navigasiHalaman(halaman)} />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
