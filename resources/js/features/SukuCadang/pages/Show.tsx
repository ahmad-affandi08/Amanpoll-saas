import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { formatUang } from '@/lib/uang';
import type {
  KelompokSukuCadang,
  KompatibilitasSukuCadang,
  PemakaianSukuCadangBaris,
  ReservasiSukuCadangBaris,
  StokSukuCadangRingkas,
  SukuCadang,
} from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_SUKU_CADANG } from '@/features/Persediaan/status';
import { ruteSukuCadang } from '@/features/SukuCadang/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari } from '@/lib/pilihan';
import { PanelPemakaian, PanelReservasi, PanelStok } from '@/features/SukuCadang/components/PanelStok';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { InputUang } from '@/components/shared/InputUang';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface AsetRingkas {
  Id: string;
  Nama: string;
  KodeAset: string;
}

interface Props {
  sukuCadang: SukuCadang;
  stok: {
    baris: StokSukuCadangRingkas[];
    TotalTersedia: number;
    TotalDitahan: number;
    TotalBersih: number;
  };
  pemakaian: { total: number; data: PemakaianSukuCadangBaris[] };
  reservasi: ReservasiSukuCadangBaris[];
  kelompokSukuCadang: KelompokSukuCadang[];
  kompatibilitasSukuCadang: KompatibilitasSukuCadang[];
  kategoriAset: Ringkas[];
  modelAset: Ringkas[];
  aset: AsetRingkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogTambahKelompok({ sukuCadang, wajib }: { sukuCadang: SukuCadang; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ NomorBatch: '', TanggalProduksi: '', TanggalKadaluarsa: '', HargaPerolehan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteSukuCadang.kelompok(sukuCadang.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          Tambah Batch
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Kelompok/Batch</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="NomorBatch">Nomor Batch</Label>
              <Input
                value={form.data.NomorBatch}
                onChange={(e) => form.setData('NomorBatch', e.target.value)}
                className="font-mono"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <Label nama="TanggalProduksi">Tanggal Produksi</Label>
                <DatePicker
                  value={form.data.TanggalProduksi}
                  onChange={(val) => form.setData('TanggalProduksi', val)}
                  placeholder="Pilih tanggal..."
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="TanggalKadaluarsa">Tanggal Kadaluarsa</Label>
                <DatePicker
                  value={form.data.TanggalKadaluarsa}
                  onChange={(val) => form.setData('TanggalKadaluarsa', val)}
                  placeholder="Pilih tanggal..."
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="HargaPerolehan">Harga Perolehan</Label>
              <InputUang
                value={form.data.HargaPerolehan}
                onChange={(nilai) => form.setData('HargaPerolehan', nilai)}
              />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing || !form.data.NomorBatch}>
                Tambah
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

function DialogTambahKompatibilitas({
  sukuCadang,
  kategoriAset,
  modelAset,
  aset,
  wajib,
}: {
  sukuCadang: SukuCadang;
  kategoriAset: Ringkas[];
  modelAset: Ringkas[];
  aset: AsetRingkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Lingkup: 'aset',
    KategoriAsetId: TANPA_PILIHAN,
    ModelAsetId: TANPA_PILIHAN,
    AsetId: TANPA_PILIHAN,
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteSukuCadang.kompatibilitas,
      {
        SukuCadangId: sukuCadang.Id,
        KategoriAsetId:
          form.data.Lingkup === 'kategori' && form.data.KategoriAsetId !== TANPA_PILIHAN
            ? form.data.KategoriAsetId
            : null,
        ModelAsetId:
          form.data.Lingkup === 'model' && form.data.ModelAsetId !== TANPA_PILIHAN
            ? form.data.ModelAsetId
            : null,
        AsetId: form.data.Lingkup === 'aset' && form.data.AsetId !== TANPA_PILIHAN ? form.data.AsetId : null,
        Catatan: form.data.Catatan || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          Tambah Kompatibilitas
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Kompatibilitas</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label>Lingkup</Label>
              <Select value={form.data.Lingkup} onValueChange={(v) => form.setData('Lingkup', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="aset">Aset spesifik</SelectItem>
                  <SelectItem value="model">Model aset</SelectItem>
                  <SelectItem value="kategori">Kategori aset</SelectItem>
                </SelectContent>
              </Select>
            </div>
            {form.data.Lingkup === 'aset' && (
              <div className="space-y-1.5">
                <Label nama="AsetId">Aset</Label>
                <Combobox
                  nilai={form.data.AsetId}
                  onPilih={(v) => form.setData('AsetId', v)}
                  opsi={opsiDari(aset, (a) => `${a.Nama} (${a.KodeAset})`)}
                  placeholder="Pilih aset"
                />
              </div>
            )}
            {form.data.Lingkup === 'model' && (
              <div className="space-y-1.5">
                <Label nama="ModelAsetId">Model Aset</Label>
                <Combobox
                  nilai={form.data.ModelAsetId}
                  onPilih={(v) => form.setData('ModelAsetId', v)}
                  opsi={opsiDari(modelAset, (m) => m.Nama)}
                  placeholder="Pilih model aset"
                />
              </div>
            )}
            {form.data.Lingkup === 'kategori' && (
              <div className="space-y-1.5">
                <Label nama="KategoriAsetId">Kategori Aset</Label>
                <Combobox
                  nilai={form.data.KategoriAsetId}
                  onPilih={(v) => form.setData('KategoriAsetId', v)}
                  opsi={opsiDari(kategoriAset, (k) => k.Nama)}
                  placeholder="Pilih kategori aset"
                />
              </div>
            )}
            <div className="space-y-1.5">
              <Label nama="Catatan">Catatan</Label>
              <Input value={form.data.Catatan} onChange={(e) => form.setData('Catatan', e.target.value)} />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Tambah
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function SukuCadangShow({
  sukuCadang,
  stok,
  pemakaian,
  reservasi,
  kelompokSukuCadang,
  kompatibilitasSukuCadang,
  kategoriAset,
  modelAset,
  aset,
  wajib,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const hapusKelompok = async (item: KelompokSukuCadang) => {
    if (
      !(await konfirmasi({
        judul: `Hapus batch "${item.NomorBatch}"?`,
        deskripsi: 'Riwayat stok yang mengacu ke batch ini tidak ikut terhapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteSukuCadang.kelompokDetail(item.Id), { preserveScroll: true });
  };

  const hapusKompatibilitas = async (item: KompatibilitasSukuCadang) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus kompatibilitas ini?',
        deskripsi: 'Suku cadang tidak lagi muncul sebagai pilihan untuk aset tersebut.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteSukuCadang.kompatibilitasDetail(item.Id), { preserveScroll: true });
  };

  return (
    <KerangkaAplikasi>
      <Head title={sukuCadang.Nama} />
      <div className="space-y-5">
        <KepalaHalaman
          judul={sukuCadang.Nama}
          labelBreadcrumb={sukuCadang.Kode}
          lencana={
            <Badge variant={VARIAN_BADGE_STATUS_SUKU_CADANG[sukuCadang.Status]}>{sukuCadang.Status}</Badge>
          }
          deskripsi={
            <>
              <span className="font-mono">{sukuCadang.Kode}</span> ·{' '}
              {sukuCadang.NamaKategori ?? 'Tanpa kategori'} · Satuan {sukuCadang.SatuanDasar}
            </>
          }
        />

        <dl className="grid gap-4 rounded-md border border-border bg-card px-5 py-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <dt className="text-[13px] text-grafit-700">Stok Minimum</dt>
            <dd className="text-sm font-medium text-foreground">
              {sukuCadang.StokMinimum} {sukuCadang.SatuanDasar}
            </dd>
          </div>
          <div>
            <dt className="text-[13px] text-grafit-700">Titik Pesan Ulang</dt>
            <dd className="text-sm font-medium text-foreground">{sukuCadang.TitikPesanUlang ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-[13px] text-grafit-700">Harga Rata-rata</dt>
            <dd className="text-sm font-medium text-foreground">{formatUang(sukuCadang.HargaRataRata)}</dd>
          </div>
          <div>
            <dt className="text-[13px] text-grafit-700">Nomor Bagian</dt>
            <dd className="text-sm font-medium text-foreground">{sukuCadang.NomorBagian ?? '—'}</dd>
          </div>
        </dl>

        <PanelStok
          stok={stok}
          satuan={sukuCadang.SatuanDasar}
          stokMinimum={parseFloat(sukuCadang.StokMinimum)}
        />

        <PanelReservasi reservasi={reservasi} satuan={sukuCadang.SatuanDasar} />

        <PanelPemakaian pemakaian={pemakaian} satuan={sukuCadang.SatuanDasar} />

        <div className="rounded-md border border-border bg-card px-5 py-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Kelompok/Batch</h2>
            <DialogTambahKelompok sukuCadang={sukuCadang} wajib={wajib.kelompok} />
          </div>
          {kelompokSukuCadang.length === 0 ? (
            <KeadaanKosong
              judul="Belum ada batch."
              deskripsi="Tambahkan batch bila suku cadang ini dilacak per kelompok/kadaluarsa."
            />
          ) : (
            <div className="space-y-2">
              {kelompokSukuCadang.map((k) => (
                <div
                  key={k.Id}
                  className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                >
                  <div>
                    <span className="font-mono font-medium text-foreground">{k.NomorBatch}</span>
                    {k.TanggalKadaluarsa && (
                      <span className="ml-2 text-xs text-muted-foreground">
                        Kadaluarsa: {new Date(k.TanggalKadaluarsa).toLocaleDateString('id-ID')}
                      </span>
                    )}
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapusKelompok(k)}>
                    Hapus
                  </Button>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="rounded-md border border-border bg-card px-5 py-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Kompatibilitas dengan Aset</h2>
            <DialogTambahKompatibilitas
              sukuCadang={sukuCadang}
              kategoriAset={kategoriAset}
              modelAset={modelAset}
              aset={aset}
              wajib={wajib.kompatibilitas}
            />
          </div>
          {kompatibilitasSukuCadang.length === 0 ? (
            <KeadaanKosong
              judul="Belum ada kompatibilitas."
              deskripsi="Tambahkan aset, model aset, atau kategori aset yang cocok dengan suku cadang ini."
            />
          ) : (
            <div className="space-y-2">
              {kompatibilitasSukuCadang.map((k) => (
                <div
                  key={k.Id}
                  className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                >
                  <div>
                    <span className="font-medium text-foreground">
                      {k.NamaAset ?? k.NamaModelAset ?? k.NamaKategoriAset}
                    </span>
                    <Badge variant="netral" className="ml-2">
                      {k.AsetId ? 'Aset' : k.ModelAsetId ? 'Model' : 'Kategori'}
                    </Badge>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapusKompatibilitas(k)}>
                    Hapus
                  </Button>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
