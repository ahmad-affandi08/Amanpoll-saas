import { type FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, PackagePlus, Send, Truck } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { KondisiPenerimaan } from '@/features/PenerimaanPembelian/types';
import type { DetailPesananPembelian, PesananPembelian } from '@/features/PesananPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { ruteTagihanPenyedia } from '@/features/TagihanPenyedia/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN } from '@/lib/pilihan';

interface GudangRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface Props {
  pesanan: PesananPembelian;
  gudang: GudangRingkas[];
}

interface BarisPenerimaan {
  DetailPesananPembelianId: string;
  JumlahDiterima: string;
  JumlahDitolak: string;
  Kondisi: KondisiPenerimaan;
  NomorSeri: string;
  Catatan: string;
}

const KONDISI: KondisiPenerimaan[] = ['Baik', 'RusakRingan', 'Rusak'];
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

/** Sisa yang belum diterima per baris PO, dihitung dari seluruh dokumen penerimaan. */
function hitungSisa(pesanan: PesananPembelian, detail: DetailPesananPembelian): number {
  const diterima = (pesanan.Penerimaan ?? []).reduce((jumlah, dokumen) => {
    const baris = (dokumen.Detail ?? []).filter((item) => item.DetailPesananPembelianId === detail.Id);
    return jumlah + baris.reduce((sub, item) => sub + Number(item.JumlahDiterima), 0);
  }, 0);

  return Math.max(Number(detail.Jumlah) - diterima, 0);
}

