import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Clock, CreditCard, Lock } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
  angka,
  labelBatas,
  persenPemakaian,
  rupiah,
  tanggal,
  ukuranBerkas,
} from '@/features/Langganan/format';
import type { PageProps } from '@/types/global';
import type {
  DefinisiFitur,
  Entitlement,
  InstruksiPembayaran,
  MetodePembayaran,
  PembayaranKembali,
  RingkasanPenyimpanan,
  StatusLangganan,
  TagihanItem,
} from '@/features/Langganan/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { ruteLangganan } from '@/features/Langganan/api';

interface Props {
  entitlement: Entitlement;
  pemakaian: Record<string, number>;
  penyimpanan: RingkasanPenyimpanan;
  katalogFitur: DefinisiFitur[];
  tagihan: TagihanItem[];
  metodePembayaran: MetodePembayaran[];
  pembayaranKembali: PembayaranKembali | null;
}

/** Status dipetakan ke nada visual; warnanya selalu didampingi ikon dan teks. */
const NADA_STATUS: Record<
  StatusLangganan,
  { varian: 'default' | 'secondary' | 'destructive'; catatan: string }
> = {
  UjiCoba: { varian: 'secondary', catatan: 'Masa uji coba sedang berjalan.' },
  Aktif: { varian: 'default', catatan: 'Langganan berjalan normal.' },
  Tenggang: {
    varian: 'secondary',
    catatan: 'Masa tenggang. Perubahan data masih dapat disimpan, tetapi segera lunasi tagihan.',
  },
  Kedaluwarsa: {
    varian: 'destructive',
    catatan: 'Langganan kedaluwarsa. Data tetap dapat dibaca, tetapi perubahan baru ditolak.',
  },
  Dibatalkan: {
    varian: 'destructive',
    catatan:
      'Langganan dibatalkan. Data tetap dapat dibaca; hubungi administrator untuk mengaktifkan kembali.',
  },
};

