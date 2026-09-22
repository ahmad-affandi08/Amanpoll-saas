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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { TemplateWhatsApp } from '@/features/Pemasaran/types';

/** Jalur manual untuk penyedia tanpa API: keputusannya tetap milik penyedia, operator hanya menyalin. */
export function DialogKeputusan({
  template,
  pilihan,
}: {
  template: TemplateWhatsApp;
  pilihan: { Status: string[] };
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: template.TujuanStatus[0] ?? template.StatusPersetujuan,
    IdTemplatePenyedia: template.IdTemplatePenyedia ?? '',
    Alasan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(rutePemasaran.whatsappTemplateKeputusan(template.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  if (template.TujuanStatus.length === 0) {
    return null;
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Catat keputusan
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Keputusan Penyedia</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Status">Status</Label>
            <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
              <SelectTrigger id="Status">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Status.filter((satu) => template.TujuanStatus.includes(satu)).map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <p className="text-sm text-muted-foreground">
              Hanya status yang sah dari {template.StatusPersetujuan} yang tersedia di sini.
            </p>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IdTemplatePenyedia">Id template penyedia</Label>
            <Input
              id="IdTemplatePenyedia"
              value={form.data.IdTemplatePenyedia}
              onChange={(e) => form.setData('IdTemplatePenyedia', e.target.value)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Alasan">Alasan</Label>
            <Textarea
              id="Alasan"
              rows={3}
              value={form.data.Alasan}
              onChange={(e) => form.setData('Alasan', e.target.value)}
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Catat
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
