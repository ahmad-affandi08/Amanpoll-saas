import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatUang } from '@/lib/uang';
import type { KelompokSukuCadang, KompatibilitasSukuCadang, SukuCadang } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_SUKU_CADANG } from '@/features/Persediaan/status';

interface Ringkas { Id: string; Nama: string }
interface AsetRingkas { Id: string; Nama: string; KodeAset: string }

interface Props {
  sukuCadang: SukuCadang;
  kelompokSukuCadang: KelompokSukuCadang[];
  kompatibilitasSukuCadang: KompatibilitasSukuCadang[];
  kategoriAset: Ringkas[];
  modelAset: Ringkas[];
  aset: AsetRingkas[];
}

const TANPA = '__tanpa__';

function DialogTambahKelompok({ sukuCadang }: { sukuCadang: SukuCadang }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ NomorBatch: '', TanggalProduksi: '', TanggalKadaluarsa: '', HargaPerolehan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`/suku-cadang/${sukuCadang.Id}/kelompok`, form.data, { preserveScroll: true, onSuccess: () => { setBuka(false); form.reset(); } });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">Tambah Batch</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Tambah Kelompok/Batch</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Nomor Batch</Label>
            <Input value={form.data.NomorBatch} onChange={(e) => form.setData('NomorBatch', e.target.value)} className="font-mono" />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Tanggal Produksi</Label>
              <Input type="date" value={form.data.TanggalProduksi} onChange={(e) => form.setData('TanggalProduksi', e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label>Tanggal Kadaluarsa</Label>
              <Input type="date" value={form.data.TanggalKadaluarsa} onChange={(e) => form.setData('TanggalKadaluarsa', e.target.value)} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Harga Perolehan</Label>
            <Input type="number" min={0} value={form.data.HargaPerolehan} onChange={(e) => form.setData('HargaPerolehan', e.target.value)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing || !form.data.NomorBatch}>Tambah</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogTambahKompatibilitas({ sukuCadang, kategoriAset, modelAset, aset }: { sukuCadang: SukuCadang; kategoriAset: Ringkas[]; modelAset: Ringkas[]; aset: AsetRingkas[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Lingkup: 'aset', KategoriAsetId: TANPA, ModelAsetId: TANPA, AsetId: TANPA, Catatan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post('/kompatibilitas-suku-cadang', {
      SukuCadangId: sukuCadang.Id,
      KategoriAsetId: form.data.Lingkup === 'kategori' && form.data.KategoriAsetId !== TANPA ? form.data.KategoriAsetId : null,
      ModelAsetId: form.data.Lingkup === 'model' && form.data.ModelAsetId !== TANPA ? form.data.ModelAsetId : null,
      AsetId: form.data.Lingkup === 'aset' && form.data.AsetId !== TANPA ? form.data.AsetId : null,
      Catatan: form.data.Catatan || null,
    }, { preserveScroll: true, onSuccess: () => { setBuka(false); form.reset(); } });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">Tambah Kompatibilitas</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Tambah Kompatibilitas</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Lingkup</Label>
            <Select value={form.data.Lingkup} onValueChange={(v) => form.setData('Lingkup', v)}>
              <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="aset">Aset spesifik</SelectItem>
                <SelectItem value="model">Model aset</SelectItem>
                <SelectItem value="kategori">Kategori aset</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {form.data.Lingkup === 'aset' && (
            <div className="space-y-1.5">
              <Label>Aset</Label>
              <Select value={form.data.AsetId} onValueChange={(v) => form.setData('AsetId', v)}>
                <SelectTrigger className="w-full"><SelectValue placeholder="Pilih aset" /></SelectTrigger>
                <SelectContent>
                  {aset.map((a) => <SelectItem key={a.Id} value={a.Id}>{a.Nama} ({a.KodeAset})</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          )}
          {form.data.Lingkup === 'model' && (
            <div className="space-y-1.5">
              <Label>Model Aset</Label>
              <Select value={form.data.ModelAsetId} onValueChange={(v) => form.setData('ModelAsetId', v)}>
                <SelectTrigger className="w-full"><SelectValue placeholder="Pilih model aset" /></SelectTrigger>
                <SelectContent>
                  {modelAset.map((m) => <SelectItem key={m.Id} value={m.Id}>{m.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          )}
          {form.data.Lingkup === 'kategori' && (
            <div className="space-y-1.5">
              <Label>Kategori Aset</Label>
              <Select value={form.data.KategoriAsetId} onValueChange={(v) => form.setData('KategoriAsetId', v)}>
                <SelectTrigger className="w-full"><SelectValue placeholder="Pilih kategori aset" /></SelectTrigger>
                <SelectContent>
                  {kategoriAset.map((k) => <SelectItem key={k.Id} value={k.Id}>{k.Nama}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          )}
          <div className="space-y-1.5">
            <Label>Catatan</Label>
            <Input value={form.data.Catatan} onChange={(e) => form.setData('Catatan', e.target.value)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Tambah</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function SukuCadangShow({ sukuCadang, kelompokSukuCadang, kompatibilitasSukuCadang, kategoriAset, modelAset, aset }: Props) {
  const hapusKelompok = (item: KelompokSukuCadang) => {
    if (!confirm(`Hapus batch "${item.NomorBatch}"?`)) return;
    router.delete(`/kelompok-suku-cadang/${item.Id}`, { preserveScroll: true });
  };

  const hapusKompatibilitas = (item: KompatibilitasSukuCadang) => {
    if (!confirm('Hapus kompatibilitas ini?')) return;
    router.delete(`/kompatibilitas-suku-cadang/${item.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title={sukuCadang.Nama} />
      <div className="space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="font-mono text-sm text-muted-foreground">{sukuCadang.Kode}</p>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">{sukuCadang.Nama}</h1>
            <p className="text-sm text-muted-foreground">{sukuCadang.NamaKategori ?? 'Tanpa kategori'} &middot; Satuan {sukuCadang.SatuanDasar}</p>
          </div>
          <Badge variant={VARIAN_BADGE_STATUS_SUKU_CADANG[sukuCadang.Status]}>{sukuCadang.Status}</Badge>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Stok Minimum</p>
            <p className="text-sm font-medium text-foreground">{sukuCadang.StokMinimum} {sukuCadang.SatuanDasar}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Titik Pesan Ulang</p>
            <p className="text-sm font-medium text-foreground">{sukuCadang.TitikPesanUlang ?? '—'}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Harga Rata-rata</p>
            <p className="text-sm font-medium text-foreground">{formatUang(sukuCadang.HargaRataRata)}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Nomor Bagian</p>
            <p className="text-sm font-medium text-foreground">{sukuCadang.NomorBagian ?? '—'}</p>
          </div>
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Kelompok/Batch</h2>
            <DialogTambahKelompok sukuCadang={sukuCadang} />
          </div>
          {kelompokSukuCadang.length === 0 ? (
            <EmptyState judul="Belum ada batch." deskripsi="Tambahkan batch bila suku cadang ini dilacak per kelompok/kadaluarsa." />
          ) : (
            <div className="space-y-2">
              {kelompokSukuCadang.map((k) => (
                <div key={k.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm">
                  <div>
                    <span className="font-mono font-medium text-foreground">{k.NomorBatch}</span>
                    {k.TanggalKadaluarsa && <span className="ml-2 text-xs text-muted-foreground">Kadaluarsa: {new Date(k.TanggalKadaluarsa).toLocaleDateString('id-ID')}</span>}
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapusKelompok(k)}>Hapus</Button>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Kompatibilitas dengan Aset</h2>
            <DialogTambahKompatibilitas sukuCadang={sukuCadang} kategoriAset={kategoriAset} modelAset={modelAset} aset={aset} />
          </div>
          {kompatibilitasSukuCadang.length === 0 ? (
            <EmptyState judul="Belum ada kompatibilitas." deskripsi="Tambahkan aset, model aset, atau kategori aset yang cocok dengan suku cadang ini." />
          ) : (
            <div className="space-y-2">
              {kompatibilitasSukuCadang.map((k) => (
                <div key={k.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm">
                  <div>
                    <span className="font-medium text-foreground">{k.NamaAset ?? k.NamaModelAset ?? k.NamaKategoriAset}</span>
                    <Badge variant="netral" className="ml-2">{k.AsetId ? 'Aset' : k.ModelAsetId ? 'Model' : 'Kategori'}</Badge>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapusKompatibilitas(k)}>Hapus</Button>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
