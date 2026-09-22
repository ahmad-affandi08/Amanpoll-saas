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
import type { Distribusi, Konten, Pilihan } from '@/features/Pemasaran/types';

export function DialogFormDistribusi({
  konten,
  distribusi,
  pilihan,
}: {
  konten: Konten;
  distribusi: Distribusi | null;
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Channel: distribusi?.Channel ?? pilihan.Channel[0],
    Caption: distribusi?.Caption ?? '',
    MediaUrl: distribusi?.MediaUrl ?? '',
    Cta: distribusi?.Cta ?? '',
    TautanTujuan: distribusi?.TautanTujuan ?? '',
    UtmSource: distribusi?.UtmSource ?? '',
    UtmMedium: distribusi?.UtmMedium ?? '',
    UtmTerm: distribusi?.UtmTerm ?? '',
    UtmContent: distribusi?.UtmContent ?? '',
  });

  const wajibMedia = pilihan.ChannelWajibMedia.includes(form.data.Channel);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!distribusi) form.reset();
      },
    };

    if (distribusi) {
      router.put(rutePemasaran.sosialDistribusiDetail(konten.Id, distribusi.Id), form.data, opsi);
    } else {
      router.post(rutePemasaran.sosialDistribusi(konten.Id), form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          {distribusi ? 'Ubah' : 'Tambah distribusi'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{distribusi ? 'Ubah Distribusi' : 'Tambah Distribusi'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Channel">Channel</Label>
            <Select value={form.data.Channel} onValueChange={(v) => form.setData('Channel', v)}>
              <SelectTrigger id="Channel">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Channel.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.Channel ? <p className="text-sm text-destructive">{form.errors.Channel}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Caption">Caption</Label>
            <Textarea
              id="Caption"
              rows={5}
              value={form.data.Caption}
              onChange={(e) => form.setData('Caption', e.target.value)}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="MediaUrl">Media</Label>
            <Input
              id="MediaUrl"
              type="url"
              value={form.data.MediaUrl}
              onChange={(e) => form.setData('MediaUrl', e.target.value)}
              required={wajibMedia}
            />
            {wajibMedia ? (
              <p className="text-sm text-muted-foreground">
                Channel ini menuntut media; caption saja akan ditolak penyedianya.
              </p>
            ) : null}
            {form.errors.MediaUrl ? <p className="text-sm text-destructive">{form.errors.MediaUrl}</p> : null}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Cta">CTA</Label>
              <Input id="Cta" value={form.data.Cta} onChange={(e) => form.setData('Cta', e.target.value)} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="TautanTujuan">Tautan tujuan</Label>
              <Input
                id="TautanTujuan"
                type="url"
                value={form.data.TautanTujuan}
                onChange={(e) => form.setData('TautanTujuan', e.target.value)}
              />
            </div>
          </div>

          <fieldset className="grid gap-2">
            <legend className="text-sm font-medium">UTM distribusi</legend>
            <p className="text-sm text-muted-foreground">
              Dikosongkan berarti memakai bawaan: channel sebagai{' '}
              <code className="font-mono">utm_source</code>, <code className="font-mono">social</code> sebagai
              medium, kode kampanye sebagai campaign.
            </p>
            <div className="grid gap-3 sm:grid-cols-2">
              {(
                [
                  ['UtmSource', 'utm_source'],
                  ['UtmMedium', 'utm_medium'],
                  ['UtmTerm', 'utm_term'],
                  ['UtmContent', 'utm_content'],
                ] as const
              ).map(([kunci, label]) => (
                <div key={kunci} className="grid gap-2">
                  <Label htmlFor={kunci}>{label}</Label>
                  <Input
                    id={kunci}
                    value={form.data[kunci]}
                    onChange={(e) => form.setData(kunci, e.target.value)}
                  />
                </div>
              ))}
            </div>
          </fieldset>

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
