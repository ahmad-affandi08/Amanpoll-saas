import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Penyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';
import { BidangKode } from '@/components/shared/BidangKode';

export function FormInfoPenyedia({
  penyedia,
  onSukses,
}: {
  penyedia: Penyedia | null;
  onSukses?: () => void;
}) {
  const form = useForm(
    penyedia
      ? {
          Kode: penyedia.Kode,
          Nama: penyedia.Nama,
          NamaLegal: penyedia.NamaLegal ?? '',
          NomorIdentitasPajak: penyedia.NomorIdentitasPajak ?? '',
          Email: penyedia.Email ?? '',
          Telepon: penyedia.Telepon ?? '',
          Website: penyedia.Website ?? '',
          Alamat: penyedia.Alamat ?? '',
          Kota: penyedia.Kota ?? '',
          Provinsi: penyedia.Provinsi ?? '',
          Negara: penyedia.Negara ?? '',
          Status: penyedia.Status,
        }
      : {
          Kode: '',
          Nama: '',
          NamaLegal: '',
          NomorIdentitasPajak: '',
          Email: '',
          Telepon: '',
          Website: '',
          Alamat: '',
          Kota: '',
          Provinsi: '',
          Negara: '',
          Status: 'Aktif' as const,
        },
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        if (!penyedia) form.reset();
        onSukses?.();
      },
    };
    if (penyedia) {
      router.put(rutePenyedia.detail(penyedia.Id), form.data, opsi);
    } else {
      router.post(rutePenyedia.index, form.data, opsi);
    }
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <BidangKode
          nilai={form.data.Kode}
          onUbah={(nilai) => form.setData('Kode', nilai)}
          galat={form.errors.Kode}
        />
        <div className="space-y-2">
          <Label>Nama</Label>
          <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
        </div>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label>Nama Legal</Label>
          <Input value={form.data.NamaLegal} onChange={(e) => form.setData('NamaLegal', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>NPWP</Label>
          <Input
            value={form.data.NomorIdentitasPajak}
            onChange={(e) => form.setData('NomorIdentitasPajak', e.target.value)}
          />
        </div>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label>Email</Label>
          <Input
            type="email"
            value={form.data.Email}
            onChange={(e) => form.setData('Email', e.target.value)}
          />
          {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
        </div>
        <div className="space-y-2">
          <Label>Telepon</Label>
          <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
        </div>
      </div>
      <div className="space-y-2">
        <Label>Website</Label>
        <Input
          value={form.data.Website}
          onChange={(e) => form.setData('Website', e.target.value)}
          placeholder="https://"
        />
        {form.errors.Website && <p className="text-sm text-destructive">{form.errors.Website}</p>}
      </div>
      <div className="space-y-2">
        <Label>Alamat</Label>
        <Textarea
          value={form.data.Alamat}
          onChange={(e) => form.setData('Alamat', e.target.value)}
          rows={2}
        />
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Kota</Label>
          <Input value={form.data.Kota} onChange={(e) => form.setData('Kota', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Provinsi</Label>
          <Input value={form.data.Provinsi} onChange={(e) => form.setData('Provinsi', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Negara</Label>
          <Input value={form.data.Negara} onChange={(e) => form.setData('Negara', e.target.value)} />
        </div>
      </div>
      <div className="space-y-2">
        <Label>Status</Label>
        <Select
          value={form.data.Status}
          onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}
        >
          <SelectTrigger className="w-48">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="Aktif">Aktif</SelectItem>
            <SelectItem value="Nonaktif">Nonaktif</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <DialogFooter>
        <Button type="submit" disabled={form.processing}>
          Simpan
        </Button>
      </DialogFooter>
    </form>
  );
}
