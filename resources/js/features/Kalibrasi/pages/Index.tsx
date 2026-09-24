import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { tanggal } from '@/components/shared/riwayat';
import { BellRing, Search, ArrowRight, Calendar } from 'lucide-react';
import type { PelaksanaanKalibrasi, RencanaKalibrasi, StatistikKepatuhan } from '@/features/Kalibrasi/types';
import { hasilKalibrasiBadge, statusKalibrasiBadge } from '@/features/Kalibrasi/status';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface Props {
  statistik: StatistikKepatuhan;
  rencanaKalibrasi: RencanaKalibrasi[];
  pelaksanaanTerbaru: PelaksanaanKalibrasi[];
}

export default function KalibrasiIndex({ statistik, rencanaKalibrasi, pelaksanaanTerbaru }: Props) {
  const [pencarian, setPencarian] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('SEMUA');
  const [sedangMemeriksa, setSedangMemeriksa] = useState(false);

  const jalankanPengingat = () => {
    setSedangMemeriksa(true);
    router.post(
      ruteKalibrasi.rencanaJalankanPengingat,
      {},
      {
        onFinish: () => setSedangMemeriksa(false),
      },
    );
  };

  const filteredRencana = rencanaKalibrasi.filter((rk) => {
    const cocokTeks =
      (rk.aset?.Nama ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (rk.aset?.KodeAset ?? '').toLowerCase().includes(pencarian.toLowerCase()) ||
      (rk.jenisKalibrasi?.Nama ?? '').toLowerCase().includes(pencarian.toLowerCase());

    const cocokStatus = filterStatus === 'SEMUA' || rk.StatusKalibrasi === filterStatus;

    return cocokTeks && cocokStatus;
  });

  return (
    <KerangkaAplikasi>
      <Head title="Dasbor Kalibrasi & Kepatuhan" />

      <div className="space-y-5">
        {/* Header */}
        <KepalaHalaman
          judul="Dasbor Kalibrasi"
          deskripsi="Ringkasan kepatuhan, jadwal jatuh tempo, dan riwayat kalibrasi instrumen."
          aksi={
            <>
              <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" onClick={jalankanPengingat} disabled={sedangMemeriksa}>
                  <BellRing className="size-4" />
                  {sedangMemeriksa ? 'Memeriksa...' : 'Kirim Pengingat'}
                </Button>
              </div>
            </>
          }
        />

        <DeretStatistik kolom={4}>
          <KartuStatistik
            menyatu
            label="Total Rencana"
            nilai={statistik.total}
            keterangan="Instrumen terdaftar"
          />
          <KartuStatistik
            menyatu
            label="Sertifikat Valid"
            nilai={statistik.valid}
            keterangan={<span className="text-sukses-700">Jadwal masih berlaku</span>}
          />
          <KartuStatistik
            menyatu
            label="Segera Jatuh Tempo"
            nilai={statistik.segeraJatuhTempo}
            keterangan={
              <span className={statistik.segeraJatuhTempo > 0 ? 'text-safety-700' : undefined}>
                Dalam rentang pengingat
              </span>
            }
          />
          <KartuStatistik
            menyatu
            label="Terlambat Kalibrasi"
            nilai={statistik.terlambat}
            keterangan={
              <span className={statistik.terlambat > 0 ? 'text-bahaya-700' : undefined}>
                Perlu tindakan segera
              </span>
            }
          />
        </DeretStatistik>

        {/* Main Content Grid: Jadwal Kalibrasi (2 col) + Pelaksanaan Terakhir (1 col) */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
          {/* Left: Upcoming Calibration Schedule */}
          <div className="lg:col-span-2 space-y-4">
            <Card>
              <CardHeader className="border-b border-border pb-4">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                  <div>
                    <CardTitle className="text-[15px]">Jadwal Kalibrasi Aset</CardTitle>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Status jatuh tempo dan kepatuhan instrumen aktif
                    </p>
                  </div>
                  <div className="relative w-full sm:w-64">
                    <Search
                      aria-hidden="true"
                      className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
                    />
                    <Input
                      aria-label="Cari aset atau jenis"
                      placeholder="Cari aset atau jenis..."
                      value={pencarian}
                      onChange={(e) => setPencarian(e.target.value)}
                      className="pl-8"
                    />
                  </div>
                </div>

                {/* Filter Tabs */}
                <div className="mt-3 max-w-full overflow-x-auto">
                  <div className="inline-flex rounded-sm border border-input">
                    {[
                      { key: 'SEMUA', label: 'Semua Status' },
                      { key: 'Terlambat', label: 'Terlambat' },
                      { key: 'SegeraJatuhTempo', label: 'Segera Jatuh Tempo' },
                      { key: 'Valid', label: 'Valid' },
                    ].map((item) => (
                      <button
                        key={item.key}
                        type="button"
                        onClick={() => setFilterStatus(item.key)}
                        aria-pressed={filterStatus === item.key}
                        className={`inline-flex h-8 min-h-11 items-center whitespace-nowrap border-r border-input px-3 text-[13px] transition-colors last:border-r-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:min-h-0 ${
                          filterStatus === item.key
                            ? 'bg-permukaan-100 font-medium text-foreground'
                            : 'text-grafit-700 hover:bg-permukaan-50'
                        }`}
                      >
                        {item.label}
                      </button>
                    ))}
                  </div>
                </div>
              </CardHeader>

              <CardContent className="p-0">
                {filteredRencana.length === 0 ? (
                  <div className="py-12">
                    <KeadaanKosong
                      judul="Belum ada rencana kalibrasi."
                      deskripsi={
                        pencarian || filterStatus !== 'SEMUA'
                          ? 'Tidak ada data rencana kalibrasi yang cocok dengan filter.'
                          : 'Rencana kalibrasi instrumen yang terdaftar akan ditampilkan di sini.'
                      }
                    />
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-[13px]">
                      <thead className="border-b border-border bg-permukaan-50 text-[12.5px] text-grafit-500">
                        <tr>
                          <th className="px-4 py-3 font-medium">Aset / Instrumen</th>
                          <th className="px-3 py-3 font-medium">Jenis Kalibrasi</th>
                          <th className="px-3 py-3 font-medium">Interval</th>
                          <th className="px-3 py-3 font-medium">Jatuh Tempo</th>
                          <th className="px-3 py-3 font-medium">Status</th>
                          <th className="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {filteredRencana.map((rk) => {
                          const badge = statusKalibrasiBadge(rk.StatusKalibrasi);
                          return (
                            <tr key={rk.Id} className="hover:bg-accent transition-colors">
                              <td className="px-4 py-3 font-medium text-foreground">
                                <Link
                                  href={ruteKalibrasi.rencanaDetail(rk.Id)}
                                  className="hover:underline font-semibold text-foreground block"
                                >
                                  {rk.aset?.Nama ?? 'Aset Tidak Dikenal'}
                                </Link>
                                <span className="font-mono text-[11px] text-muted-foreground">
                                  {rk.aset?.KodeAset}
                                </span>
                              </td>
                              <td className="px-3 py-3 text-muted-foreground">
                                {rk.jenisKalibrasi?.Nama ?? '—'}
                              </td>
                              <td className="px-3 py-3 text-muted-foreground whitespace-nowrap">
                                Setiap {rk.IntervalHari} hari
                              </td>
                              <td className="px-3 py-3 text-muted-foreground whitespace-nowrap">
                                <div>{tanggal(rk.TanggalBerikutnya)}</div>
                                {rk.SisaHari !== undefined && (
                                  <div
                                    className={`text-[11px] ${
                                      rk.SisaHari < 0
                                        ? 'text-bahaya-600 font-semibold'
                                        : rk.SisaHari <= rk.PeringatanHariSebelum
                                          ? 'text-safety-700 font-medium'
                                          : 'text-muted-foreground'
                                    }`}
                                  >
                                    {rk.SisaHari < 0
                                      ? `Terlambat ${Math.abs(rk.SisaHari)} hari`
                                      : `${rk.SisaHari} hari lagi`}
                                  </div>
                                )}
                              </td>
                              <td className="px-3 py-3 whitespace-nowrap">
                                <Badge variant="outline" className={badge.className}>
                                  {badge.label}
                                </Badge>
                              </td>
                              <td className="px-4 py-3 text-right whitespace-nowrap">
                                <Button asChild variant="ghost" size="sm" className="h-7 text-xs gap-1">
                                  <Link href={ruteKalibrasi.rencanaDetail(rk.Id)}>
                                    Detail
                                    <ArrowRight className="size-3" />
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

          {/* Right: Recent Executions Log */}
          <div>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between border-b border-border pb-4">
                <div>
                  <CardTitle className="text-[15px]">Pelaksanaan Terakhir</CardTitle>
                  <p className="text-xs text-muted-foreground mt-0.5">10 kegiatan kalibrasi terkini</p>
                </div>
                <Button asChild variant="ghost" size="sm" className="h-7 text-xs">
                  <Link href={ruteKalibrasi.pelaksanaan}>Lihat Semua</Link>
                </Button>
              </CardHeader>
              <CardContent className="p-0 divide-y divide-border">
                {pelaksanaanTerbaru.length === 0 ? (
                  <div className="p-5 text-center text-xs text-muted-foreground">
                    Belum ada riwayat pelaksanaan kalibrasi.
                  </div>
                ) : (
                  pelaksanaanTerbaru.map((pk) => {
                    const badge = hasilKalibrasiBadge(pk.Hasil);
                    return (
                      <div
                        key={pk.Id}
                        className="p-3.5 hover:bg-accent transition-colors flex items-start justify-between gap-2"
                      >
                        <div className="min-w-0 flex-1">
                          <div className="flex items-center gap-2">
                            <Link
                              href={ruteKalibrasi.pelaksanaanDetail(pk.Id)}
                              className="font-medium font-mono text-xs text-foreground hover:underline truncate"
                            >
                              {pk.Nomor}
                            </Link>
                            <Badge variant="outline" className={`text-[10px] py-0 px-1.5 ${badge.className}`}>
                              {badge.label}
                            </Badge>
                          </div>
                          <p className="text-xs text-muted-foreground truncate mt-0.5">
                            {pk.aset?.Nama ?? 'Aset'}
                          </p>
                          <div className="flex items-center gap-2 text-[11px] text-muted-foreground mt-1 font-mono">
                            <Calendar className="size-3" />
                            <span>{tanggal(pk.TanggalKalibrasi)}</span>
                            {pk.NomorSertifikat && (
                              <>
                                <span>•</span>
                                <span className="truncate font-mono">Sert: {pk.NomorSertifikat}</span>
                              </>
                            )}
                          </div>
                        </div>
                        <Button
                          asChild
                          variant="ghost"
                          size="icon"
                          className="sm:size-7 text-muted-foreground hover:text-foreground"
                        >
                          <Link href={ruteKalibrasi.pelaksanaanDetail(pk.Id)}>
                            <ArrowRight className="size-3.5" />
                          </Link>
                        </Button>
                      </div>
                    );
                  })
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
