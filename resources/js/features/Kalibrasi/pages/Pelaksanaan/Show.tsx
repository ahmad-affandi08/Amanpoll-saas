import { Head, Link } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileBadge, Calendar, ShieldCheck } from 'lucide-react';
import type { PelaksanaanKalibrasi } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { tanggal } from '@/components/shared/riwayat';
import { DialogFinalisasiKalibrasi } from '@/features/Kalibrasi/components/DialogFinalisasiKalibrasi';
import { EditorTitikUkur } from '@/features/Kalibrasi/components/EditorTitikUkur';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  pelaksanaan: PelaksanaanKalibrasi;
  teknisi: { Id: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function KalibrasiPelaksanaanShow({ pelaksanaan, wajib }: Props) {
  const badgeHasil = hasilKalibrasiBadge(pelaksanaan.Hasil);
  const sudahVerifikasi = Boolean(pelaksanaan.DiverifikasiPada);

  return (
    <KerangkaAplikasi>
      <Head title={`Kalibrasi ${pelaksanaan.Nomor} - ${pelaksanaan.aset?.Nama}`} />
      <div className="space-y-5">
        <KepalaHalaman
          judul={<span className="font-mono">{pelaksanaan.Nomor}</span>}
          lencana={
            <>
              <Badge variant="outline" className={badgeHasil.className}>
                {badgeHasil.label}
              </Badge>
              {sudahVerifikasi && (
                <Badge variant="sukses" className="gap-1">
                  <ShieldCheck className="size-3" />
                  Terverifikasi
                </Badge>
              )}
            </>
          }
          deskripsi={
            <>
              Aset: <span className="font-medium text-foreground">{pelaksanaan.aset?.Nama}</span> (
              <span className="font-mono">{pelaksanaan.aset?.KodeAset}</span>)
              {pelaksanaan.rencanaKalibrasi && ` • Terhubung ke Rencana Kalibrasi`}
            </>
          }
          aksi={
            !sudahVerifikasi ? (
              <DialogFinalisasiKalibrasi pelaksanaan={pelaksanaan} wajib={wajib.hasil} />
            ) : undefined
          }
        />

        {/* Certificate Authorized Banner if verified */}
        {sudahVerifikasi && (
          <div className="flex items-start gap-3 rounded-md border border-sukses-200 bg-sukses-50 p-4">
            <FileBadge className="size-5 text-sukses-600 mt-0.5 shrink-0" />
            <div className="space-y-1 text-xs">
              <div className="font-semibold text-foreground">
                Sertifikat Kalibrasi Resmi Terotorisasi:{' '}
                <span className="font-mono">{pelaksanaan.NomorSertifikat}</span>
              </div>
              <p className="text-grafit-700">
                Diverifikasi oleh{' '}
                <span className="font-medium">
                  {pelaksanaan.diverifikasiOleh?.Nama ?? 'Petugas Berwenang'}
                </span>{' '}
                pada {pelaksanaan.DiverifikasiPada}. Berlaku sampai dengan{' '}
                <span className="font-semibold">{tanggal(pelaksanaan.TanggalBerlakuSampai ?? null)}</span>.
                Siklus kalibrasi berikutnya pada instrumen telah otomatis diperbarui.
              </p>
            </div>
          </div>
        )}

        {/* Metadata Overview Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-1.5">
                <Calendar className="size-4 text-grafit-500" />
                Informasi Kalibrasi
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Tanggal Kalibrasi:</span>
                <span className="font-medium text-foreground">{tanggal(pelaksanaan.TanggalKalibrasi)}</span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Berlaku Sampai:</span>
                <span className="font-semibold text-foreground">
                  {(pelaksanaan.TanggalBerlakuSampai && tanggal(pelaksanaan.TanggalBerlakuSampai)) || (
                    <span className="text-muted-foreground italic">Belum diatur</span>
                  )}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Laboratorium Uji:</span>
                <span className="font-medium text-foreground">{pelaksanaan.Laboratorium || '—'}</span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Nomor Sertifikat:</span>
                <span className="font-mono font-semibold text-foreground">
                  {pelaksanaan.NomorSertifikat || (
                    <span className="text-muted-foreground font-normal italic">Belum terbit</span>
                  )}
                </span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-1.5">Pelaksana & Verifikator</CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Teknisi / Pelaksana:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.dilaksanakanOleh?.Nama ?? '—'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Penyedia / Mitra:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.penyedia?.Nama ?? 'Internal'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Diverifikasi Oleh:</span>
                <span className="font-medium text-foreground">
                  {pelaksanaan.diverifikasiOleh?.Nama ?? (
                    <span className="text-muted-foreground italic">Menunggu Finalisasi</span>
                  )}
                </span>
              </div>
              <div className="flex justify-between py-1">
                <span className="text-muted-foreground">Waktu Verifikasi:</span>
                <span className="text-muted-foreground font-mono">{pelaksanaan.DiverifikasiPada ?? '—'}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="border-b border-border pb-3">
              <CardTitle className="flex items-center gap-1.5">Kondisi Lingkungan & Catatan</CardTitle>
            </CardHeader>
            <CardContent className="pt-3 space-y-2.5 text-xs">
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Suhu Ruang:</span>
                <span className="font-medium text-foreground">
                  {(pelaksanaan.KondisiLingkungan as any)?.Suhu || '—'}
                </span>
              </div>
              <div className="flex justify-between py-1 border-b border-border/50">
                <span className="text-muted-foreground">Kelembapan Udara:</span>
                <span className="font-medium text-foreground">
                  {(pelaksanaan.KondisiLingkungan as any)?.Kelembapan || '—'}
                </span>
              </div>
              <div className="py-1">
                <span className="text-muted-foreground block mb-1">Catatan Pengujian:</span>
                <p className="text-grafit-700 italic bg-permukaan-100 p-2 rounded-sm text-[11px] border border-border">
                  {pelaksanaan.Catatan || 'Tidak ada catatan khusus.'}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <EditorTitikUkur pelaksanaan={pelaksanaan} sudahVerifikasi={sudahVerifikasi} />
      </div>
    </KerangkaAplikasi>
  );
}
