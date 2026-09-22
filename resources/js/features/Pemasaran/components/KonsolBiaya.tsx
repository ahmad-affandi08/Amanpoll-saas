import { FormEvent } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatAngka } from '@/lib/angka';
import type { BiayaKampanye, Kampanye } from '@/features/Pemasaran/types';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';

/** Biaya dicatat per channel per hari, sehingga CAC terbaca pada rentang tanggal mana pun. */
export function KonsolBiaya({
  akar,
  biaya,
  channel,
  total,
}: {
  akar: string;
  biaya: BiayaKampanye[];
  channel: string[];
  total: number;
}) {
  const form = useForm({
    Channel: channel[0] ?? '',
    Tanggal: '',
    Jumlah: '',
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${akar}/biaya`, form.data, {
      preserveScroll: true,
      onSuccess: () => form.setData('Jumlah', ''),
    });
  };

  if (channel.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">
        Kampanye ini belum punya channel. Tambahkan channelnya lebih dulu agar biayanya punya tempat.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
        <div className="grid gap-1.5">
          <Label htmlFor="Channel">Channel</Label>
          <Combobox
            nilai={form.data.Channel}
            onPilih={(v) => form.setData('Channel', v)}
            opsi={channel.map((satu) => ({ nilai: satu, label: satu }))}
            className="w-44"
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Tanggal">Tanggal</Label>
          <DatePicker
            value={form.data.Tanggal}
            onChange={(nilai) => form.setData('Tanggal', nilai)}
            id="Tanggal"
            className="w-40"
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Jumlah">Jumlah</Label>
          <Input
            id="Jumlah"
            type="number"
            min="0"
            step="1"
            className="w-40"
            value={form.data.Jumlah}
            onChange={(e) => form.setData('Jumlah', e.target.value)}
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Catatan">Catatan</Label>
          <Input
            id="Catatan"
            className="w-56"
            value={form.data.Catatan}
            onChange={(e) => form.setData('Catatan', e.target.value)}
          />
        </div>

        <Button type="submit" disabled={form.processing}>
          Catat biaya
        </Button>
      </form>

      {form.errors.Channel ? <p className="text-sm text-destructive">{form.errors.Channel}</p> : null}
      {form.errors.Jumlah ? <p className="text-sm text-destructive">{form.errors.Jumlah}</p> : null}

      <p className="text-sm text-muted-foreground">
        Satu channel pada satu tanggal hanya punya satu angka; mengirim ulang berarti mengoreksinya. Total
        belanja tercatat: <span className="font-mono text-foreground">{formatAngka(total)}</span>.
      </p>

      {biaya.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada biaya tercatat.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Channel</TableHead>
              <TableHead className="text-right">Jumlah</TableHead>
              <TableHead>Catatan</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {biaya.map((satu) => (
              <TableRow key={satu.Id}>
                <TableCell className="font-mono text-xs">{satu.Tanggal}</TableCell>
                <TableCell>{satu.Channel}</TableCell>
                <TableCell className="text-right font-mono">{formatAngka(satu.Jumlah)}</TableCell>
                <TableCell className="text-muted-foreground">{satu.Catatan ?? '—'}</TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => router.delete(`${akar}/biaya/${satu.Id}`, { preserveScroll: true })}
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
