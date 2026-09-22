import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Send, Truck } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PesananPembelian } from '@/features/PesananPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import type { GudangRingkas } from '@/features/PesananPembelian/types';
import { hitungSisa } from '@/features/PesananPembelian/perhitungan';
import { DialogCatatPenerimaan } from '@/features/PesananPembelian/components/DialogCatatPenerimaan';
import { DialogCatatTagihan } from '@/features/PesananPembelian/components/DialogCatatTagihan';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  pesanan: PesananPembelian;
  gudang: GudangRingkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'info',
  Ditolak: 'bahaya',
  Dikirim: 'proses',
  DiterimaSebagian: 'perhatian',
  DiterimaPenuh: 'sukses',
} as const;
const VARIAN_TAGIHAN = { BelumDibayar: 'perhatian', DibayarSebagian: 'proses', Dibayar: 'sukses' } as const;

export default function PesananPembelianShow(props: Props) {
  const { pesanan, gudang, wajib } = props;
  const [memproses, setMemproses] = useState(false);
  const detail = pesanan.Detail ?? [];
  const penerimaan = pesanan.Penerimaan ?? [];
  const tagihan = pesanan.Tagihan ?? [];
  const bolehTerima = pesanan.Status === 'Dikirim' || pesanan.Status === 'DiterimaSebagian';
  const bolehTagih = bolehTerima || pesanan.Status === 'DiterimaPenuh';

  function jalankanAksi(url: string): void {
    router.post(
      url,
      {},
      {
        preserveScroll: true,
        onStart: () => setMemproses(true),
        onFinish: () => setMemproses(false),
      },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title={pesanan.Nomor} />
      <div className="space-y-6">
        <Link
          href={rutePesananPembelian.index}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="size-4" /> Kembali
        </Link>

        <KepalaHalaman
          judul={<span className="font-mono">{pesanan.Nomor}</span>}
          labelBreadcrumb={pesanan.Nomor}
          lencana={<Badge variant={VARIAN_STATUS[pesanan.Status]}>{pesanan.Status}</Badge>}
          deskripsi={
            <>
              {pesanan.NamaPenyedia} · sumber {pesanan.NomorPermintaanPembelian ?? '-'} ·{' '}
              {pesanan.NamaPosAnggaran ?? 'Tanpa pos anggaran'}
            </>
          }
          aksi={
            <>
              {pesanan.Status === 'Draft' && (
                <Button
                  size="sm"
                  className="min-h-11 sm:min-h-9"
                  disabled={memproses}
                  onClick={() => jalankanAksi(rutePesananPembelian.ajukan(pesanan.Id))}
                >
                  <Send /> Ajukan Persetujuan
                </Button>
              )}
              {pesanan.Status === 'Disetujui' && (
                <Button
                  size="sm"
                  className="min-h-11 sm:min-h-9"
                  disabled={memproses}
                  onClick={() => jalankanAksi(rutePesananPembelian.kirim(pesanan.Id))}
                >
                  <Truck /> Kirim ke Penyedia
                </Button>
              )}
              {bolehTerima && <DialogCatatPenerimaan {...props} wajib={wajib.penerimaan} />}
              {bolehTagih && <DialogCatatTagihan pesanan={pesanan} wajib={wajib.tagihan} />}
            </>
          }
        />

        <Card>
          <CardHeader>
            <CardTitle>Item Pesanan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {detail.map((item) => (
              <div
                key={item.Id}
                className="grid gap-2 rounded-[9px] border border-border p-3 sm:grid-cols-[1fr_auto] sm:items-center"
              >
                <div className="min-w-0">
                  <p className="font-medium">{item.Deskripsi}</p>
                  <p className="text-xs text-muted-foreground">
                    {item.JenisItem} · {item.Jumlah} {item.Satuan} @ {formatUang(item.HargaSatuan)} · sisa{' '}
                    {hitungSisa(pesanan, item)}
                  </p>
                </div>
                <p className="font-mono font-medium">{formatUang(item.Total)}</p>
              </div>
            ))}
            <div className="flex items-center justify-between border-t border-border pt-3">
              <span className="text-sm text-muted-foreground">Total PO (dihitung server)</span>
              <strong className="font-mono text-lg">{formatUang(pesanan.Total)}</strong>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Penerimaan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {penerimaan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada penerimaan."
                deskripsi="Penerimaan dapat dicatat setelah PO dikirim ke penyedia."
              />
            ) : (
              penerimaan.map((dokumen) => (
                <div key={dokumen.Id} className="space-y-2 rounded-[9px] border border-border p-3">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="font-mono text-sm font-medium">{dokumen.Nomor}</span>
                    <span className="text-xs text-muted-foreground">
                      {dokumen.NamaGudang ?? 'Tanpa gudang'} · {dokumen.NamaPenerima ?? '-'}
                    </span>
                  </div>
                  <ul className="space-y-1 text-xs text-muted-foreground">
                    {(dokumen.Detail ?? []).map((baris) => (
                      <li key={baris.Id}>
                        {baris.Deskripsi ?? 'Item'} — diterima {baris.JumlahDiterima}, ditolak{' '}
                        {baris.JumlahDitolak} ({baris.Kondisi ?? '-'})
                      </li>
                    ))}
                  </ul>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Tagihan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {tagihan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada tagihan."
                deskripsi="Tagihan hanya dapat dicatat setelah ada barang yang diterima."
              />
            ) : (
              tagihan.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteTagihanPenyedia.detail(item.Id)}
                  className="flex flex-col gap-2 rounded-[9px] border border-border p-3 transition hover:border-primary/40 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="font-mono font-medium">{item.NomorTagihan}</p>
                    <p className="text-xs text-muted-foreground">Sisa {formatUang(item.Sisa)}</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <strong className="font-mono">{formatUang(item.Total)}</strong>
                    <Badge variant={VARIAN_TAGIHAN[item.Status]}>{item.Status}</Badge>
                  </div>
                </Link>
              ))
            )}
          </CardContent>
        </Card>

        {gudang.length === 0 && bolehTerima && (
          <p className="text-sm text-muted-foreground">
            Belum ada gudang aktif; penerimaan item suku cadang akan ditolak sampai gudang dibuat.
          </p>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
