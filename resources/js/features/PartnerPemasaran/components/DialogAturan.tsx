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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { Aturan, Partner, Pilihan, Program } from '@/features/PartnerPemasaran/types';
import { Bidang } from '@/features/PartnerPemasaran/components/Bidang';

export function DialogAturan({
  aturan,
  program,
  partner,
  pilihan,
}: {
  aturan: Aturan | null;
  program: Program[];
  partner: Partner[];
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    ProgramPartnerId: aturan?.ProgramPartnerId ?? program[0]?.Id ?? '',
    PartnerId: aturan?.PartnerId ?? '',
    Nama: aturan?.Nama ?? '',
    Jenis: aturan?.Jenis ?? pilihan.JenisKomisi[0] ?? '',
    Nilai: aturan?.Nilai ?? 10,
    MaksPembayaran: aturan?.MaksPembayaran ?? '',
    Aktif: aturan?.Aktif ?? true,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = { preserveScroll: true, onSuccess: () => setBuka(false) };

    if (aturan) {
      form.put(rutePemasaran.partnerAturanDetail(aturan.Id), selesai);
    } else {
      form.post(rutePemasaran.partnerAturan, selesai);
    }
  };

  const seprogram = partner.filter((satu) => satu.ProgramPartnerId === form.data.ProgramPartnerId);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={aturan ? 'outline' : 'default'}>
          {aturan ? 'Ubah' : 'Aturan baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{aturan ? 'Ubah aturan komisi' : 'Aturan komisi baru'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Program" galat={form.errors.ProgramPartnerId}>
            <Select
              value={form.data.ProgramPartnerId}
              onValueChange={(nilai) => form.setData('ProgramPartnerId', nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Pilih program" />
              </SelectTrigger>
              <SelectContent>
                {program.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Khusus partner (kosong = bawaan program)" galat={form.errors.PartnerId}>
            <Select
              value={form.data.PartnerId === '' ? 'bawaan' : form.data.PartnerId}
              onValueChange={(nilai) => form.setData('PartnerId', nilai === 'bawaan' ? '' : nilai)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Bawaan program" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="bawaan">Bawaan program</SelectItem>
                {seprogram.map((satu) => (
                  <SelectItem key={satu.Id} value={satu.Id}>
                    {satu.NamaPerusahaan}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Nama" galat={form.errors.Nama}>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          </Bidang>
          <Bidang label="Jenis" galat={form.errors.Jenis}>
            <Select value={form.data.Jenis} onValueChange={(nilai) => form.setData('Jenis', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.JenisKomisi.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang
            label={form.data.Jenis === 'Persentase' ? 'Nilai (persen)' : 'Nilai (rupiah)'}
            galat={form.errors.Nilai}
          >
            <Input
              type="number"
              step="0.01"
              value={form.data.Nilai}
              onChange={(e) => form.setData('Nilai', Number(e.target.value))}
            />
          </Bidang>
          <Bidang label="Maksimal pembayaran (kosong = tanpa batas)" galat={form.errors.MaksPembayaran}>
            <Input
              type="number"
              value={form.data.MaksPembayaran}
              onChange={(e) => form.setData('MaksPembayaran', e.target.value)}
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
