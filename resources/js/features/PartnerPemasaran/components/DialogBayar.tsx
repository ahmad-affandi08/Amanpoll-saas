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
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { Payout } from '@/features/PartnerPemasaran/types';
import { Bidang } from '@/features/PartnerPemasaran/components/Bidang';

export function DialogBayar({ payout }: { payout: Payout }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Referensi: '', Catatan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePemasaran.partnerPayoutBayar(payout.Id), {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm">Tandai dibayar</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tandai {payout.Nomor} dibayar</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Referensi transfer" galat={form.errors.Referensi}>
            <Input value={form.data.Referensi} onChange={(e) => form.setData('Referensi', e.target.value)} />
          </Bidang>
          <Bidang label="Catatan" galat={form.errors.Catatan}>
            <Textarea value={form.data.Catatan} onChange={(e) => form.setData('Catatan', e.target.value)} />
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
