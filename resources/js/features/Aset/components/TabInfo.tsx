import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { DatePicker } from '@/components/ui/date-picker';
import type { Aset, KategoriAset, ModelAset } from '@/features/Aset/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import type { Penyedia } from '@/features/Penyedia/types';
import { ruteAset } from '@/features/Aset/api';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

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
            <Combobox
              nilai={form.data.KategoriAsetId}
              onPilih={(v) => form.setData('KategoriAsetId', v)}
              opsi={opsiDari(kategoriAset, (k) => k.Nama)}
            />
          </div>
          <div className="space-y-2">
            <Label nama="ModelAsetId">Model</Label>
            <Combobox
              nilai={form.data.ModelAsetId}
              onPilih={(v) => form.setData('ModelAsetId', v)}
              opsi={[opsiKosong('Tanpa model'), ...opsiDari(modelAset, (m) => m.Nama)]}
            />
          </div>
          <div className="space-y-2">
            <Label nama="PenyediaId">Penyedia</Label>
            <Combobox
              nilai={form.data.PenyediaId}
              onPilih={(v) => form.setData('PenyediaId', v)}
              opsi={[opsiKosong('Tanpa penyedia'), ...opsiDari(penyedia, (p) => p.Nama)]}
            />
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label nama="UnitOrganisasiId">Unit Organisasi</Label>
            <Combobox
              nilai={form.data.UnitOrganisasiId}
              onPilih={(v) => form.setData('UnitOrganisasiId', v)}
              opsi={[opsiKosong('Tidak ditautkan'), ...opsiDari(unitOrganisasi, (u) => u.Nama)]}
            />
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
            <Combobox
              nilai={form.data.Status}
              onPilih={(v) => form.setData('Status', v as Aset['Status'])}
              opsi={['Aktif', 'Nonaktif', 'Dipinjam', 'Rusak', 'Diarsipkan'].map((s) => ({
                nilai: s,
                label: s,
              }))}
            />
          </div>
          <div className="space-y-2">
            <Label nama="Kondisi">Kondisi</Label>
            <Combobox
              nilai={form.data.Kondisi}
              onPilih={(v) => form.setData('Kondisi', v as Aset['Kondisi'])}
              opsi={['Baik', 'PerluPerhatian', 'Rusak'].map((s) => ({ nilai: s, label: s }))}
            />
          </div>
          <div className="space-y-2">
            <Label nama="TingkatKritis">Tingkat Kritis</Label>
            <Combobox
              nilai={form.data.TingkatKritis}
              onPilih={(v) => form.setData('TingkatKritis', v as Aset['TingkatKritis'])}
              opsi={['Normal', 'Tinggi', 'SangatTinggi'].map((s) => ({ nilai: s, label: s }))}
            />
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
