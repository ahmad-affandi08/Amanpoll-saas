import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import type { PrioritasUsulanAset, Referensi, UsulanAset } from '@/features/UsulanAset/types';
import { ruteUsulanAset } from '@/features/UsulanAset/api';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { PRIORITAS } from '@/features/UsulanAset/status';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { InputUang } from '@/components/shared/InputUang';

export function DialogUbahUsulan({
  usulan,
  unitOrganisasi,
  kategoriAset,
  modelAset,
  wajib,
}: {
  usulan: UsulanAset;
  unitOrganisasi: Referensi[];
  kategoriAset: Referensi[];
  modelAset: Referensi[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: usulan.UnitOrganisasiId,
    KategoriAsetId: usulan.KategoriAsetId ?? TANPA_PILIHAN,
    ModelAsetId: usulan.ModelAsetId ?? TANPA_PILIHAN,
    NamaKebutuhan: usulan.NamaKebutuhan,
    Jumlah: usulan.Jumlah,
    EstimasiHargaSatuan: usulan.EstimasiHargaSatuan ?? '',
    Alasan: usulan.Alasan,
    JenisKebutuhan: usulan.JenisKebutuhan ?? '',
    TahunKebutuhan: usulan.TahunKebutuhan?.toString() ?? '',
    Prioritas: usulan.Prioritas,
  });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KategoriAsetId: data.KategoriAsetId === TANPA_PILIHAN ? null : data.KategoriAsetId,
      ModelAsetId: data.ModelAsetId === TANPA_PILIHAN ? null : data.ModelAsetId,
      EstimasiHargaSatuan: data.EstimasiHargaSatuan || null,
      JenisKebutuhan: data.JenisKebutuhan || null,
      TahunKebutuhan: data.TahunKebutuhan || null,
    }));
    form.put(ruteUsulanAset.detail(usulan.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Pencil /> Ubah
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Ubah Usulan</DialogTitle>
          <DialogDescription>
            Usulan hanya dapat diubah selama berstatus draft atau ditolak.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="UnitOrganisasiId">Unit</Label>
                <Combobox
                  nilai={form.data.UnitOrganisasiId}
                  onPilih={(value) => form.setData('UnitOrganisasiId', value)}
                  opsi={opsiDari(unitOrganisasi, (item) => item.Nama)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="Prioritas">Prioritas</Label>
                <Select
                  value={form.data.Prioritas}
                  onValueChange={(value) => form.setData('Prioritas', value as PrioritasUsulanAset)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {PRIORITAS.map((item) => (
                      <SelectItem key={item} value={item}>
                        {item}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="NamaKebutuhan">Nama Kebutuhan</Label>
              <Input
                value={form.data.NamaKebutuhan}
                onChange={(event) => form.setData('NamaKebutuhan', event.target.value)}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="KategoriAsetId">Kategori</Label>
                <Combobox
                  nilai={form.data.KategoriAsetId}
                  onPilih={(value) => form.setData('KategoriAsetId', value)}
                  opsi={[opsiKosong('Belum ditentukan'), ...opsiDari(kategoriAset, (item) => item.Nama)]}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="ModelAsetId">Model</Label>
                <Combobox
                  nilai={form.data.ModelAsetId}
                  onPilih={(value) => form.setData('ModelAsetId', value)}
                  opsi={[opsiKosong('Belum ditentukan'), ...opsiDari(modelAset, (item) => item.Nama)]}
                />
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-1.5">
                <Label nama="Jumlah">Jumlah</Label>
                <Input
                  type="number"
                  step="0.0001"
                  value={form.data.Jumlah}
                  onChange={(event) => form.setData('Jumlah', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="EstimasiHargaSatuan">Harga / Unit</Label>
                <InputUang
                  value={form.data.EstimasiHargaSatuan}
                  onChange={(nilai) => form.setData('EstimasiHargaSatuan', nilai)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="TahunKebutuhan">Tahun</Label>
                <Input
                  type="number"
                  value={form.data.TahunKebutuhan}
                  onChange={(event) => form.setData('TahunKebutuhan', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="JenisKebutuhan">Jenis Kebutuhan</Label>
              <Input
                value={form.data.JenisKebutuhan}
                onChange={(event) => form.setData('JenisKebutuhan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan</Label>
              <Textarea
                rows={4}
                value={form.data.Alasan}
                onChange={(event) => form.setData('Alasan', event.target.value)}
              />
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Perubahan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
