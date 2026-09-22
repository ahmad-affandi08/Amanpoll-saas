import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { KontenKampanye } from '@/features/Pemasaran/types';
import { Combobox } from '@/components/ui/combobox';

export function KonsolKonten({
  akar,
  konten,
  jenis,
}: {
  akar: string;
  konten: KontenKampanye[];
  jenis: string[];
}) {
  const form = useForm({
    Jenis: jenis[0] ?? '',
    Judul: '',
    Tautan: '',
    Catatan: '',
    Urutan: '0',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${akar}/konten`, form.data, {
      preserveScroll: true,
      onSuccess: () => form.reset('Judul', 'Tautan', 'Catatan'),
    });
  };

  return (
    <div className="space-y-4">
      <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
        <div className="grid gap-1.5">
          <Label htmlFor="Jenis">Jenis</Label>
          <Combobox
            nilai={form.data.Jenis}
            onPilih={(v) => form.setData('Jenis', v)}
            opsi={jenis.map((satu) => ({ nilai: satu, label: satu }))}
            className="w-40"
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Judul">Judul</Label>
          <Input
            id="Judul"
            className="w-64"
            value={form.data.Judul}
            onChange={(e) => form.setData('Judul', e.target.value)}
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Tautan">Tautan</Label>
          <Input
            id="Tautan"
            type="url"
            className="w-64"
            value={form.data.Tautan}
            onChange={(e) => form.setData('Tautan', e.target.value)}
          />
        </div>

        <Button type="submit" disabled={form.processing}>
          Tambah konten
        </Button>
      </form>

      {form.errors.Tautan ? <p className="text-sm text-destructive">{form.errors.Tautan}</p> : null}

      {konten.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada konten yang ditautkan.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Jenis</TableHead>
              <TableHead>Judul</TableHead>
              <TableHead>Tautan</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {konten.map((satu) => (
              <TableRow key={satu.Id}>
                <TableCell>
                  <Badge variant="outline">{satu.Jenis}</Badge>
                </TableCell>
                <TableCell className="text-foreground">{satu.Judul}</TableCell>
                <TableCell className="break-all font-mono text-xs text-muted-foreground">
                  {satu.Tautan ?? '—'}
                </TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => router.delete(`${akar}/konten/${satu.Id}`, { preserveScroll: true })}
                  >
                    Hapus
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