export default function LanggananIndex({
  entitlement,
  pemakaian,
  penyimpanan,
  katalogFitur,
  tagihan,
  metodePembayaran,
  pembayaranKembali,
}: Props) {
  const { flash, errors } = usePage<PageProps>().props;
  const [penyedia, setPenyedia] = useState(metodePembayaran[0]?.Kode ?? '');
  const instruksi = flash.instruksiPembayaran as InstruksiPembayaran | undefined;

  const nada = NADA_STATUS[entitlement.Status];
  const fiturModul = katalogFitur.filter((f) => f.TipeBatas === 'Boolean');
  const fiturBatas = katalogFitur.filter((f) => f.TipeBatas === 'Angka');

  return (
    <KerangkaAplikasi>
      <Head title="Langganan" />

      <div className="space-y-5">
        <KepalaHalaman
          judul="Langganan"
          deskripsi="Paket yang sedang berjalan, pemakaian terhadap batasnya, dan riwayat tagihan organisasi Anda."
          aksi={<TombolEkspor url={ruteLangganan.ekspor} label="Ekspor Tagihan" />}
        />

        {!entitlement.AksesPenuh && (
          <div
            role="status"
            className="flex items-start gap-3 rounded-md border border-destructive/40 bg-destructive/5 p-4"
          >
            <Lock aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-destructive" />
            <div className="space-y-0.5 text-sm">
              <p className="font-medium text-foreground">Perubahan data sedang ditolak</p>
              <p className="text-muted-foreground">{nada.catatan}</p>
            </div>
          </div>
        )}

        {pembayaranKembali && <KartuPembayaranKembali pembayaran={pembayaranKembali} />}

        {instruksi && <KartuInstruksi instruksi={instruksi} />}

        <Card>
          <CardHeader className="flex flex-row items-center justify-between gap-3">
            <CardTitle>Paket berjalan</CardTitle>
            <Badge variant={nada.varian}>{entitlement.LabelStatus}</Badge>
          </CardHeader>
          <CardContent className="space-y-4">
            {entitlement.PaketId === null ? (
              <KeadaanKosong
                judul="Organisasi ini belum berlangganan."
                deskripsi="Hubungi administrator platform untuk mengaktifkan paket."
              />
            ) : (
              <>
                <dl className="grid gap-4 sm:grid-cols-3">
                  <Rincian label="Nama paket" nilai={entitlement.NamaPaket ?? '—'} />
                  <Rincian label="Berlaku sampai" nilai={tanggal(entitlement.BerakhirPada)} />
                  <Rincian
                    label="Uji coba sampai"
                    nilai={entitlement.UjiCobaSampai ? tanggal(entitlement.UjiCobaSampai) : '—'}
                  />
                </dl>
                <p className="text-sm text-muted-foreground">{nada.catatan}</p>
              </>
            )}
          </CardContent>
        </Card>

        <div className="grid gap-5 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Modul yang termasuk</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {fiturModul.map((fitur) => {
                const aktif = entitlement.Fitur[fitur.Kode] ?? false;

                return (
                  <div key={fitur.Kode} className="flex items-start gap-3">
                    {aktif ? (
                      <CheckCircle2 aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-sukses-700" />
                    ) : (
                      <Lock aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    )}
                    <div className="min-w-0 space-y-0.5">
                      <p className="text-sm font-medium text-foreground">
                        {fitur.Nama}{' '}
                        <span className="font-normal text-muted-foreground">
                          — {aktif ? 'termasuk' : 'tidak termasuk'}
                        </span>
                      </p>
                      <p className="text-xs text-muted-foreground">{fitur.Deskripsi}</p>
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Pemakaian terhadap batas</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {fiturBatas.map((fitur) => {
                const batas = entitlement.Batas[fitur.Kode] ?? null;
                const terpakai = pemakaian[fitur.Kode] ?? 0;
                const persen = persenPemakaian(terpakai, batas);

                return (
                  <div key={fitur.Kode} className="space-y-1.5">
                    <div className="flex items-baseline justify-between gap-3 text-sm">
                      <span className="font-medium text-foreground">{fitur.Nama}</span>
                      <span className="tabular-nums text-muted-foreground">
                        {angka(terpakai)} / {labelBatas(batas, fitur.SatuanBatas)}
                      </span>
                    </div>
                    {persen !== null && (
                      <div
                        className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                        role="img"
                        aria-label={`${fitur.Nama}: ${persen} persen terpakai`}
                      >
                        <div
                          className={persen >= 100 ? 'h-full bg-destructive' : 'h-full bg-primary'}
                          style={{ width: `${persen}%` }}
                        />
                      </div>
                    )}
                    {persen !== null && persen >= 100 && (
                      <p className="flex items-center gap-1.5 text-xs text-destructive">
                        <AlertTriangle aria-hidden="true" className="size-3.5" />
                        Kuota habis. Penambahan baru akan ditolak.
                      </p>
                    )}
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Penyimpanan berkas</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <dl className="grid gap-4 sm:grid-cols-3">
              <Rincian label="Tersimpan di server" nilai={ukuranBerkas(penyimpanan.UkuranTersimpanByte)} />
              <Rincian
                label={`Ukuran asli (${angka(penyimpanan.JumlahBerkas)} berkas)`}
                nilai={ukuranBerkas(penyimpanan.UkuranAsliByte)}
              />
              <Rincian
                label="Hemat berkat kompresi"
                nilai={`${penyimpanan.PersenHemat.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`}
              />
            </dl>
            <p className="text-sm text-muted-foreground">
              Gambar disimpan sebagai WebP, sedangkan CSV dan PDF dipadatkan otomatis. Berkas yang diunduh
              tetap utuh seperti aslinya. Berkas identik hanya disimpan satu kali.
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
            <CardTitle>Tagihan</CardTitle>
            {metodePembayaran.length > 1 && (
              <div className="flex items-center gap-2">
                <Label htmlFor="metode-pembayaran" className="text-sm text-muted-foreground">
                  Metode bayar
                </Label>
                <Select value={penyedia} onValueChange={setPenyedia}>
                  <SelectTrigger id="metode-pembayaran" className="w-56">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {metodePembayaran.map((metode) => (
                      <SelectItem key={metode.Kode} value={metode.Kode}>
                        {metode.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          </CardHeader>
          <CardContent className="space-y-3">
            {errors.Penyedia && <p className="text-sm text-destructive">{errors.Penyedia}</p>}
            {tagihan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada tagihan."
                deskripsi="Tagihan akan muncul di sini setelah periode berlangganan diterbitkan."
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nomor</TableHead>
                    <TableHead>Periode</TableHead>
                    <TableHead>Jatuh tempo</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="w-0" />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tagihan.map((baris) => (
                    <TableRow key={baris.Id}>
                      <TableCell className="font-medium">{baris.Nomor}</TableCell>
                      <TableCell className="text-muted-foreground">
                        {tanggal(baris.PeriodeMulai)} – {tanggal(baris.PeriodeSelesai)}
                      </TableCell>
                      <TableCell className="text-muted-foreground">{tanggal(baris.JatuhTempo)}</TableCell>
                      <TableCell className="text-right tabular-nums">{rupiah(baris.Total)}</TableCell>
                      <TableCell>
                        <Badge variant={baris.Status === 'Lunas' ? 'default' : 'secondary'}>
                          {baris.LabelStatus}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {baris.DapatDibayar && (
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() =>
                              router.post(
                                ruteLangganan.tagihanBayar(baris.Id),
                                { Penyedia: penyedia },
                                { preserveScroll: true },
                              )
                            }
                          >
                            <CreditCard aria-hidden="true" className="size-4" />
                            Bayar
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </KerangkaAplikasi>
  );
}

function Rincian({ label, nilai }: { label: string; nilai: string }) {
  return (
    <div className="space-y-0.5">
      <dt className="text-xs font-medium text-grafit-500">{label}</dt>
      <dd className="text-sm font-medium text-foreground">{nilai}</dd>
    </div>
  );
}

function KartuPembayaranKembali({ pembayaran }: { pembayaran: PembayaranKembali }) {
  return (
    <div
      role="status"
      className="flex items-start gap-3 rounded-md border border-border bg-permukaan-100 p-4"
    >
      {pembayaran.Lunas ? (
        <CheckCircle2 aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-sukses-700" />
      ) : (
        <Clock aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
      )}
      <div className="flex-1 space-y-0.5 text-sm">
        <p className="font-medium text-foreground">
          {pembayaran.Lunas
            ? `Pembayaran tagihan ${pembayaran.Nomor} sudah diterima.`
            : `Pembayaran tagihan ${pembayaran.Nomor} menunggu konfirmasi.`}
        </p>
        <p className="text-muted-foreground">
          {pembayaran.Lunas
            ? 'Langganan diperpanjang otomatis.'
            : 'Status diperbarui setelah penyedia pembayaran mengirim konfirmasi. Muat ulang halaman ini beberapa saat lagi.'}
        </p>
      </div>
      {!pembayaran.Lunas && (
        <Button size="sm" variant="outline" onClick={() => router.reload()}>
          Muat ulang
        </Button>
      )}
    </div>
  );
}

function KartuInstruksi({ instruksi }: { instruksi: InstruksiPembayaran }) {
  const baris = Object.entries(instruksi.Instruksi).filter(([kunci]) => kunci !== 'Jenis');

  return (
    <Card>
      <CardHeader>
        <CardTitle>Instruksi pembayaran — {instruksi.NomorTagihan}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="mb-3 text-sm text-muted-foreground">Metode: {instruksi.Penyedia}</p>
        <dl className="grid gap-3 sm:grid-cols-2">
          {baris.map(([kunci, nilai]) => (
            <div key={kunci} className="space-y-0.5">
              <dt className="text-xs font-medium text-grafit-500">{kunci}</dt>
              <dd className="text-sm font-medium text-foreground">{String(nilai ?? '—')}</dd>
            </div>
          ))}
        </dl>
      </CardContent>
    </Card>
  );
}
