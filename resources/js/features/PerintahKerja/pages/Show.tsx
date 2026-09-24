import { Head, Link, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
  KodeKegagalan,
  PenugasanPerintahKerjaItem,
  PerintahKerja,
  StatusPerintahKerja,
  StokOpsi,
  TeknisiOpsi,
} from '@/features/PerintahKerja/types';
import {
  VARIAN_PRIORITAS_PERINTAH_KERJA,
  VARIAN_STATUS_PERINTAH_KERJA,
} from '@/features/PerintahKerja/status';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogUbahStatus } from '@/features/PerintahKerja/components/DialogUbahStatus';
import { DialogTugaskanTeknisi } from '@/features/PerintahKerja/components/DialogTugaskanTeknisi';
import { DialogAlihkanUnitPengelola } from '@/features/PerintahKerja/components/DialogAlihkanUnitPengelola';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { DialogReservasiSukuCadang } from '@/features/PerintahKerja/components/DialogReservasiSukuCadang';
import { DialogCatatBiaya } from '@/features/PerintahKerja/components/DialogCatatBiaya';
import { DialogAnalisisKegagalan } from '@/features/PerintahKerja/components/DialogAnalisisKegagalan';
import { DialogDowntimeAset } from '@/features/PerintahKerja/components/DialogDowntimeAset';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface GudangOpsi {
  Id: string;
  Nama: string;
}

interface PenyediaOpsi {
  Id: string;
  Nama: string;
}