function DialogCatatPenerimaan({ pesanan, gudang }: Props) {
  const [buka, setBuka] = useState(false);
  const detail = pesanan.Detail ?? [];
  const perluGudang = detail.some((item) => item.JenisItem === 'SukuCadang');
  const form = useForm({
    Nomor: '',
    GudangId: perluGudang && gudang.length > 0 ? gudang[0].Id : TANPA_PILIHAN,
    TanggalTerima: new Date().toISOString().slice(0, 10),
    NomorSuratJalan: '',
    Catatan: '',
    Detail: detail.map((item): BarisPenerimaan => ({
      DetailPesananPembelianId: item.Id,
      JumlahDiterima: hitungSisa(pesanan, item).toString(),
      JumlahDitolak: '0',
      Kondisi: 'Baik',
      NomorSeri: '',
      Catatan: '',
    })),
  });

  function ubahBaris(indeks: number, kolom: keyof BarisPenerimaan, nilai: string): void {
    const baris = [...form.data.Detail];
    baris[indeks] = { ...baris[indeks], [kolom]: nilai };
    form.setData('Detail', baris);
  }

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Nomor: data.Nomor || null,
      GudangId: data.GudangId === TANPA_PILIHAN ? null : data.GudangId,
      NomorSuratJalan: data.NomorSuratJalan || null,
      Catatan: data.Catatan || null,
      Detail: data.Detail.filter((baris) => Number(baris.JumlahDiterima) > 0).map((baris) => ({
        ...baris,
        Catatan: baris.Catatan || null,
        NomorSeri: baris.NomorSeri.split(',')
          .map((nilai) => nilai.trim())
          .filter((nilai) => nilai !== ''),
      })),
    }));
    form.post(rutePesananPembelian.penerimaan(pesanan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" className="min-h-11 sm:min-h-9">
          <PackagePlus /> Catat Penerimaan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Penerimaan Barang</DialogTitle>
          <DialogDescription>
            Penerimaan sebagian diperbolehkan. Item aset wajib mencantumkan satu nomor seri per unit, dipisah
            koma.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label htmlFor="TanggalTerima">Tanggal Terima</Label>
              <Input
                id="TanggalTerima"
                type="date"
                value={form.data.TanggalTerima}
                onChange={(event) => form.setData('TanggalTerima', event.target.value)}
              />
              {form.errors.TanggalTerima && (
                <p className="text-sm text-destructive">{form.errors.TanggalTerima}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="NomorSuratJalan">Nomor Surat Jalan</Label>
              <Input
                id="NomorSuratJalan"
                value={form.data.NomorSuratJalan}
                onChange={(event) => form.setData('NomorSuratJalan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Gudang</Label>
              <Select value={form.data.GudangId} onValueChange={(value) => form.setData('GudangId', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Tanpa gudang</SelectItem>
                  {gudang.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Kode} — {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {perluGudang && form.data.GudangId === TANPA_PILIHAN && (
                <p className="text-sm text-destructive">Item suku cadang wajib memilih gudang tujuan.</p>
              )}
            </div>
          </div>

          <div className="space-y-3">
            {detail.map((item, indeks) => (
              <div key={item.Id} className="space-y-3 rounded-[9px] border border-border p-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="min-w-0">
                    <p className="text-sm font-medium">{item.Deskripsi}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.JenisItem} · dipesan {item.Jumlah} {item.Satuan} · sisa{' '}
                      {hitungSisa(pesanan, item)}
                    </p>
                  </div>
                  <Badge variant="netral">{item.JenisItem}</Badge>
                </div>
                <div className="grid gap-3 sm:grid-cols-[repeat(3,minmax(0,1fr))]">
                  <div className="space-y-1">
                    <Label className="text-xs">Diterima</Label>
                    <Input
                      type="number"
                      min="0"
                      step="0.0001"
                      value={form.data.Detail[indeks].JumlahDiterima}
                      onChange={(event) => ubahBaris(indeks, 'JumlahDiterima', event.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label className="text-xs">Ditolak</Label>
                    <Input
                      type="number"
                      min="0"
                      step="0.0001"
                      value={form.data.Detail[indeks].JumlahDitolak}
                      onChange={(event) => ubahBaris(indeks, 'JumlahDitolak', event.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label className="text-xs">Kondisi</Label>
                    <Select
                      value={form.data.Detail[indeks].Kondisi}
                      onValueChange={(value) => ubahBaris(indeks, 'Kondisi', value)}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {KONDISI.map((nilai) => (
                          <SelectItem key={nilai} value={nilai}>
                            {nilai}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                {item.JenisItem === 'Aset' && (
                  <div className="space-y-1">
                    <Label className="text-xs">Nomor Seri (pisahkan dengan koma)</Label>
                    <Input
                      value={form.data.Detail[indeks].NomorSeri}
                      onChange={(event) => ubahBaris(indeks, 'NomorSeri', event.target.value)}
                    />
                  </div>
                )}
              </div>
            ))}
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Penerimaan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogCatatTagihan({ pesanan }: { pesanan: PesananPembelian }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    NomorTagihan: '',
    TanggalTagihan: new Date().toISOString().slice(0, 10),
    JatuhTempo: '',
    Subtotal: '0',
    Pajak: '0',
  });
  const total = useMemo(
    () => Number(form.data.Subtotal) + Number(form.data.Pajak),
    [form.data.Subtotal, form.data.Pajak],
  );

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, JatuhTempo: data.JatuhTempo || null }));
    form.post(ruteTagihanPenyedia.simpanDariPesanan(pesanan.Id), { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline" className="min-h-11 sm:min-h-9">
          <FileText /> Catat Tagihan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Tagihan Penyedia</DialogTitle>
          <DialogDescription>
            Server menolak tagihan yang melebihi nilai barang yang sudah diterima pada PO ini.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="NomorTagihan">Nomor Tagihan</Label>
            <Input
              id="NomorTagihan"
              value={form.data.NomorTagihan}
              onChange={(event) => form.setData('NomorTagihan', event.target.value)}
            />
            {form.errors.NomorTagihan && (
              <p className="text-sm text-destructive">{form.errors.NomorTagihan}</p>
            )}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="TanggalTagihan">Tanggal Tagihan</Label>
              <Input
                id="TanggalTagihan"
                type="date"
                value={form.data.TanggalTagihan}
                onChange={(event) => form.setData('TanggalTagihan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="JatuhTempo">Jatuh Tempo</Label>
              <Input
                id="JatuhTempo"
                type="date"
                value={form.data.JatuhTempo}
                onChange={(event) => form.setData('JatuhTempo', event.target.value)}
              />
              {form.errors.JatuhTempo && <p className="text-sm text-destructive">{form.errors.JatuhTempo}</p>}
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="Subtotal">Subtotal</Label>
              <Input
                id="Subtotal"
                type="number"
                min="0"
                step="0.01"
                value={form.data.Subtotal}
                onChange={(event) => form.setData('Subtotal', event.target.value)}
              />
              {form.errors.Subtotal && <p className="text-sm text-destructive">{form.errors.Subtotal}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="Pajak">Pajak</Label>
              <Input
                id="Pajak"
                type="number"
                min="0"
                step="0.01"
                value={form.data.Pajak}
                onChange={(event) => form.setData('Pajak', event.target.value)}
              />
            </div>
          </div>
          <DialogFooter className="items-center gap-3 sm:justify-between">
            <span className="font-mono text-sm">Total {formatUang(total)}</span>
            <Button type="submit" disabled={form.processing}>
              Simpan Tagihan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PesananPembelianShow(props: Props) {
  const { pesanan, gudang } = props;
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
              {bolehTerima && <DialogCatatPenerimaan {...props} />}
              {bolehTagih && <DialogCatatTagihan pesanan={pesanan} />}
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
