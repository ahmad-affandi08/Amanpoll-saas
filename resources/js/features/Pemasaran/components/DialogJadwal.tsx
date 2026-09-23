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
import { dariMasukanWaktu } from '@/lib/waktu';

export function DialogJadwal({ akar }: { akar: string }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ JadwalPada: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      `${akar}/jadwal`,
      { JadwalPada: dariMasukanWaktu(form.data.JadwalPada) },
      {
        preserveScroll: true,
        onSuccess: () => setBuka(false),
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Jadwalkan
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Jadwalkan Penerbitan</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid content-start gap-2">
            <Label htmlFor="JadwalPada">Terbit pada</Label>
            <Input
              id="JadwalPada"
              type="datetime-local"
              value={form.data.JadwalPada}
              onChange={(e) => form.setData('JadwalPada', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Menjadwalkan ulang membatalkan rencana sebelumnya, sehingga tidak ada posting ganda.
            </p>
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Jadwalkan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
