import { Head, Link } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { tanggal } from '@/components/shared/riwayat';
import { Sliders, Clock, Building2, Plus } from 'lucide-react';
import type { RencanaKalibrasi } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge, statusKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface Props {
  rencana: RencanaKalibrasi;
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
}

export default function KalibrasiRencanaShow({ rencana }: Props) {
  const badgeKepatuhan = statusKalibrasiBadge(rencana.StatusKalibrasi);

  return (
    <KerangkaAplikasi>
      <Head title={`Rencana Kalibrasi - ${rencana.aset?.Nama}`} />
      <div className="space-y-5">
        <KepalaHalaman
          judul={rencana.aset?.Nama ?? 'Aset'}
          lencana={
            <Badge variant="outline" className={badgeKepatuhan.className}>
              {badgeKepatuhan.label}
            </Badge>
          }
          deskripsi={
            <>
              Kode Aset: <span className="font-mono">{rencana.aset?.KodeAset}</span> • Interval: Setiap{' '}
              {rencana.IntervalHari} hari
            </>
          }
          aksi={
            <Button asChild size="sm">
              <Link
                href={`/kalibrasi/pelaksanaan?bukaModal=1&rencanaId=${rencana.Id}&asetId=${rencana.AsetId}`}
              >
                <Plus className="size-4" />
                Jadwalkan Kalibrasi
              </Link>
            </Button>
          }
        />

        {/* Overview Information Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Card 1: Siklus Kalibrasi */}
          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-2">
                <Clock className="size-4 text-grafit-500" />
                Status Siklus & Jatuh Tempo
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-4 space-y-3 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Tanggal Mulai:</span>
                <span className="font-medium font-mono text-foreground">{rencana.TanggalMulai}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Jatuh Tempo Berikutnya:</span>
                <span className="font-semibold text-foreground">{tanggal(rencana.TanggalBerikutnya)}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Sisa Waktu:</span>
                <span
                  className={`font-semibold ${
                    (rencana.SisaHari ?? 0) < 0
                      ? 'text-bahaya-600'
                      : (rencana.SisaHari ?? 0) <= rencana.PeringatanHariSebelum
                        ? 'text-safety-700'
                        : 'text-sukses-600'
                  }`}
                >
                  {(rencana.SisaHari ?? 0) < 0
                    ? `Terlambat ${Math.abs(rencana.SisaHari ?? 0)} hari`
                    : `${rencana.SisaHari} hari lagi`}
                </span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Batas Pengingat Dini:</span>
                <span className="font-medium text-foreground">
                  {rencana.PeringatanHariSebelum} hari sebelumnya
                </span>
              </div>
            </CardContent>
          </Card>

          {/* Card 2: Metode & Jenis Kalibrasi */}
          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-2">
                <Sliders className="size-4 text-grafit-500" />
                Spesifikasi & Metode
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-4 space-y-3 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Jenis Kalibrasi:</span>
                <span className="font-medium text-foreground">{rencana.jenisKalibrasi?.Nama ?? '—'}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Kode Acuan:</span>
                <span className="font-mono text-foreground">{rencana.jenisKalibrasi?.Kode ?? '—'}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Template Titik Ukur:</span>
                <span className="font-medium text-foreground">
                  {rencana.jenisKalibrasi?.titikUkur?.length ?? 0} Titik Terdefinisi
                </span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Status Rencana:</span>
                <span className="font-medium text-foreground">{rencana.Aktif ? 'Aktif' : 'Nonaktif'}</span>
              </div>
            </CardContent>
          </Card>

          {/* Card 3: Penyedia Rekanan / Lab */}
          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-2">
                <Building2 className="size-4 text-grafit-500" />
                Laboratorium & Rekanan
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-4 space-y-3 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Penyedia Terpilih:</span>
                <span className="font-medium text-foreground">
                  {rencana.penyedia?.Nama ?? 'Internal Perusahaan'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Kode Penyedia:</span>
                <span className="font-mono text-foreground">{rencana.penyedia?.Kode ?? '—'}</span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Total Riwayat Pelaksanaan:</span>
                <span className="font-semibold text-foreground">
                  {rencana.pelaksanaanKalibrasi?.length ?? 0} Kali
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Riwayat Pelaksanaan Kalibrasi Terkait Rencana Ini */}
        <Card>
          <CardHeader className="border-b border-border pb-4">
            <CardTitle className="text-[15px]">Riwayat Pelaksanaan & Log Sertifikat</CardTitle>
            <p className="text-xs text-muted-foreground mt-0.5">
              Daftar kalibrasi yang telah dilakukan untuk rencana instrumen ini
            </p>
          </CardHeader>

          <CardContent className="p-0">
            {!rencana.pelaksanaanKalibrasi || rencana.pelaksanaanKalibrasi.length === 0 ? (
              <div className="py-12">
                <KeadaanKosong
                  judul="Belum ada riwayat pelaksanaan."
                  deskripsi="Kalibrasi yang dijadwalkan dan difinalisasi untuk instrumen ini akan muncul di sini."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-[13px]">
                  <thead className="border-b border-border bg-permukaan-50 text-[12.5px] text-grafit-500">
                    <tr>
                      <th className="px-4 py-3 font-medium">Nomor</th>
                      <th className="px-4 py-3 font-medium">Tanggal Kalibrasi</th>
                      <th className="px-4 py-3 font-medium">Nomor Sertifikat</th>
                      <th className="px-3 py-3 font-medium">Hasil</th>
                      <th className="px-3 py-3 font-medium">Masa Berlaku Sampai</th>
                      <th className="px-3 py-3 font-medium">Diverifikasi Oleh</th>
                      <th className="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {rencana.pelaksanaanKalibrasi.map((pk) => {
                      const badge = hasilKalibrasiBadge(pk.Hasil);
                      return (
                        <tr key={pk.Id} className="hover:bg-accent transition-colors">
                          <td className="px-4 py-3 font-semibold font-mono text-foreground">{pk.Nomor}</td>
                          <td className="px-4 py-3 text-muted-foreground">{tanggal(pk.TanggalKalibrasi)}</td>
                          <td className="px-4 py-3 font-medium font-mono text-foreground">
                            {pk.NomorSertifikat || (
                              <span className="text-muted-foreground italic">Belum Ada</span>
                            )}
                          </td>
                          <td className="px-3 py-3">
                            <Badge variant="outline" className={badge.className}>
                              {badge.label}
                            </Badge>
                          </td>
                          <td className="px-3 py-3 text-muted-foreground font-mono">
                            {tanggal(pk.TanggalBerlakuSampai ?? null)}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground">
                            {pk.diverifikasiOleh?.Nama ?? (
                              <span className="text-muted-foreground italic">Belum diverifikasi</span>
                            )}
                          </td>
                          <td className="px-4 py-3 text-right">
                            <Button asChild variant="outline" size="sm" className="h-7 text-xs">
                              <Link href={ruteKalibrasi.pelaksanaanDetail(pk.Id)}>Detail</Link>
                            </Button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}
