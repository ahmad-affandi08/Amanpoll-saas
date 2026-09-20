import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EmptyState } from '@/components/shared/EmptyState';
import {
  ArrowLeft,
  Sliders,
  Clock,
  Building2,
  Plus,
} from 'lucide-react';
import type { RencanaKalibrasi } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge, statusKalibrasiBadge } from '@/features/Kalibrasi/status';

interface Props {
  rencana: RencanaKalibrasi;
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
}

export default function RencanaKalibrasiShow({ rencana }: Props) {
  const badgeKepatuhan = statusKalibrasiBadge(rencana.StatusKalibrasi);

  return (
    <AppLayout>
      <Head title={`Rencana Kalibrasi - ${rencana.aset?.Nama}`} />

      <div className="space-y-6">
        {/* Navigation & Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-center gap-3">
            <Button asChild variant="outline" size="icon" className="size-8">
              <Link href="/kalibrasi/rencana">
                <ArrowLeft className="size-4" />
              </Link>
            </Button>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-semibold tracking-tight text-foreground">
                  {rencana.aset?.Nama ?? 'Aset'}
                </h1>
                <Badge variant="outline" className={badgeKepatuhan.className}>
                  {badgeKepatuhan.label}
                </Badge>
              </div>
              <p className="text-xs text-muted-foreground mt-0.5">
                Kode Aset: <span className="font-mono">{rencana.aset?.KodeAset}</span> • Interval: Setiap {rencana.IntervalHari} hari
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <Button asChild size="sm">
              <Link
                href={`/kalibrasi/pelaksanaan?bukaModal=1&rencanaId=${rencana.Id}&asetId=${rencana.AsetId}`}
              >
                <Plus className="mr-1.5 size-4" />
                Jadwalkan Kalibrasi
              </Link>
            </Button>
          </div>
        </div>

        {/* Overview Information Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Card 1: Siklus Kalibrasi */}
          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                <Clock className="size-4 text-teknisi-700" />
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
                <span className="font-bold font-mono text-foreground">{rencana.TanggalBerikutnya}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Sisa Waktu:</span>
                <span className={`font-semibold ${
                  (rencana.SisaHari ?? 0) < 0
                    ? 'text-bahaya-600'
                    : (rencana.SisaHari ?? 0) <= rencana.PeringatanHariSebelum
                    ? 'text-safety-600'
                    : 'text-sukses-600'
                }`}>
                  {(rencana.SisaHari ?? 0) < 0
                    ? `Terlambat ${Math.abs(rencana.SisaHari ?? 0)} hari`
                    : `${rencana.SisaHari} hari lagi`}
                </span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Batas Pengingat Dini:</span>
                <span className="font-medium text-foreground">{rencana.PeringatanHariSebelum} hari sebelumnya</span>
              </div>
            </CardContent>
          </Card>

          {/* Card 2: Metode & Jenis Kalibrasi */}
          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                <Sliders className="size-4 text-teknisi-700" />
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
          <Card className="border-border">
            <CardHeader className="pb-3 border-b border-border">
              <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                <Building2 className="size-4 text-teknisi-700" />
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
                <span className="font-bold text-foreground">
                  {rencana.pelaksanaanKalibrasi?.length ?? 0} Kali
                </span>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Riwayat Pelaksanaan Kalibrasi Terkait Rencana Ini */}
        <Card className="border-border">
          <CardHeader className="p-4 sm:p-5 border-b border-border">
            <CardTitle className="text-base font-semibold text-foreground">
              Riwayat Pelaksanaan & Log Sertifikat
            </CardTitle>
            <p className="text-xs text-muted-foreground mt-0.5">
              Daftar kalibrasi yang telah dilakukan untuk rencana instrumen ini
            </p>
          </CardHeader>

          <CardContent className="p-0">
            {(!rencana.pelaksanaanKalibrasi || rencana.pelaksanaanKalibrasi.length === 0) ? (
              <div className="py-12">
                <EmptyState
                  judul="Belum ada riwayat pelaksanaan."
                  deskripsi="Kalibrasi yang dijadwalkan dan difinalisasi untuk instrumen ini akan muncul di sini."
                />
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs text-left">
                  <thead className="bg-permukaan-50 text-muted-foreground border-b border-border">
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
                        <tr key={pk.Id} className="hover:bg-permukaan-50 transition-colors">
                          <td className="px-4 py-3 font-semibold font-mono text-foreground">
                            {pk.Nomor}
                          </td>
                          <td className="px-4 py-3 text-muted-foreground font-mono">
                            {pk.TanggalKalibrasi}
                          </td>
                          <td className="px-4 py-3 font-medium font-mono text-foreground">
                            {pk.NomorSertifikat || <span className="text-muted-foreground italic">Belum Ada</span>}
                          </td>
                          <td className="px-3 py-3">
                            <Badge variant="outline" className={badge.className}>
                              {badge.label}
                            </Badge>
                          </td>
                          <td className="px-3 py-3 text-muted-foreground font-mono">
                            {pk.TanggalBerlakuSampai || '—'}
                          </td>
                          <td className="px-3 py-3 text-muted-foreground">
                            {pk.diverifikasiOleh?.Nama ?? <span className="text-muted-foreground italic">Belum diverifikasi</span>}
                          </td>
                          <td className="px-4 py-3 text-right">
                            <Button asChild variant="outline" size="sm" className="h-7 text-xs">
                              <Link href={`/kalibrasi/pelaksanaan/${pk.Id}`}>
                                Detail
                              </Link>
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
    </AppLayout>
  );
}
