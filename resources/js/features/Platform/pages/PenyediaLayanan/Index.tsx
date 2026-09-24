import { Head } from '@inertiajs/react';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DialogPenyediaLayanan } from '@/features/Platform/components/DialogPenyediaLayanan';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import type { KategoriPenyediaLayanan, PenyediaLayanan } from '@/features/Platform/types';

interface Props {
  kategori: KategoriPenyediaLayanan[];
}

const DESKRIPSI_KATEGORI: Record<KategoriPenyediaLayanan['Kode'], string> = {
  Pembayaran:
    'Cara pelanggan membayar tagihan langganan. Boleh lebih dari satu yang aktif; pelanggan memilih saat membayar.',
  WhatsApp:
    'Pengirim pesan WhatsApp pemasaran dan notifikasi ke staf. Hanya satu yang dipakai pada satu waktu.',
  Email:
    'Pengirim seluruh email sistem: reset kata sandi, notifikasi, dan email pemasaran. Hanya satu yang dipakai pada satu waktu.',
};

/** Pengaturan payment gateway, WhatsApp, dan email milik platform (PRD 8.23). */
export default function PlatformPenyediaLayananIndex({ kategori }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Pembayaran, WhatsApp & Email" />

      <div className="space-y-5">
        <KepalaHalaman
          tanpaBreadcrumb
          judul="Pembayaran, WhatsApp & Email"
          deskripsi="Kredensial disimpan terenkripsi dan tidak pernah ditampilkan ulang. Isi ulang hanya bila ingin menggantinya."
        />

        {kategori.map((jenis) => (
          <Card key={jenis.Kode}>
            <CardHeader>
              <CardTitle>{jenis.Label}</CardTitle>
              <p className="text-sm text-muted-foreground">{DESKRIPSI_KATEGORI[jenis.Kode]}</p>
            </CardHeader>
            <CardContent>
              {jenis.Penyedia.length === 0 ? (
                <KeadaanKosong
                  judul="Belum ada penyedia terpasang."
                  deskripsi="Penyedia untuk kategori ini belum tersedia."
                />
              ) : (
                <ul className="divide-y divide-border">
                  {jenis.Penyedia.map((penyedia) => (
                    <li key={penyedia.Kode} className="flex flex-wrap items-start gap-x-4 gap-y-2 py-3">
                      <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <span className="font-medium text-foreground">{penyedia.Nama}</span>
                          <LencanaPenyedia kategori={jenis} penyedia={penyedia} />
                        </div>
                        <p className="text-sm text-muted-foreground">{penyedia.Keterangan}</p>
                      </div>
                      <DialogPenyediaLayanan kategori={jenis} penyedia={penyedia} />
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </KerangkaPlatform>
  );
}

function LencanaPenyedia({
  kategori,
  penyedia,
}: {
  kategori: KategoriPenyediaLayanan;
  penyedia: PenyediaLayanan;
}) {
  const tersimpan = penyedia.Isian.some((isian) => isian.Tersimpan);

  return (
    <>
      {kategori.Kode === 'WhatsApp' && (
        <Badge variant={penyedia.Resmi ? 'info' : 'perhatian'}>
          {penyedia.Resmi ? 'Resmi' : 'Tidak resmi'}
        </Badge>
      )}
      {penyedia.Aktif ? (
        <Badge variant="sukses">
          {kategori.BolehBanyakAktif && penyedia.Utama ? 'Aktif · Utama' : 'Aktif'}
        </Badge>
      ) : (
        <Badge variant="netral">{tersimpan ? 'Nonaktif' : 'Belum diatur'}</Badge>
      )}
      {penyedia.Aktif && penyedia.MendukungModeUji && penyedia.ModeUji && (
        <Badge variant="proses">Mode uji</Badge>
      )}
    </>
  );
}
