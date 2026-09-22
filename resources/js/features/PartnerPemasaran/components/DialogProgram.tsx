import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { Program } from '@/features/PartnerPemasaran/types';
import { Bidang } from '@/features/PartnerPemasaran/components/Bidang';

export function DialogProgram({ program }: { program: Program | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: program?.Kode ?? '',
    Nama: program?.Nama ?? '',
    Keterangan: program?.Keterangan ?? '',
    HariAtribusi: program?.HariAtribusi ?? 180,
    Aktif: program?.Aktif ?? false,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = { preserveScroll: true, onSuccess: () => setBuka(false) };

    if (program) {
      form.put(rutePemasaran.partnerProgramDetail(program.Kode), selesai);
    } else {
      form.post(rutePemasaran.partnerProgram, selesai);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={program ? 'outline' : 'default'}>
          {program ? 'Ubah' : 'Program baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{program ? 'Ubah program partner' : 'Program partner baru'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Kode" galat={form.errors.Kode}>
            <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} />
          </Bidang>
          <Bidang label="Nama" galat={form.errors.Nama}>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          </Bidang>
          <Bidang label="Keterangan" galat={form.errors.Keterangan}>
            <Textarea
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
            />
          </Bidang>
          <Bidang label="Hari atribusi" galat={form.errors.HariAtribusi}>
            <Input
              type="number"
              value={form.data.HariAtribusi}
              onChange={(e) => form.setData('HariAtribusi', Number(e.target.value))}
            />
          </Bidang>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Aktif
          </label>
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
