import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import { BidangKode } from '@/components/shared/BidangKode';

export function DialogBuatStandar() {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: '',
    Nama: '',
    Penerbit: '',
    VersiStandar: '',
    JenisIndustri: '',
    Deskripsi: '',
    Aktif: true,
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      Penerbit: data.Penerbit || null,
      VersiStandar: data.VersiStandar || null,
      JenisIndustri: data.JenisIndustri || null,
      Deskripsi: data.Deskripsi || null,
    }));
    form.post(ruteKepatuhan.standar, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Standar
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Standar Kepatuhan</DialogTitle>
          <DialogDescription>
            Amanpoll tidak membawa katalog standar bawaan. Daftarkan standar yang benar-benar berlaku bagi
            organisasi Anda beserta versinya.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
            />
            <div className="space-y-1.5">
              <Label htmlFor="NamaStandar">Nama</Label>
              <Input
                id="NamaStandar"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="space-y-1.5">
              <Label htmlFor="Penerbit">Penerbit</Label>
              <Input
                id="Penerbit"
                value={form.data.Penerbit}
                onChange={(event) => form.setData('Penerbit', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="VersiStandar">Versi</Label>
              <Input
                id="VersiStandar"
                value={form.data.VersiStandar}
                onChange={(event) => form.setData('VersiStandar', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="JenisIndustri">Lingkup / Industri</Label>
              <Input
                id="JenisIndustri"
                value={form.data.JenisIndustri}
                onChange={(event) => form.setData('JenisIndustri', event.target.value)}
              />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="DeskripsiStandar">Deskripsi</Label>
            <Textarea
              id="DeskripsiStandar"
              rows={2}
              value={form.data.Deskripsi}
              onChange={(event) => form.setData('Deskripsi', event.target.value)}
            />
          </div>
          <div className="flex items-center justify-between rounded-[9px] border border-border p-3">
            <div>
              <p className="text-sm font-medium">Standar aktif</p>
              <p className="text-xs text-muted-foreground">
                Standar nonaktif tidak dapat ditugaskan ke aset baru.
              </p>
            </div>
            <Switch checked={form.data.Aktif} onCheckedChange={(nilai) => form.setData('Aktif', nilai)} />
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Standar
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
