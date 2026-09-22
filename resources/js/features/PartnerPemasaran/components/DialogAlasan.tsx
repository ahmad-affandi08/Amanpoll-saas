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
import { Textarea } from '@/components/ui/textarea';
import { Bidang } from '@/features/PartnerPemasaran/components/Bidang';

export function DialogAlasan({
  judul,
  tombol,
  url,
  maks,
}: {
  judul: string;
  tombol: string;
  url: string;
  maks: number;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Alasan: '' });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(url, { preserveScroll: true, onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          {tombol}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{judul}</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <Bidang label="Alasan" galat={form.errors.Alasan}>
            <Textarea
              maxLength={maks}
              value={form.data.Alasan}
              onChange={(e) => form.setData('Alasan', e.target.value)}
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
