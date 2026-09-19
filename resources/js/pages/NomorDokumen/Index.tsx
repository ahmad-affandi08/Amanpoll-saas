import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { NomorDokumen } from '@/features/NomorDokumen/types';

interface Props {
  nomorDokumen: NomorDokumen[];
}

function DialogFormPola({ pola }: { pola: NomorDokumen | null }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(pola
    ? { JenisDokumen: pola.JenisDokumen, Awalan: pola.Awalan ?? '', FormatNomor: pola.FormatNomor, ResetPeriode: pola.ResetPeriode }
    : { JenisDokumen: '', Awalan: '', FormatNomor: '{Awalan}/{Nomor:4}/{Tahun}', ResetPeriode: 'Tahunan' as const });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = { onSuccess: () => { setBuka(false); form.reset(); } };
    if (pola) {
      form.put(`/platform/nomor-dokumen/${pola.Id}`, opsi);
    } else {
      form.post('/platform/nomor-dokumen', opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={pola ? 'outline' : 'default'} size={pola ? 'sm' : 'default'}>{pola ? 'Ubah' : 'Tambah Pola'}</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>{pola ? 'Ubah Pola Nomor Dokumen' : 'Tambah Pola Nomor Dokumen'}</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Jenis Dokumen</Label>
            <Input value={form.data.JenisDokumen} onChange={(e) => form.setData('JenisDokumen', e.target.value)} placeholder="PerintahKerja, PesananPembelian, dst." disabled={!!pola} />
            {form.errors.JenisDokumen && <p className="text-sm text-destructive">{form.errors.JenisDokumen}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Awalan</Label>
              <Input value={form.data.Awalan} onChange={(e) => form.setData('Awalan', e.target.value)} className="font-mono" />
            </div>
            <div className="space-y-2">
              <Label>Reset Periode</Label>
              <Select value={form.data.ResetPeriode} onValueChange={(v) => form.setData('ResetPeriode', v as 'Tahunan' | 'Bulanan' | 'TidakAda')}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="Tahunan">Tahunan</SelectItem>
                  <SelectItem value="Bulanan">Bulanan</SelectItem>
                  <SelectItem value="TidakAda">Tidak Reset</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Format Nomor</Label>
            <Input value={form.data.FormatNomor} onChange={(e) => form.setData('FormatNomor', e.target.value)} className="font-mono" />
            <p className="text-xs text-muted-foreground">
              Placeholder: {'{Awalan}'}, {'{Nomor}'} atau {'{Nomor:4}'} (padding), {'{Tahun}'}, {'{TahunPendek}'}, {'{Bulan}'}, {'{Periode}'}.
            </p>
            {form.errors.FormatNomor && <p className="text-sm text-destructive">{form.errors.FormatNomor}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function NomorDokumenIndex({ nomorDokumen }: Props) {
  const hapus = (pola: NomorDokumen) => {
    if (!confirm(`Hapus pola nomor "${pola.JenisDokumen}"?`)) return;
    router.delete(`/platform/nomor-dokumen/${pola.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Nomor Dokumen" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Nomor Dokumen</h1>
          <p className="text-sm text-muted-foreground">Pola penomoran otomatis untuk dokumen operasional.</p>
        </div>
        <DialogFormPola pola={null} />
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Jenis Dokumen</TableHead>
              <TableHead>Format</TableHead>
              <TableHead>Pratinjau Berikutnya</TableHead>
              <TableHead>Reset</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {nomorDokumen.map((pola) => (
              <TableRow key={pola.Id}>
                <TableCell className="font-medium text-foreground">{pola.JenisDokumen}</TableCell>
                <TableCell className="font-mono text-sm">{pola.FormatNomor}</TableCell>
                <TableCell className="font-mono text-sm">{pola.Pratinjau}</TableCell>
                <TableCell><Badge variant="outline">{pola.ResetPeriode}</Badge></TableCell>
                <TableCell className="flex justify-end gap-2">
                  <DialogFormPola pola={pola} />
                  <Button variant="ghost" size="sm" onClick={() => hapus(pola)}>Hapus</Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
