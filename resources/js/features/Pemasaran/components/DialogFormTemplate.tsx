import { FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { Template } from '@/features/Pemasaran/types';

export function DialogFormTemplate({
  template,
  variabel,
}: {
  template: Template | null;
  variabel: string[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: template?.Kode ?? '',
    Nama: template?.Nama ?? '',
    Bahasa: template?.Bahasa ?? 'id',
    Kategori: template?.Kategori ?? 'Marketing',
    IsiTeks: template?.IsiTeks ?? '',
    Aktif: template?.Aktif ?? true,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!template) form.reset();
      },
    };

    if (template) {
      router.put(rutePemasaran.whatsappTemplateDetail(template.Id), form.data, opsi);
    } else {
      router.post(rutePemasaran.whatsappTemplate, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={template ? 'outline' : 'default'} size={template ? 'sm' : 'default'}>
          {template ? 'Ubah' : 'Tambah Template'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{template ? 'Ubah Template' : 'Tambah Template'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Kode">Kode</Label>
              <Input
                id="Kode"
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                required
              />
              {form.errors.Kode ? <p className="text-sm text-destructive">{form.errors.Kode}</p> : null}
            </div>
            <div className="grid gap-2">
              <Label htmlFor="Nama">Nama</Label>
              <Input
                id="Nama"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Bahasa">Bahasa</Label>
              <Input
                id="Bahasa"
                value={form.data.Bahasa}
                onChange={(e) => form.setData('Bahasa', e.target.value)}
                required
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="Kategori">Kategori</Label>
              <Input
                id="Kategori"
                value={form.data.Kategori}
                onChange={(e) => form.setData('Kategori', e.target.value)}
                required
              />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IsiTeks">Naskah</Label>
            <Textarea
              id="IsiTeks"
              rows={6}
              value={form.data.IsiTeks}
              onChange={(e) => form.setData('IsiTeks', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Variabel yang dikenal: {variabel.map((satu) => `{{${satu}}}`).join(', ')}.
            </p>
            {form.errors.IsiTeks ? <p className="text-sm text-destructive">{form.errors.IsiTeks}</p> : null}
            <p className="text-sm text-muted-foreground">
              Mengubah naskah template yang sudah disetujui mengembalikannya ke draf: yang disetujui penyedia
              adalah naskah lamanya.
            </p>
          </div>

          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Template aktif
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
