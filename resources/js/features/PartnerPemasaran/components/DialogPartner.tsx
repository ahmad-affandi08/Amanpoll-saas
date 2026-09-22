import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { Partner, Pilihan, Program } from '@/features/PartnerPemasaran/types';
import { Bidang } from '@/features/PartnerPemasaran/components/Bidang';

export function DialogPartner({
  partner,
  program,
  pilihan,
}: {
  partner: Partner | null;
  program: Program[];
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    ProgramPartnerId: partner?.ProgramPartnerId ?? program[0]?.Id ?? '',
    NamaPerusahaan: partner?.NamaPerusahaan ?? '',
    Jenis: partner?.Jenis ?? pilihan.Jenis[0] ?? '',
    NamaPic: partner?.NamaPic ?? '',
    EmailPic: partner?.EmailPic ?? '',
    TeleponPic: partner?.TeleponPic ?? '',
    Status: partner?.Status ?? 'Diajukan',
    ReferensiPerjanjian: partner?.ReferensiPerjanjian ?? '',
    ReferensiPayout: partner?.ReferensiPayout ?? '',
    KataSandi: '',
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const selesai = {
      preserveScroll: true,
      onSuccess: () => {
        form.reset('KataSandi');
        setBuka(false);
      },
    };

    if (partner) {
      form.put(rutePemasaran.partnerDetail(partner.Id), selesai);
    } else {
      form.post(rutePemasaran.partner, selesai);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={partner ? 'outline' : 'default'}>
          {partner ? 'Ubah' : 'Partner baru'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{partner ? 'Ubah partner' : 'Partner baru'}</DialogTitle>
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
          <Bidang label="Nama perusahaan" galat={form.errors.NamaPerusahaan}>
            <Input
              value={form.data.NamaPerusahaan}
              onChange={(e) => form.setData('NamaPerusahaan', e.target.value)}
            />
          </Bidang>
          <Bidang label="Jenis" galat={form.errors.Jenis}>
            <Select value={form.data.Jenis} onValueChange={(nilai) => form.setData('Jenis', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih jenis" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Jenis.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Nama PIC" galat={form.errors.NamaPic}>
            <Input value={form.data.NamaPic} onChange={(e) => form.setData('NamaPic', e.target.value)} />
          </Bidang>
          <Bidang label="Email PIC" galat={form.errors.EmailPic}>
            <Input
              type="email"
              value={form.data.EmailPic}
              onChange={(e) => form.setData('EmailPic', e.target.value)}
            />
          </Bidang>
          <Bidang label="Telepon PIC" galat={form.errors.TeleponPic}>
            <Input
              value={form.data.TeleponPic}
              onChange={(e) => form.setData('TeleponPic', e.target.value)}
            />
          </Bidang>
          <Bidang label="Status" galat={form.errors.Status}>
            <Select value={form.data.Status} onValueChange={(nilai) => form.setData('Status', nilai)}>
              <SelectTrigger>
                <SelectValue placeholder="Pilih status" />
              </SelectTrigger>
              <SelectContent>
                {pilihan.StatusPartner.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Bidang>
          <Bidang label="Referensi perjanjian" galat={form.errors.ReferensiPerjanjian}>
            <Input
              value={form.data.ReferensiPerjanjian}
              onChange={(e) => form.setData('ReferensiPerjanjian', e.target.value)}
            />
          </Bidang>
          <Bidang label="Referensi payout" galat={form.errors.ReferensiPayout}>
            <Input
              value={form.data.ReferensiPayout}
              onChange={(e) => form.setData('ReferensiPayout', e.target.value)}
            />
          </Bidang>
          <Bidang
            label={partner ? 'Kata sandi baru (kosongkan bila tidak diubah)' : 'Kata sandi'}
            galat={form.errors.KataSandi}
          >
            <Input
              type="password"
              autoComplete="new-password"
              value={form.data.KataSandi}
              onChange={(e) => form.setData('KataSandi', e.target.value)}
            />
          </Bidang>
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
