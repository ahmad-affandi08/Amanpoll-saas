import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
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
import type { PrioritasUsulanAset, UsulanAset } from '@/features/UsulanAset/types';
import { ruteUsulanAset } from '@/features/UsulanAset/api';
import { PRIORITAS } from '@/features/UsulanAset/status';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

export function DialogPenilaian({ usulan, wajib }: { usulan: UsulanAset; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kriteria: '', Bobot: '1', Nilai: '', Prioritas: usulan.Prioritas });
  function submit(event: FormEvent): void {
    event.preventDefault();
    form.post(ruteUsulanAset.penilaian(usulan.Id), {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset('Kriteria', 'Nilai');
      },
    });
  }
  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          <Star /> Tambah Penilaian
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Nilai Usulan</DialogTitle>
          <DialogDescription>
            Skor dihitung di server dari bobot × nilai dan prioritas usulan diperbarui.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Kriteria">Kriteria</Label>
              <Input
                value={form.data.Kriteria}
                onChange={(event) => form.setData('Kriteria', event.target.value)}
              />
              {form.errors.Kriteria && <p className="text-sm text-destructive">{form.errors.Kriteria}</p>}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="Bobot">Bobot</Label>
                <Input
                  type="number"
                  min="0.0001"
                  max="100"
                  step="0.0001"
                  value={form.data.Bobot}
                  onChange={(event) => form.setData('Bobot', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="Nilai">Nilai</Label>
                <Input
                  type="number"
                  min="0"
                  max="100"
                  step="0.0001"
                  value={form.data.Nilai}
                  onChange={(event) => form.setData('Nilai', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Prioritas">Prioritas Hasil</Label>
              <Select
                value={form.data.Prioritas}
                onValueChange={(value) => form.setData('Prioritas', value as PrioritasUsulanAset)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PRIORITAS.map((item) => (
                    <SelectItem key={item} value={item}>
                      {item}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan Penilaian
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
