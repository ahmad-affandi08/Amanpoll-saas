import { FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { KontenSosial, PilihanSosial } from '@/features/Pemasaran/types';
import { BidangKode } from '@/components/shared/BidangKode';

function PilihRelasi({
  id,
  label,
  nilai,
  opsi,
  ubah,
}: {
  id: string;
  label: string;
  nilai: string;
  opsi: Record<string, string>;
  ubah: (nilai: string) => void;
}) {
  return (
    <div className="grid gap-2">
      <Label htmlFor={id}>{label}</Label>
      <Select value={nilai === '' ? 'kosong' : nilai} onValueChange={(v) => ubah(v === 'kosong' ? '' : v)}>
        <SelectTrigger id={id}>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="kosong">Belum ditentukan</SelectItem>
          {Object.entries(opsi).map(([kunci, teks]) => (
            <SelectItem key={kunci} value={kunci}>
              {teks}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
}

export function DialogFormKonten({
  konten,
  pilihan,
}: {
  konten: KontenSosial | null;
  pilihan: PilihanSosial;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: konten?.Kode ?? '',
    Judul: konten?.Judul ?? '',
    Ringkasan: konten?.Ringkasan ?? '',
    MediaUrl: konten?.MediaUrl ?? '',
    HalamanId: konten?.HalamanId ?? '',
    KampanyeId: konten?.KampanyeId ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!konten) form.reset();
      },
    };

    if (konten) {
      router.put(rutePemasaran.sosialKonten(konten.Id), form.data, opsi);
    } else {
      router.post(rutePemasaran.sosial, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={konten ? 'outline' : 'default'} size={konten ? 'sm' : 'default'}>
          {konten ? 'Ubah konten' : 'Tambah Konten'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{konten ? 'Ubah Konten' : 'Tambah Konten'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <BidangKode
            nilai={form.data.Kode}
            onUbah={(nilai) => form.setData('Kode', nilai)}
            galat={form.errors.Kode}
          />

          <div className="grid gap-2">
            <Label htmlFor="Judul">Judul</Label>
            <Input
              id="Judul"
              value={form.data.Judul}
              onChange={(e) => form.setData('Judul', e.target.value)}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Ringkasan">Ringkasan</Label>
            <Textarea
              id="Ringkasan"
              rows={3}
              value={form.data.Ringkasan}
              onChange={(e) => form.setData('Ringkasan', e.target.value)}
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <PilihRelasi
              id="KampanyeId"
              label="Kampanye"
              nilai={form.data.KampanyeId}
              opsi={pilihan.Kampanye}
              ubah={(v) => form.setData('KampanyeId', v)}
            />
            <PilihRelasi
              id="HalamanId"
              label="Halaman yang dipromosikan"
              nilai={form.data.HalamanId}
              opsi={pilihan.Halaman}
              ubah={(v) => form.setData('HalamanId', v)}
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
