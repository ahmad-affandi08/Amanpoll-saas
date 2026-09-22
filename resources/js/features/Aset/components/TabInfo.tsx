import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import type { Aset, KategoriAset, ModelAset } from '@/features/Aset/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import type { Penyedia } from '@/features/Penyedia/types';
import { ruteAset } from '@/features/Aset/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function TabInfo({
  aset,
  kategoriAset,
  modelAset,
  penyedia,
  unitOrganisasi,
  wajib,
}: {
  aset: Aset;
  kategoriAset: KategoriAset[];
  modelAset: ModelAset[];
  penyedia: Penyedia[];
  unitOrganisasi: UnitOrganisasi[];
  wajib: AturanWajib;
}) {
  const form = useForm({
    KategoriAsetId: aset.KategoriAsetId,
    ModelAsetId: aset.ModelAsetId ?? TANPA_PILIHAN,
    PenyediaId: aset.PenyediaId ?? TANPA_PILIHAN,
    UnitOrganisasiId: aset.UnitOrganisasiId ?? TANPA_PILIHAN,
    KodeAset: aset.KodeAset,
    Nama: aset.Nama,
    NomorSeri: aset.NomorSeri ?? '',
    NomorInventaris: aset.NomorInventaris ?? '',
    NomorRegistrasiEksternal: aset.NomorRegistrasiEksternal ?? '',
    TanggalPerolehan: aset.TanggalPerolehan ?? '',
    TanggalMulaiOperasi: aset.TanggalMulaiOperasi ?? '',
    TanggalAkhirOperasi: aset.TanggalAkhirOperasi ?? '',
    HargaPerolehan: aset.HargaPerolehan ?? '',
    NilaiResidu: aset.NilaiResidu ?? '',
    MataUang: aset.MataUang,
    SumberDana: aset.SumberDana ?? '',
    MetodePenyusutan: aset.MetodePenyusutan ?? '',
    UmurManfaatBulan: aset.UmurManfaatBulan?.toString() ?? '',
    Status: aset.Status,
    Kondisi: aset.Kondisi,
    TingkatKritis: aset.TingkatKritis,
    NfcUid: aset.NfcUid ?? '',
    KodeBatang: aset.KodeBatang ?? '',
    Catatan: aset.Catatan ?? '',
    Versi: aset.Versi,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      ModelAsetId: form.data.ModelAsetId === TANPA_PILIHAN ? null : form.data.ModelAsetId,
      PenyediaId: form.data.PenyediaId === TANPA_PILIHAN ? null : form.data.PenyediaId,
      UnitOrganisasiId: form.data.UnitOrganisasiId === TANPA_PILIHAN ? null : form.data.UnitOrganisasiId,
    };
    router.put(ruteAset.detail(aset.Id), payload, { preserveScroll: true });
  };

  return (
    <AturanWajibProvider aturan={wajib}>
      <form onSubmit={submit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <BidangKode
            nilai={form.data.KodeAset}
            onUbah={(nilai) => form.setData('KodeAset', nilai)}
            galat={form.errors.KodeAset}
            label="Kode Aset"
            id="KodeAset"
          />
          <div className="space-y-2">
            <Label nama="Nama">Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="KategoriAsetId">Kategori</Label>
            <Select value={form.data.KategoriAsetId} onValueChange={(v) => form.setData('KategoriAsetId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {kategoriAset.map((k) => (
                  <SelectItem key={k.Id} value={k.Id}>
                    {k.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label nama="ModelAsetId">Model</Label>
            <Select value={form.data.ModelAsetId} onValueChange={(v) => form.setData('ModelAsetId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_PILIHAN}>Tanpa model</SelectItem>
                {modelAset.map((m) => (
                  <SelectItem key={m.Id} value={m.Id}>
                    {m.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label nama="PenyediaId">Penyedia</Label>
            <Select value={form.data.PenyediaId} onValueChange={(v) => form.setData('PenyediaId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_PILIHAN}>Tanpa penyedia</SelectItem>
                {penyedia.map((p) => (
                  <SelectItem key={p.Id} value={p.Id}>
                    {p.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="UnitOrganisasiId">Unit Organisasi</Label>
            <Select
              value={form.data.UnitOrganisasiId}
              onValueChange={(v) => form.setData('UnitOrganisasiId', v)}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_PILIHAN}>Tidak ditautkan</SelectItem>
                {unitOrganisasi.map((u) => (
                  <SelectItem key={u.Id} value={u.Id}>
                    {u.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label nama="NomorSeri">Nomor Seri</Label>
            <Input value={form.data.NomorSeri} onChange={(e) => form.setData('NomorSeri', e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label nama="NomorInventaris">Nomor Inventaris</Label>
            <Input
              value={form.data.NomorInventaris}
              onChange={(e) => form.setData('NomorInventaris', e.target.value)}
            />
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="TanggalPerolehan">Tanggal Perolehan</Label>
            <DatePicker
              value={form.data.TanggalPerolehan}
              onChange={(val) => form.setData('TanggalPerolehan', val)}
              placeholder="Pilih tanggal perolehan"
            />
          </div>
          <div className="space-y-2">
            <Label nama="TanggalMulaiOperasi">Tanggal Mulai Operasi</Label>
            <DatePicker
              value={form.data.TanggalMulaiOperasi}
              onChange={(val) => form.setData('TanggalMulaiOperasi', val)}
              placeholder="Pilih tanggal mulai"
            />
          </div>
          <div className="space-y-2">
            <Label nama="TanggalAkhirOperasi">Tanggal Akhir Operasi</Label>
            <DatePicker
              value={form.data.TanggalAkhirOperasi}
              onChange={(val) => form.setData('TanggalAkhirOperasi', val)}
              placeholder="Pilih tanggal akhir"
            />
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="HargaPerolehan">Harga Perolehan</Label>
            <Input
              type="number"
              min={0}
              value={form.data.HargaPerolehan}
              onChange={(e) => form.setData('HargaPerolehan', e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label nama="NilaiResidu">Nilai Residu</Label>
            <Input
              type="number"
              min={0}
              value={form.data.NilaiResidu}
              onChange={(e) => form.setData('NilaiResidu', e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label nama="UmurManfaatBulan">Umur Manfaat (bulan)</Label>
            <Input
              type="number"
              min={1}
              value={form.data.UmurManfaatBulan}
              onChange={(e) => form.setData('UmurManfaatBulan', e.target.value)}
            />
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="Status">Status</Label>
            <Select
              value={form.data.Status}
              onValueChange={(v) => form.setData('Status', v as Aset['Status'])}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {['Aktif', 'Nonaktif', 'Dipinjam', 'Rusak', 'Diarsipkan'].map((s) => (
                  <SelectItem key={s} value={s}>
                    {s}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label nama="Kondisi">Kondisi</Label>
            <Select
              value={form.data.Kondisi}
              onValueChange={(v) => form.setData('Kondisi', v as Aset['Kondisi'])}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {['Baik', 'PerluPerhatian', 'Rusak'].map((s) => (
                  <SelectItem key={s} value={s}>
                    {s}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label nama="TingkatKritis">Tingkat Kritis</Label>
            <Select
              value={form.data.TingkatKritis}
              onValueChange={(v) => form.setData('TingkatKritis', v as Aset['TingkatKritis'])}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {['Normal', 'Tinggi', 'SangatTinggi'].map((s) => (
                  <SelectItem key={s} value={s}>
                    {s}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="space-y-2">
          <Label nama="Catatan">Catatan</Label>
          <Textarea
            value={form.data.Catatan}
            onChange={(e) => form.setData('Catatan', e.target.value)}
            rows={2}
          />
        </div>
        <div className="flex justify-end">
          <Button type="submit" disabled={form.processing}>
            Simpan Perubahan
          </Button>
        </div>
      </form>
    </AturanWajibProvider>
  );
}