interface Props {
  perintahKerja: PerintahKerja;
  dapatMengelola: boolean;
  dapatMengoperasikan: boolean;
  transisiDiizinkan: StatusPerintahKerja[];
  penugasanSaya: PenugasanPerintahKerjaItem | null;
  teknisi: TeknisiOpsi[];
  /** Pengguna aktif yang tidak tampil di pilihan teknisi karena lingkupnya tidak mencakup tiket ini. */
  jumlahTeknisiDiluarLingkup: number;
  /** Organisasi memakai unit pengelola (PRD 8.21). */
  unitPengelolaDipakai: boolean;
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  stok: StokOpsi[];
  gudang: GudangOpsi[];
  penyedia: PenyediaOpsi[];
  kodeKegagalan: KodeKegagalan[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(nilai);
}

// MAIN PAGE COMPONENT
export default function PerintahKerjaShow({
  perintahKerja,
  dapatMengelola,
  dapatMengoperasikan,
  transisiDiizinkan,
  penugasanSaya,
  teknisi,
  jumlahTeknisiDiluarLingkup,
  unitPengelolaDipakai,
  pilihanUnitPengelola,
  stok,
  kodeKegagalan,
  wajib,
}: Props) {
  const dapatDialihkan =
    dapatMengelola &&
    unitPengelolaDipakai &&
    perintahKerja.Status !== 'Ditutup' &&
    perintahKerja.Status !== 'Dibatalkan';

  // Aksi respon penugasan teknisi
  const formResponsPenugasan = useForm({
    Respons: 'Terima' as 'Terima' | 'Tolak',
    Catatan: '',
  });

  const tanganiResponsPenugasan = (respons: 'Terima' | 'Tolak') => {
    if (!penugasanSaya) return;
    formResponsPenugasan.setData('Respons', respons);
    formResponsPenugasan.post(rutePerintahKerja.penugasanRespons(perintahKerja.Id, penugasanSaya.Id), {
      preserveScroll: true,
    });
  };

  // Aksi waktu kerja
  const formWaktuKerja = useForm({
    Aksi: 'Mulai' as 'Mulai' | 'Jeda' | 'Lanjut' | 'Selesai',
    Catatan: '',
  });

  const tanganiWaktuKerja = (aksi: 'Mulai' | 'Jeda' | 'Lanjut' | 'Selesai') => {
    formWaktuKerja.setData('Aksi', aksi);
    formWaktuKerja.post(rutePerintahKerja.waktuKerja(perintahKerja.Id), {
      preserveScroll: true,
    });
  };

  // Aksi konsumsi / kembalikan suku cadang
  const formSukuCadang = useForm({
    ReservasiSukuCadangId: '',
    Aksi: 'Pakai' as 'Pakai' | 'Kembalikan',
  });

  const tanganiAksiSukuCadang = (reservasiId: string, aksi: 'Pakai' | 'Kembalikan') => {
    formSukuCadang.setData({
      ReservasiSukuCadangId: reservasiId,
      Aksi: aksi,
    });
    formSukuCadang.post(rutePerintahKerja.sukuCadang(perintahKerja.Id), {
      preserveScroll: true,
    });
  };

  // Cek apakah ada sesi kerja aktif
  const sesiKerjaAktif = perintahKerja.WaktuKerja?.find((w) => w.SelesaiPada === null);
  const waktuHentiAktif = perintahKerja.WaktuHenti?.find((h) => h.SelesaiPada === null);

  return (
    <KerangkaAplikasi>
      <Head title={`${perintahKerja.Nomor} - Perintah Kerja`} />

      <div className="mb-5">
        <Link
          href={rutePerintahKerja.index}
          className="inline-flex min-h-11 items-center gap-2 rounded-[5px] text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
        >
          ← Kembali ke Daftar Perintah Kerja
        </Link>
      </div>

      <KepalaHalaman
        className="mb-6"
        judul={perintahKerja.Judul}
        labelBreadcrumb={perintahKerja.Nomor}
        lencana={
          <>
            <span className="font-mono text-sm font-semibold text-muted-foreground">
              {perintahKerja.Nomor}
            </span>
            <Badge variant="outline">{perintahKerja.Jenis}</Badge>
            <Badge variant={VARIAN_PRIORITAS_PERINTAH_KERJA[perintahKerja.Prioritas]}>
              {perintahKerja.Prioritas}
            </Badge>
            <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[perintahKerja.Status]}>{perintahKerja.Status}</Badge>
            {perintahKerja.NomorKeluhan && (
              <Badge variant="info">Keluhan: {perintahKerja.NomorKeluhan}</Badge>
            )}
          </>
        }
        deskripsi={
          <>
            Lokasi: {perintahKerja.NamaLokasi ?? '—'}
            {unitPengelolaDipakai && <> · Unit pengelola: {perintahKerja.UnitPengelola?.Nama ?? '—'}</>} ·
            Persentase Selesai: {perintahKerja.PersentaseSelesai}%
          </>
        }
        aksi={
          <>
            {penugasanSaya && penugasanSaya.Status === 'Ditugaskan' && (
              <div className="flex items-center gap-2">
                <Button
                  size="sm"
                  onClick={() => tanganiResponsPenugasan('Terima')}
                  className="cursor-pointer bg-sukses-600 hover:bg-sukses-700 text-white"
                >
                  Terima Tugas
                </Button>
                <Button
                  size="sm"
                  variant="destructive"
                  onClick={() => tanganiResponsPenugasan('Tolak')}
                  className="cursor-pointer"
                >
                  Tolak
                </Button>
              </div>
            )}

            {dapatDialihkan && (
              <DialogAlihkanUnitPengelola
                perintahKerja={perintahKerja}
                pilihanUnitPengelola={pilihanUnitPengelola}
                wajib={wajib.unitPengelola}
              />
            )}

            <DialogUbahStatus
              perintahKerja={perintahKerja}
              transisi={transisiDiizinkan}
              wajib={wajib.status}
            />
          </>
        }
      />

      {/* BANNER NOTIFIKASI SESI AKTIF */}
      {(sesiKerjaAktif || waktuHentiAktif) && (
        <div className="mb-6 grid gap-3 sm:grid-cols-2">
          {sesiKerjaAktif && (
            <div className="flex items-center justify-between rounded-lg border border-teknisi-600/30 bg-teknisi-600/10 p-3 text-sm">
              <div>
                <span className="font-semibold text-teknisi-700">Sesi Kerja Berjalan: </span>
                <span>
                  {sesiKerjaAktif.NamaPengguna ?? 'Teknisi'} sejak {formatTanggal(sesiKerjaAktif.MulaiPada)}
                </span>
              </div>
              {dapatMengoperasikan && (
                <div className="flex gap-1.5">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => tanganiWaktuKerja('Jeda')}
                    className="cursor-pointer h-7 text-xs"
                  >
                    Jeda
                  </Button>
                  <Button
                    size="sm"
                    onClick={() => tanganiWaktuKerja('Selesai')}
                    className="cursor-pointer h-7 text-xs bg-teknisi-700 text-white"
                  >
                    Selesai Kerja
                  </Button>
                </div>
              )}
            </div>
          )}

          {waktuHentiAktif && (
            <div className="flex items-center justify-between rounded-lg border border-bahaya-600/30 bg-bahaya-600/10 p-3 text-sm">
              <div>
                <span className="font-semibold text-bahaya-700">Downtime Aktif: </span>
                <span>
                  {waktuHentiAktif.NamaAset ?? 'Aset'} ({waktuHentiAktif.Alasan})
                </span>
              </div>
            </div>
          )}
        </div>
      )}

