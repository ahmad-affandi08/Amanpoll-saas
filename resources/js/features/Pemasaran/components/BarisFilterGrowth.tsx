import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { FilterGrowth, PilihanGrowth } from '@/features/Pemasaran/types';

export function BarisFilterGrowth({ filter, pilihan }: { filter: FilterGrowth; pilihan: PilihanGrowth }) {
  const [nilai, setNilai] = useState<FilterGrowth>(filter);

  const ubah = (kunci: string, isi: string) =>
    setNilai((lama) => ({ ...lama, [kunci]: isi === '' ? null : isi }));

  const terapkan = (e: FormEvent) => {
    e.preventDefault();

    const bersih = Object.fromEntries(Object.entries(nilai).filter(([, isi]) => isi !== null && isi !== ''));

    router.get(rutePemasaran.growth, bersih, { preserveState: true, preserveScroll: true });
  };

  const daftar: Array<[string, string, string[]]> = [
    ['channel', 'Channel', pilihan.Channel],
    ['kampanye', 'Campaign', pilihan.Kampanye],
    ['industri', 'Industri', pilihan.Industri],
    ['perangkat', 'Device', pilihan.Perangkat],
    ['paket', 'Paket', pilihan.Paket],
    ['referral', 'Referral', pilihan.Referral],
  ];

  return (
    <form onSubmit={terapkan} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
      <div className="grid gap-1.5">
        <Label htmlFor="dari">Dari</Label>
        <Input
          id="dari"
          type="date"
          value={nilai.dari ?? ''}
          onChange={(e) => ubah('dari', e.target.value)}
          className="w-40"
        />
      </div>

      <div className="grid gap-1.5">
        <Label htmlFor="sampai">Sampai</Label>
        <Input
          id="sampai"
          type="date"
          value={nilai.sampai ?? ''}
          onChange={(e) => ubah('sampai', e.target.value)}
          className="w-40"
        />
      </div>

      {daftar.map(([kunci, label, opsi]) => (
        <div key={kunci} className="grid gap-1.5">
          <Label htmlFor={kunci}>{label}</Label>
          <Select value={nilai[kunci] ?? 'semua'} onValueChange={(v) => ubah(kunci, v === 'semua' ? '' : v)}>
            <SelectTrigger id={kunci} className="w-40">
              <SelectValue placeholder="Semua" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Semua</SelectItem>
              {opsi.map((satu) => (
                <SelectItem key={satu} value={satu}>
                  {satu}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      ))}

      <div className="grid gap-1.5">
        <Label htmlFor="landing">Landing page</Label>
        <Input
          id="landing"
          value={nilai.landing ?? ''}
          onChange={(e) => ubah('landing', e.target.value)}
          placeholder="Cari jalur..."
          className="w-48"
        />
      </div>

      <Button type="submit">Terapkan</Button>
      <Button type="button" variant="ghost" onClick={() => router.get(rutePemasaran.growth)}>
        Reset
      </Button>
    </form>
  );
}
