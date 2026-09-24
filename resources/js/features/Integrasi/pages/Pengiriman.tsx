import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import type { Paginasi } from '@/types/global';
import type { PanggilanBalikWeb, PengirimanPanggilanBalikWeb } from '@/features/Integrasi/types';
import { ruteIntegrasi } from '@/features/Integrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

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
    <KerangkaAplikasi>
      <Head title={`Pengiriman ${webhook.Nama}`} />
      <div className="space-y-5">
        <Link
          href={ruteIntegrasi.index}
          className="inline-flex min-h-11 items-center gap-2 rounded-[5px] text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
        >
          <ArrowLeft className="size-4" /> Kembali ke Integrasi
        </Link>

        <KepalaHalaman
          judul={webhook.Nama}
          lencana={
            <Badge variant={webhook.Aktif ? 'sukses' : 'netral'}>
              {webhook.Aktif ? 'Aktif' : 'Nonaktif'}
            </Badge>
          }
          deskripsi={<span className="block truncate font-mono">{webhook.Url}</span>}
          meta={<span className="text-xs">Berlangganan: {webhook.Peristiwa.join(', ')}</span>}
        />

        {pengiriman.data.length === 0 ? (
          <KeadaanKosong
            judul="Belum ada pengiriman."
            deskripsi="Pengiriman muncul setelah peristiwa yang dilanggani terjadi."
          />
        ) : (
          <div className="overflow-hidden rounded-md border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-permukaan-50 text-left text-[12.5px] text-grafit-500 [&_th]:font-medium">
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
                    <tr key={item.Id} className="hover:bg-accent">
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
            <KontrolPaginasi meta={pengiriman.meta} onNavigasi={(halaman) => navigasiHalaman(halaman)} />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
