import { Head, router } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { DistribusiSosial, KontenSosial, PilihanSosial } from '@/features/Pemasaran/types';
import { DialogFormKonten } from '@/features/Pemasaran/components/DialogFormKonten';
import { DialogFormDistribusi } from '@/features/Pemasaran/components/DialogFormDistribusi';
import { DialogJadwal } from '@/features/Pemasaran/components/DialogJadwal';

interface Props {
  konten: KontenSosial[];
  pilihan: PilihanSosial;
}

const WARNA_STATUS: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
  Draf: 'outline',
  Review: 'secondary',
  Terjadwal: 'secondary',
  Diproses: 'secondary',
  Terbit: 'default',
  Gagal: 'destructive',
};

export default function PemasaranSosial({ konten, pilihan }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Konten Sosial" />

      <KepalaHalaman
        judul="Konten Sosial"
        deskripsi="Satu konten utama, banyak distribusi. Tiap distribusi punya caption, media, CTA, dan UTM sendiri."
        tanpaBreadcrumb
        aksi={<DialogFormKonten konten={null} pilihan={pilihan} />}
        className="mb-6"
      />

      {konten.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada konten sosial.</p>
      ) : (
        <div className="space-y-6">
          {konten.map((satu) => (
            <KartuKonten key={satu.Id} konten={satu} pilihan={pilihan} />
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function KartuKonten({ konten, pilihan }: { konten: KontenSosial; pilihan: PilihanSosial }) {
  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle className="text-base">{konten.Judul}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">
            {konten.Kode}
            {konten.KampanyeKode ? ` · kampanye ${konten.KampanyeKode}` : ' · tanpa kampanye'}
            {konten.HalamanSlug ? ` · ${konten.HalamanSlug}` : ''}
          </p>
        </div>
        <div className="flex shrink-0 gap-2">
          <DialogFormKonten konten={konten} pilihan={pilihan} />
          <DialogFormDistribusi konten={konten} distribusi={null} pilihan={pilihan} />
        </div>
      </CardHeader>

      <CardContent className="space-y-3">
        {konten.Ringkasan ? <p className="text-sm text-muted-foreground">{konten.Ringkasan}</p> : null}

        {konten.Distribusi.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Belum ada distribusi. Tambahkan channel agar konten ini punya tempat terbit.
          </p>
        ) : (
          konten.Distribusi.map((satu) => (
            <BarisDistribusi key={satu.Id} konten={konten} distribusi={satu} pilihan={pilihan} />
          ))
        )}
      </CardContent>
    </Card>
  );
}

function BarisDistribusi({
  konten,
  distribusi,
  pilihan,
}: {
  konten: KontenSosial;
  distribusi: DistribusiSosial;
  pilihan: PilihanSosial;
}) {
  const akar = rutePemasaran.sosialDistribusiDetail(konten.Id, distribusi.Id);

  return (
    <div className="rounded-lg border p-3">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="flex items-center gap-2">
          <Badge variant="outline">{distribusi.Channel}</Badge>
          <Badge variant={WARNA_STATUS[distribusi.Status] ?? 'secondary'}>{distribusi.Status}</Badge>
          {distribusi.JadwalPada ? (
            <span className="text-xs text-muted-foreground">Terjadwal {distribusi.JadwalPada}</span>
          ) : null}
        </div>

        <div className="flex flex-wrap gap-1">
          <DialogFormDistribusi konten={konten} distribusi={distribusi} pilihan={pilihan} />
          {distribusi.TujuanStatus.map((tujuan) => (
            <Button
              key={tujuan}
              variant="ghost"
              size="sm"
              onClick={() => router.post(`${akar}/status`, { Status: tujuan }, { preserveScroll: true })}
            >
              → {tujuan}
            </Button>
          ))}
          <DialogJadwal akar={akar} />
          {distribusi.JadwalPada ? (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => router.delete(`${akar}/jadwal`, { preserveScroll: true })}
            >
              Batalkan
            </Button>
          ) : null}
          {distribusi.Status !== 'Terbit' ? (
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.post(`${akar}/terbitkan`, {}, { preserveScroll: true })}
            >
              Terbitkan sekarang
            </Button>
          ) : null}
        </div>
      </div>

      <p className="mt-2 whitespace-pre-wrap text-sm text-foreground">{distribusi.Caption}</p>

      {distribusi.TautanBerUtm ? (
        <p className="mt-2 break-all font-mono text-xs text-muted-foreground">{distribusi.TautanBerUtm}</p>
      ) : (
        <p className="mt-2 text-xs text-muted-foreground">
          Belum ada tautan tujuan, jadi trafiknya tidak akan terbaca di attribution.
        </p>
      )}

      {distribusi.Galat ? <p className="mt-1 text-xs text-destructive">{distribusi.Galat}</p> : null}
      {distribusi.UrlTerbit ? (
        <p className="mt-1 break-all text-xs text-muted-foreground">Terbit di {distribusi.UrlTerbit}</p>
      ) : null}
    </div>
  );
}
