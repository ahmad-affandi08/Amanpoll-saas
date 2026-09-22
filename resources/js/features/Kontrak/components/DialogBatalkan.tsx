import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Ban } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Kontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';

export function DialogBatalkan({ kontrak }: { kontrak: Kontrak }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '' });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteKontrak.batalkan(kontrak.Id), { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="destructive" className="min-h-11 sm:min-h-9">
          <Ban /> Batalkan
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Batalkan kontrak {kontrak.Nomor}?</DialogTitle>
          <DialogDescription>
            Kontrak tidak dapat diaktifkan kembali; cakupan aset dan layanannya tetap tersimpan sebagai
            riwayat.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="AlasanBatal">Alasan pembatalan</Label>
            <Textarea
              id="AlasanBatal"
              rows={3}
              value={form.data.Alasan}
              onChange={(event) => form.setData('Alasan', event.target.value)}
            />
            {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" variant="destructive" disabled={form.processing}>
              Batalkan Kontrak
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
