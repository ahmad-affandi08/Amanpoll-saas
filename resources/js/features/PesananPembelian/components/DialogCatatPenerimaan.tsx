import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { PackagePlus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { PesananPembelian } from '@/features/PesananPembelian/types';
import { rutePesananPembelian } from '@/features/PesananPembelian/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import type { GudangRingkas } from '@/features/PesananPembelian/types';
import { hitungSisa } from '@/features/PesananPembelian/perhitungan';

const KONDISI: KondisiPenerimaan[] = ['Baik', 'RusakRingan', 'Rusak'];

interface BarisPenerimaan {
  DetailPesananPembelianId: string;
  JumlahDiterima: string;
  JumlahDitolak: string;
  Kondisi: KondisiPenerimaan;
  NomorSeri: string;
  Catatan: string;
}

export function DialogCatatPenerimaan({
  pesanan,
  gudang,
}: {
  pesanan: PesananPembelian;
  gudang: GudangRingkas[];
}) {
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
