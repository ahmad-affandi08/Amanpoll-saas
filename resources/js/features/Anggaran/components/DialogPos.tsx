import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Anggaran, PosAnggaran } from '@/features/Anggaran/types';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';

export function DialogPos({
  anggaran,
  pos,
  semuaPos,
}: {
  anggaran: Anggaran;
  pos?: PosAnggaran;
  semuaPos: PosAnggaran[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    IndukId: pos?.IndukId ?? TANPA_PILIHAN,
    Kode: pos?.Kode ?? '',
    Nama: pos?.Nama ?? '',
    Jumlah: pos?.Jumlah ?? '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, IndukId: data.IndukId === TANPA_PILIHAN ? null : data.IndukId }));
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    if (pos) form.put(ruteAnggaran.posDetail(pos.Id), opsi);
    else form.post(ruteAnggaran.pos(anggaran.Id), opsi);
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        {pos ? (
          <Button variant="ghost" size="sm" aria-label={`Ubah ${pos.Nama}`}>
            <Pencil />
          </Button>
        ) : (
          <Button size="sm">
            <Plus /> Tambah Pos
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{pos ? 'Ubah Pos Anggaran' : 'Tambah Pos Anggaran'}</DialogTitle>
          <DialogDescription>
            Nilai anak tidak boleh melebihi pos induk; pos utama tidak boleh melampaui total anggaran.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Pos Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(value) => form.setData('IndukId', value)}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_PILIHAN}>Pos utama</SelectItem>
                {semuaPos
                  .filter((item) => item.Id !== pos?.Id)
                  .map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Kode} — {item.Nama}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="kode-pos">Kode</Label>
              <Input
                id="kode-pos"
                value={form.data.Kode}
                onChange={(event) => form.setData('Kode', event.target.value)}
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="nilai-pos">Nilai</Label>
              <Input
                id="nilai-pos"
                type="number"
                min="0.01"
                step="0.01"
                value={form.data.Jumlah}
                onChange={(event) => form.setData('Jumlah', event.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="nama-pos">Nama</Label>
            <Input
              id="nama-pos"
              value={form.data.Nama}
              onChange={(event) => form.setData('Nama', event.target.value)}
            />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              {pos ? 'Simpan Perubahan' : 'Tambah Pos'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