      {/* MAIN TWO COLUMN LAYOUT */}
      <div className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        {/* KOLOM UTAMA */}
        <div className="space-y-6">
          {/* DETAIL & INSTRUKSI */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <CardTitle className="text-base font-semibold">Instruksi & Lingkup Pekerjaan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="whitespace-pre-wrap text-sm leading-relaxed text-foreground">
                {perintahKerja.Deskripsi || 'Tidak ada catatan deskripsi pekerjaan.'}
              </p>

              {perintahKerja.RingkasanPenyelesaian && (
                <div className="rounded-md border border-sukses-600/20 bg-sukses-600/5 p-3">
                  <div className="text-xs font-semibold text-sukses-700 uppercase tracking-wider">
                    Ringkasan Penyelesaian
                  </div>
                  <p className="mt-1 text-sm text-foreground">{perintahKerja.RingkasanPenyelesaian}</p>
                </div>
              )}

              <div className="grid gap-4 border-t border-border pt-4 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground block text-xs">Jadwal Mulai</span>
                  <span className="font-medium">{formatTanggal(perintahKerja.DijadwalkanMulaiPada)}</span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Jadwal Selesai</span>
                  <span className="font-medium">{formatTanggal(perintahKerja.DijadwalkanSelesaiPada)}</span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Downtime Mesin</span>
                  <span className="font-medium">
                    {perintahKerja.MembutuhkanWaktuHenti ? 'Diperlukan' : 'Tidak diperlukan'}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-xs">Persetujuan Hasil</span>
                  <span className="font-medium">
                    {perintahKerja.MembutuhkanPersetujuan ? 'Diperlukan' : 'Tidak diperlukan'}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* DAFTAR ASET TERKAIT */}
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base font-semibold">Aset yang Ditangani</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="divide-y divide-border">
                {perintahKerja.Aset?.map((aset) => (
                  <div key={aset.Id} className="flex items-center justify-between py-2.5 text-sm">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono text-xs font-semibold text-muted-foreground">
                          {aset.KodeAset}
                        </span>
                        <span className="font-medium">{aset.Nama}</span>
                        {aset.Utama && <Badge variant="secondary">Aset Utama</Badge>}
                      </div>
                      <div className="text-xs text-muted-foreground mt-0.5">
                        Kondisi Awal: {aset.KondisiAwal ?? '—'} · Kondisi Akhir: {aset.KondisiAkhir ?? '—'}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* SUKU CADANG & BAHAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Suku Cadang & Bahan</CardTitle>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Reservasi suku cadang dari gudang dan catat pemakaian aktualnya.
                </p>
              </div>
              {dapatMengoperasikan && (
                <DialogReservasiSukuCadang
                  perintahKerja={perintahKerja}
                  stok={stok}
                  wajib={wajib.reservasi}
                />
              )}
            </CardHeader>
            <CardContent className="space-y-4">
              {/* TABEL RESERVASI */}
              <div>
                <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                  Daftar Reservasi
                </h4>
                {perintahKerja.ReservasiSukuCadang && perintahKerja.ReservasiSukuCadang.length > 0 ? (
                  <div className="overflow-x-auto rounded border border-border">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-muted/40 text-muted-foreground border-b border-border">
                        <tr>
                          <th className="px-3 py-2">Suku Cadang</th>
                          <th className="px-3 py-2">Gudang</th>
                          <th className="px-3 py-2">Jumlah</th>
                          <th className="px-3 py-2">Status</th>
                          <th className="px-3 py-2 text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {perintahKerja.ReservasiSukuCadang.map((res) => (
                          <tr key={res.Id}>
                            <td className="px-3 py-2 font-medium">{res.NamaSukuCadang ?? '—'}</td>
                            <td className="px-3 py-2 text-muted-foreground">{res.NamaGudang ?? '—'}</td>
                            <td className="px-3 py-2">{res.Jumlah} unit</td>
                            <td className="px-3 py-2">
                              <Badge variant={res.Status === 'Disetujui' ? 'sukses' : 'perhatian'}>
                                {res.Status}
                              </Badge>
                            </td>
                            <td className="px-3 py-2 text-right">
                              {dapatMengoperasikan && res.Status !== 'Dipakai' && (
                                <div className="flex justify-end gap-1.5">
                                  <Button
                                    size="sm"
                                    onClick={() => tanganiAksiSukuCadang(res.Id, 'Pakai')}
                                    className="cursor-pointer h-6 px-2 text-[11px] bg-teknisi-700 text-white"
                                  >
                                    Pakai
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => tanganiAksiSukuCadang(res.Id, 'Kembalikan')}
                                    className="cursor-pointer h-6 px-2 text-[11px]"
                                  >
                                    Kembalikan
                                  </Button>
                                </div>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground italic">
                    Belum ada reservasi suku cadang untuk pekerjaan ini.
                  </p>
                )}
              </div>

              {/* PEMAKAIAN AKTUAL */}
              {perintahKerja.PemakaianSukuCadang && perintahKerja.PemakaianSukuCadang.length > 0 && (
                <div className="pt-2">
                  <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                    Pemakaian Aktual & Biaya
                  </h4>
                  <div className="overflow-x-auto rounded border border-border">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-muted/40 text-muted-foreground border-b border-border">
                        <tr>
                          <th className="px-3 py-2">Suku Cadang</th>
                          <th className="px-3 py-2">Jumlah</th>
                          <th className="px-3 py-2">Harga Satuan</th>
                          <th className="px-3 py-2">Total</th>
                          <th className="px-3 py-2">Waktu Pemakaian</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border">
                        {perintahKerja.PemakaianSukuCadang.map((pem) => (
                          <tr key={pem.Id}>
                            <td className="px-3 py-2 font-medium">{pem.NamaSukuCadang ?? '—'}</td>
                            <td className="px-3 py-2">{pem.Jumlah} unit</td>
                            <td className="px-3 py-2">{formatRupiah(pem.HargaSatuan)}</td>
                            <td className="px-3 py-2 font-medium">
                              {formatRupiah(pem.Jumlah * pem.HargaSatuan)}
                            </td>
                            <td className="px-3 py-2 text-muted-foreground">
                              {formatTanggal(pem.DipakaiPada)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* ANALISIS KEGAGALAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Analisis Kegagalan & Akar Masalah</CardTitle>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Standar taksonomi Problem-Cause-Remedy untuk evaluasi keandalan aset.
                </p>
              </div>
              {dapatMengoperasikan && (
                <DialogAnalisisKegagalan
                  perintahKerja={perintahKerja}
                  kodeKegagalan={kodeKegagalan}
                  wajib={wajib.analisis}
                />
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.AnalisisKegagalan ? (
                <dl className="grid gap-3 text-sm sm:grid-cols-3">
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">Akar Masalah</dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.AkarMasalah || '—'}
                    </dd>
                  </div>
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">
                      Tindakan Korektif
                    </dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.TindakanKorektif || '—'}
                    </dd>
                  </div>
                  <div className="rounded-md border border-border p-3 bg-muted/20">
                    <dt className="text-xs font-semibold text-muted-foreground uppercase">
                      Tindakan Pencegahan
                    </dt>
                    <dd className="mt-1 text-sm font-medium">
                      {perintahKerja.AnalisisKegagalan.TindakanPencegahan || '—'}
                    </dd>
                  </div>
                </dl>
              ) : (
                <p className="text-xs text-muted-foreground italic">
                  Belum ada analisis kegagalan yang dicatat untuk pekerjaan ini.
                </p>
              )}
            </CardContent>
          </Card>

          {/* PANEL KOLABORASI (Berkas, Komentar, Tag, Kolom Kustom) */}
          <PanelKolaborasi jenisEntitas="PerintahKerja" entitasId={perintahKerja.Id} />
        </div>

        {/* KOLOM KANAN / SIDEBAR */}
        <div className="space-y-6">
          {/* PENUGASAN TEKNISI */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <CardTitle className="text-base font-semibold">Penugasan Teknisi</CardTitle>
              {dapatMengelola && (
                <DialogTugaskanTeknisi
                  perintahKerja={perintahKerja}
                  teknisi={teknisi}
                  wajib={wajib.penugasan}
                  jumlahDiluarLingkup={jumlahTeknisiDiluarLingkup}
                />
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.Penugasan && perintahKerja.Penugasan.length > 0 ? (
                <ul className="divide-y divide-border">
                  {perintahKerja.Penugasan.map((t) => (
                    <li key={t.Id} className="flex items-center justify-between py-2 text-sm">
                      <div>
                        <div className="font-medium">{t.NamaPengguna ?? 'Teknisi'}</div>
                        <div className="text-xs text-muted-foreground">
                          Peran: {t.PeranTugas} · Ditugaskan {formatTanggal(t.DitugaskanPada)}
                        </div>
                      </div>
                      <Badge
                        variant={
                          t.Status === 'Diterima'
                            ? 'sukses'
                            : t.Status === 'Ditolak'
                              ? 'destructive'
                              : 'perhatian'
                        }
                      >
                        {t.Status}
                      </Badge>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada teknisi ditugaskan.</p>
              )}
            </CardContent>
          </Card>

          {/* WAKTU KERJA / PENCATATAN JAM */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Waktu Kerja</CardTitle>
                <div className="text-xs font-semibold text-teknisi-700 mt-0.5">
                  Total: {perintahKerja.TotalWaktuKerjaMenit ?? 0} Menit
                </div>
              </div>
              {dapatMengoperasikan && !sesiKerjaAktif && (
                <Button
                  size="sm"
                  onClick={() => tanganiWaktuKerja('Mulai')}
                  className="cursor-pointer bg-teknisi-700 text-white h-7 text-xs"
                >
                  Mulai Kerja
                </Button>
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.WaktuKerja && perintahKerja.WaktuKerja.length > 0 ? (
                <div className="space-y-2 text-xs">
                  {perintahKerja.WaktuKerja.map((w) => (
                    <div
                      key={w.Id}
                      className="rounded border border-border p-2 flex justify-between items-center"
                    >
                      <div>
                        <span className="font-medium block">{w.NamaPengguna ?? 'Teknisi'}</span>
                        <span className="text-muted-foreground">
                          {formatTanggal(w.MulaiPada)} →{' '}
                          {w.SelesaiPada ? formatTanggal(w.SelesaiPada) : 'Berjalan'}
                        </span>
                      </div>
                      <span className="font-mono font-medium">
                        {w.DurasiMenit ? `${w.DurasiMenit}m` : 'Aktif'}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada jam kerja tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* DOWNTIME ASET */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Downtime Aset</CardTitle>
                <div className="text-xs font-semibold text-bahaya-600 mt-0.5">
                  Total: {perintahKerja.TotalDowntimeMenit ?? 0} Menit
                </div>
              </div>
              {dapatMengoperasikan && (
                <DialogDowntimeAset perintahKerja={perintahKerja} wajib={wajib.waktuHenti} />
              )}
            </CardHeader>
            <CardContent>
              {perintahKerja.WaktuHenti && perintahKerja.WaktuHenti.length > 0 ? (
                <div className="space-y-2 text-xs">
                  {perintahKerja.WaktuHenti.map((h) => (
                    <div
                      key={h.Id}
                      className="rounded border border-border p-2 flex justify-between items-center"
                    >
                      <div>
                        <span className="font-medium block">{h.NamaAset ?? 'Aset'}</span>
                        <span className="text-muted-foreground">
                          {h.Jenis} · {h.Alasan}
                        </span>
                      </div>
                      <span className="font-mono font-medium">
                        {h.DurasiMenit ? `${h.DurasiMenit}m` : 'Mati'}
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground italic">Tidak ada downtime mesin tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* BIAYA PEKERJAAN */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle className="text-base font-semibold">Biaya Pekerjaan</CardTitle>
                <div className="text-xs font-semibold text-foreground mt-0.5">
                  Total: {formatRupiah(perintahKerja.TotalBiaya ?? 0)}
                </div>
              </div>
              {dapatMengelola && <DialogCatatBiaya perintahKerja={perintahKerja} wajib={wajib.biaya} />}
            </CardHeader>
            <CardContent>
              {perintahKerja.Biaya && perintahKerja.Biaya.length > 0 ? (
                <ul className="divide-y divide-border text-xs">
                  {perintahKerja.Biaya.map((b) => (
                    <li key={b.Id} className="py-2 flex justify-between items-center">
                      <div>
                        <span className="font-medium block">{b.Deskripsi}</span>
                        <span className="text-muted-foreground">{b.JenisBiaya}</span>
                      </div>
                      <span className="font-medium">{formatRupiah(b.Jumlah)}</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-xs text-muted-foreground italic">Belum ada biaya tercatat.</p>
              )}
            </CardContent>
          </Card>

          {/* RIWAYAT STATUS */}
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base font-semibold">Riwayat Status</CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="space-y-4">
                {perintahKerja.RiwayatStatus?.map((riwayat) => (
                  <li key={riwayat.Id} className="relative border-l-2 border-border pl-4">
                    <div className="font-medium text-sm text-foreground">{riwayat.StatusSesudah}</div>
                    <div className="text-xs text-muted-foreground">
                      {formatTanggal(riwayat.DiubahPada)} · {riwayat.NamaPengubah ?? 'Sistem'}
                    </div>
                    {riwayat.Catatan && (
                      <p className="mt-1 text-xs text-muted-foreground">{riwayat.Catatan}</p>
                    )}
                  </li>
                ))}
              </ol>
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
